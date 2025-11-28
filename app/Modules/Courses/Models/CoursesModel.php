<?php
// /app/Modules/Courses/Models/CoursesModel.php
declare(strict_types=1);

namespace App\Modules\Courses\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class CoursesModel
{
    public function __construct(private StorageInterface $db) {}

    public function getPage(?string $q, int $limit, int $offset, ?int $restrictCollegeId = null): array
    {
        $pdo = $this->db->getConnection();

        $where = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            $where .= "
            AND (
                LOWER(c.course_code)       LIKE :q
                OR LOWER(c.course_name)       LIKE :q
                OR LOWER(cur.curriculum_code) LIKE :q
                OR LOWER(cur.title)           LIKE :q
                OR LOWER(d.short_name)        LIKE :q
            )";
            $params[':q'] = '%' . $q . '%';
        }

        if ($restrictCollegeId !== null && $restrictCollegeId > 0) {
            $where .= ' AND c.college_id = :restrictCollege';
            $params[':restrictCollege'] = $restrictCollegeId;
        }

        $countSql = "
            SELECT COUNT(DISTINCT c.course_id)
              FROM public.courses c
              LEFT JOIN curriculum_courses cc ON cc.course_id = c.course_id
              LEFT JOIN curricula cur ON cur.curriculum_id = cc.curriculum_id
              LEFT JOIN departments d ON d.department_id = c.college_id
            $where";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $rowsSql = "
            SELECT
                c.course_id,
                c.course_code,
                c.course_name,
                c.college_id,
                COALESCE(d.short_name, '—') AS department_short,
                STRING_AGG(DISTINCT cur.curriculum_code, ', ' ORDER BY cur.curriculum_code) AS curricula,
                STRING_AGG(DISTINCT cur.curriculum_id::text, ',' ORDER BY cur.curriculum_id::text) AS curricula_ids
            FROM courses c
            LEFT JOIN departments d ON d.department_id = c.college_id
            LEFT JOIN public.curriculum_courses cc ON cc.course_id = c.course_id
            LEFT JOIN public.curricula cur ON cur.curriculum_id = cc.curriculum_id
            $where
            GROUP BY c.course_id, c.course_code, c.course_name, c.college_id, d.short_name
            ORDER BY LOWER(c.course_code), LOWER(c.course_name)
            LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($rowsSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return ['total' => $total, 'rows' => []];
        }

        $courseIds   = array_column($rows, 'course_id');
        $assignments = $this->getAssignmentsIndexedByCourse($courseIds);

        foreach ($rows as &$row) {
            $assigned = $assignments[$row['course_id']] ?? [];
            $row['assigned_professors']      = $assigned;
            $row['assigned_professor_ids']   = array_column($assigned, 'id');
            $row['assigned_professor_names'] = array_column($assigned, 'name');
        }
        unset($row);

        return ['total' => $total, 'rows' => $rows];
    }

    public function listColleges(): array
    {
        $sql = "SELECT college_id, short_name FROM public.colleges ORDER BY LOWER(short_name)";
        return $this->db->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listCurricula(): array
    {
        $sql = "SELECT curriculum_id, curriculum_code, title AS curriculum_title
              FROM public.curricula
             ORDER BY LOWER(curriculum_code)";
        return $this->db->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $pdo = $this->db->getConnection();
        $sql = "
            INSERT INTO courses (course_code, course_name, college_id)
            VALUES (:code, :name, :college_id)
            RETURNING course_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':code', $data['course_code']);
        $stmt->bindValue(':name', $data['course_name']);
        if ($data['department_id'] === null) {
            $stmt->bindValue(':college_id', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':college_id', (int)$data['department_id'], \PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = $this->db->getConnection();
        $sql = "
        UPDATE public.courses
            SET course_code = :code,
                course_name = :name,
                college_id  = :college_id
        WHERE course_id   = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':code', $data['course_code']);
        $stmt->bindValue(':name', $data['course_name']);
        if ($data['department_id'] === null) {
            $stmt->bindValue(':college_id', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':college_id', (int)$data['department_id'], \PDO::PARAM_INT);
        }
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM public.courses WHERE course_id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function setCourseCurricula(int $courseId, array $curriculumIds): void
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            // Remove old links
            $del = $pdo->prepare("DELETE FROM public.curriculum_courses WHERE course_id = :cid");
            $del->execute([':cid' => $courseId]);

            // Insert new links (ignore invalids, rely on FK to error if bad id)
            if (!empty($curriculumIds)) {
                $ins = $pdo->prepare("
                    INSERT INTO public.curriculum_courses (curriculum_id, course_id)
                    VALUES (:curid, :cid)
                    ON CONFLICT DO NOTHING
                ");
                foreach ($curriculumIds as $curId) {
                    $curId = (int)$curId;
                    if ($curId > 0) {
                        $ins->execute([':curid' => $curId, ':cid' => $courseId]);
                    }
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function listProfessors(?int $collegeId = null): array
    {
        $sql = "
            SELECT DISTINCT
                   u.id_no,
                   TRIM(u.lname || ', ' || u.fname || COALESCE(' ' || u.mname, '')) AS full_name,
                   ur.department_id
              FROM public.user_roles ur
              JOIN public.roles r ON r.role_id = ur.role_id
              JOIN public.users u ON u.id_no = ur.id_no
             WHERE LOWER(r.role_name) = 'professor'
        ";

        $params = [];
        if ($collegeId !== null && $collegeId > 0) {
            $sql    .= ' AND ur.department_id = :collegeId';
            $params[':collegeId'] = $collegeId;
        }

        // Use positional ordering to satisfy DISTINCT requirements.
        $sql .= ' ORDER BY 2, 1';

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function setCourseProfessors(int $courseId, array $professorIds): void
    {
        $pdo   = $this->db->getConnection();
        $clean = [];

        foreach ($professorIds as $pid) {
            $pid = trim((string)$pid);
            if ($pid !== '' && !isset($clean[$pid])) {
                $clean[$pid] = $pid;
            }
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM public.course_assignments WHERE course_id = :cid')
                ->execute([':cid' => $courseId]);

            if (!empty($clean)) {
                $insert = $pdo->prepare("
                    INSERT INTO public.course_assignments (professor_id, course_id, semester, year, status, date_assigned)
                    VALUES (:pid, :cid, NULL, NULL, 'active', NOW())
                    ON CONFLICT (professor_id, course_id)
                    DO UPDATE SET status = EXCLUDED.status,
                                  date_assigned = EXCLUDED.date_assigned
                ");

                foreach ($clean as $pid) {
                    $insert->execute([
                        ':pid' => $pid,
                        ':cid' => $courseId,
                    ]);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function getAssignmentsIndexedByCourse(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));

        $sql = "
            SELECT
                ca.course_id,
                ca.professor_id,
                TRIM(u.lname || ', ' || u.fname || COALESCE(' ' || u.mname, '')) AS full_name,
                ca.status,
                ca.date_assigned
            FROM public.course_assignments ca
            JOIN public.users u ON u.id_no = ca.professor_id
           WHERE ca.course_id IN ($placeholders)
        ";

        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute(array_values($courseIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int)$row['course_id']][] = [
                'id'     => $row['professor_id'],
                'name'   => $row['full_name'],
                'status' => $row['status'],
                'dated'  => $row['date_assigned'],
            ];
        }

        return $indexed;
    }

}
