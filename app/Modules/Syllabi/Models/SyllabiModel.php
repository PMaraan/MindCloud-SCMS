<?php
// /app/Modules/Syllabi/Models/SyllabiModel.php
declare(strict_types=1);

namespace App\Modules\Syllabi\Models;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * SyllabiModel
 *
 * Implements listing (search + pagination), create, update, delete for the `public.syllabi` table.
 * Updated to use `public.syllabi_programs` (many-to-many) instead of a single program_id column.
 */
final class SyllabiModel
{
    private StorageInterface $db;
    private PDO $pdo;

    /** Match role sets to your controllers (adjust if needed) */
    private array $SYSTEM_ROLES  = ['VPAA','Admin','Librarian','QA','Registrar'];
    private array $DEAN_ROLES    = ['Dean','College Dean'];
    private array $CHAIR_ROLES   = ['Program Chair','Department Chair','Coordinator'];

    public function __construct(StorageInterface $db)
    {
        $this->db  = $db;
        $this->pdo = $db->getConnection();
    }

    // --------- Simple read helpers for folders/college/program views ---------

    /** List colleges for the folders screen. */
    public function getCollegesForFolders(): array
    {
        $sql = "SELECT d.department_id AS college_id,
                       d.short_name,
                       d.department_name AS college_name
                FROM public.departments d
                WHERE d.is_college = true
                ORDER BY COALESCE(NULLIF(d.short_name, ''), d.department_name) ASC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** One college by id. */
    public function getCollege(int $collegeId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT department_id AS college_id,
                    short_name,
                    department_name AS college_name
             FROM public.departments
             WHERE department_id = :id AND is_college = true"
        );
        $stmt->execute([':id' => $collegeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** Programs for a college (department). */
    public function getProgramsByCollege(int $collegeId): array
    {
        $sql = "
            SELECT program_id,
                   program_name,
                   department_id AS college_id
            FROM public.programs
            WHERE department_id = :did
            ORDER BY program_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':did' => $collegeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** One program. */
    public function getProgram(int $programId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT program_id, program_name, department_id AS college_id FROM public.programs WHERE program_id = :pid");
        $stmt->execute([':pid' => $programId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get a list of all courses of the college
     */
    public function getCoursesOfCollege(int $collegeId): array
    {
        $college = $this->getCollege($collegeId); // college_id, short_name, college_name
        if (!$college) return [];
        $cid = (int)$college['college_id'];
        // course_id, course_code, course_name, college_id
        $stmt = $this->pdo->prepare("
            SELECT course_id, course_code, course_name
            FROM public.courses
            WHERE college_id = :cid
            ORDER BY course_code, course_name
        ");
        $stmt->execute([':cid' => $cid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** College-wide syllabi (no per-program split).
     * Returns program_ids/program_names arrays for each syllabus.
     */
    public function getCollegeSyllabi(int $collegeId): array
    {
        $sql = "
            SELECT
                s.syllabus_id,
                s.title,
                s.filename,
                s.version,
                s.status,
                s.college_id,
                d.short_name AS college_short_name,
                d.department_name AS college_name,
                s.course_id,
                c.course_code,
                c.course_name,
                array_remove(array_agg(DISTINCT p.program_name), NULL) AS program_names,
                array_remove(array_agg(DISTINCT sp.program_id), NULL) AS program_ids,
                (SELECT MIN(sp2.program_id) FROM public.syllabi_programs sp2 WHERE sp2.syllabus_id = s.syllabus_id) AS rep_program_id,
                (SELECT p2.program_name FROM public.programs p2 WHERE p2.program_id = (
                    SELECT MIN(sp3.program_id) FROM public.syllabi_programs sp3 WHERE sp3.syllabus_id = s.syllabus_id
                )) AS rep_program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.departments d ON d.department_id = s.college_id
            LEFT JOIN public.courses c      ON c.course_id      = s.course_id
            LEFT JOIN public.syllabi_programs sp ON sp.syllabus_id = s.syllabus_id
            LEFT JOIN public.programs p          ON p.program_id    = sp.program_id
            WHERE s.college_id = :cid
            GROUP BY s.syllabus_id,
                     s.title,
                     s.filename,
                     s.version,
                     s.status,
                     s.college_id,
                     d.short_name,
                     d.department_name,
                     s.course_id,
                     c.course_code,
                     c.course_name,
                     s.updated_at
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT 120";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cid' => $collegeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getProgramSyllabi(int $programId): array
    {
        $sql = "
            SELECT
                s.syllabus_id,
                s.title,
                s.filename,
                s.version,
                s.status,
                s.college_id,
                d.short_name AS college_short_name,
                d.department_name AS college_name,
                s.course_id,
                c.course_code,
                c.course_name,
                array_remove(array_agg(DISTINCT p.program_name), NULL) AS program_names,
                array_remove(array_agg(DISTINCT sp.program_id), NULL) AS program_ids,
                (SELECT MIN(sp2.program_id) FROM public.syllabi_programs sp2 WHERE sp2.syllabus_id = s.syllabus_id) AS rep_program_id,
                (SELECT p2.program_name FROM public.programs p2 WHERE p2.program_id = (
                    SELECT MIN(sp3.program_id) FROM public.syllabi_programs sp3 WHERE sp3.syllabus_id = s.syllabus_id
                )) AS rep_program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.departments d ON d.department_id = s.college_id
            LEFT JOIN public.courses c      ON c.course_id      = s.course_id
            LEFT JOIN public.syllabi_programs sp ON sp.syllabus_id = s.syllabus_id
            LEFT JOIN public.programs p          ON p.program_id    = sp.program_id
            WHERE sp.program_id = :pid
            GROUP BY s.syllabus_id,
                     s.title,
                     s.filename,
                     s.version,
                     s.status,
                     s.college_id,
                     d.short_name,
                     d.department_name,
                     s.course_id,
                     c.course_code,
                     c.course_name,
                     s.updated_at
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT 120";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pid' => $programId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    /**
     * List syllabi with filters:
     * - System roles: see all (optionally filter by search).
     * - Dean roles: default-filter by their college's programs (if $collegeId passed).
     * - Chair roles: default-filter by their $programId (if provided).
     *
     * Returns ['rows'=>..., 'total'=>N]
     */
    public function listSyllabi(
        string $userId,
        string $role,
        ?int $collegeId,
        ?int $programId,
        int $pg,
        int $perpage,
        string $q
    ): array {
        $pg      = max(1, (int)$pg);
        $perpage = max(1, (int)$perpage);
        $offset  = ($pg - 1) * $perpage;
        $limit   = $perpage;

        // Base WHERE fragments (use EXISTS for program/college scoping)
        $wheres = [];
        $params = [];

        // Text search on title/filename
        if ($q !== '') {
            $wheres[] = "(s.title ILIKE :q OR s.filename ILIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        // Role-based scoping
        if (!in_array($role, $this->SYSTEM_ROLES, true)) {
            if (in_array($role, $this->CHAIR_ROLES, true) && $programId) {
                // restrict to syllabi that map to given programId
                $wheres[] = "EXISTS (
                    SELECT 1 FROM public.syllabi_programs sp_ch
                    WHERE sp_ch.syllabus_id = s.syllabus_id AND sp_ch.program_id = :pgid
                )";
                $params[':pgid'] = $programId;
            } elseif (in_array($role, $this->DEAN_ROLES, true) && $collegeId) {
                // restrict to syllabi mapped to any program under the college
                $wheres[] = "EXISTS (
                    SELECT 1 FROM public.syllabi_programs sp_de
                    JOIN public.programs pp ON pp.program_id = sp_de.program_id
                    WHERE sp_de.syllabus_id = s.syllabus_id AND pp.department_id = :cid
                )";
                $params[':cid'] = $collegeId;
            }
        }

        // Course access narrowing
        $courseIds = $this->getAccessibleCourseIds($userId, $role, $collegeId, $programId);
        if (is_array($courseIds)) {
            if (count($courseIds) === 0) {
                // Strict: deny all quickly
                $wheres[] = "1=0";
            } else {
                $in = implode(',', array_map('intval', $courseIds));
                $wheres[] = "s.course_id IN ($in)";
            }
        }

        $whereSql = count($wheres) ? ("WHERE " . implode(" AND ", $wheres)) : "";

        // COUNT distinct syllabi
        $sqlCount = "SELECT COUNT(DISTINCT s.syllabus_id) FROM public.syllabi s $whereSql";
        $stmtC = $this->pdo->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtC->bindValue($k, $v);
        }
        $stmtC->execute();
        $total = (int)$stmtC->fetchColumn();

        // DATA: aggregate program names/ids and include a representative program id+name (rep_program_*)
        $sql = "
            SELECT
                s.syllabus_id,
                s.title,
                s.filename,
                s.version,
                s.status,
                s.college_id,
                d.short_name AS college_short_name,
                d.department_name AS college_name,
                s.course_id,
                c.course_code,
                c.course_name,
                array_remove(array_agg(DISTINCT p.program_name), NULL) AS program_names,
                array_remove(array_agg(DISTINCT sp.program_id), NULL) AS program_ids,
                (SELECT MIN(sp2.program_id) FROM public.syllabi_programs sp2 WHERE sp2.syllabus_id = s.syllabus_id) AS rep_program_id,
                (SELECT p2.program_name FROM public.programs p2 WHERE p2.program_id = (
                    SELECT MIN(sp3.program_id) FROM public.syllabi_programs sp3 WHERE sp3.syllabus_id = s.syllabus_id
                )) AS rep_program_name,
                s.updated_at
            FROM public.syllabi s
            LEFT JOIN public.departments d ON d.department_id = s.college_id
            LEFT JOIN public.courses  c ON c.course_id  = s.course_id
            LEFT JOIN public.syllabi_programs sp ON sp.syllabus_id = s.syllabus_id
            LEFT JOIN public.programs p ON p.program_id = sp.program_id
            $whereSql
            GROUP BY s.syllabus_id,
                     s.title,
                     s.filename,
                     s.version,
                     s.status,
                     s.college_id,
                     d.short_name,
                     d.department_name,
                     s.course_id,
                     c.course_code,
                     c.course_name,
                     s.updated_at
            ORDER BY s.updated_at DESC, s.syllabus_id DESC
            LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Create a syllabus.
     * Accepts either 'program_id' (int) for legacy callers or 'program_ids' (array) for multiple mappings.
     * Expected keys in $payload: title, course_id, program_id|program_ids, version?, status?, filename?, content?
     * Returns new syllabus_id.
     */
    public function createSyllabus(array $payload, string $userId): int
    {
        $this->pdo->beginTransaction();
        try {
            $title     = trim((string)($payload['title'] ?? 'Untitled'));
            $course_id = (int)($payload['course_id'] ?? 0);
            $college_id = isset($payload['college_id']) ? (int)$payload['college_id'] : null;
            if ($college_id !== null && $college_id <= 0) {
                $college_id = null;
            }

            // program mapping(s)
            $programIds = [];
            if (isset($payload['program_ids']) && is_array($payload['program_ids'])) {
                foreach ($payload['program_ids'] as $pid) {
                    $p = (int)$pid;
                    if ($p > 0) $programIds[] = $p;
                }
            } elseif (isset($payload['program_id'])) {
                $p = (int)$payload['program_id'];
                if ($p > 0) $programIds[] = $p;
            }

            if ($course_id <= 0 || count($programIds) === 0) {
                throw new \InvalidArgumentException('course_id and at least one program_id are required.');
            }

            $version   = (string)($payload['version'] ?? null);
            $status    = (string)($payload['status']  ?? 'draft');
            $filename  = (string)($payload['filename'] ?? '');
            $content   = $payload['content'] ?? new \stdClass(); // json

            $pdo = $this->pdo;
            $pdo->beginTransaction();
            try {
                $sql = "
                    INSERT INTO public.syllabi
                        (title, college_id, course_id, version, content, status, filename)
                    VALUES
                        (:title, :college_id, :course_id, :version, CAST(:content AS jsonb), :status, :filename)
                    RETURNING syllabus_id";
                $stmt = $pdo->prepare($sql);
                $params = [
                    ':title'      => $title,
                    ':college_id' => $college_id,
                    ':course_id'  => $course_id,
                    ':version'    => ($version === '' ? null : $version),
                    ':content'    => json_encode($content, JSON_UNESCAPED_UNICODE),
                    ':status'     => ($status === '' ? 'draft' : $status),
                    ':filename'   => ($filename === '' ? null : $filename),
                ];
                $stmt->execute($params);
                $sid = (int)$stmt->fetchColumn();

                $link = $this->pdo->prepare(
                    "INSERT INTO public.syllabi_programs (syllabus_id, program_id)
                     VALUES (:sid, :pid)
                     ON CONFLICT DO NOTHING"
                );
                foreach ($programIds as $pid) {
                    $link->execute([':sid' => $sid, ':pid' => $pid]);
                }

                $this->pdo->commit();
                return $sid;
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Update basic fields; sets updated_at = now().
     * If payload contains 'program_ids' (array) the mappings are replaced atomically.
     */
    public function updateSyllabus(int $id, array $payload, string $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                UPDATE public.syllabi
                   SET title = :title,
                       college_id = :college_id,
                       course_id = :course_id,
                       version = :version,
                       status = :status,
                       updated_at = NOW()
                 WHERE syllabus_id = :sid
            ");
            $stmt->execute([
                ':title'      => $payload['title'],
                ':college_id' => $payload['college_id'],
                ':course_id'  => $payload['course_id'],
                ':version'    => $payload['version'],
                ':status'     => $payload['status'] ?? 'draft',
                ':sid'        => $id,
            ]);

            $this->pdo->prepare("DELETE FROM public.syllabi_programs WHERE syllabus_id = :sid")
                ->execute([':sid' => $id]);

            $link = $this->pdo->prepare(
                "INSERT INTO public.syllabi_programs (syllabus_id, program_id)
                 VALUES (:sid, :pid)
                 ON CONFLICT DO NOTHING"
            );
            foreach ($payload['program_ids'] ?? [] as $pid) {
                $link->execute([':sid' => $id, ':pid' => $pid]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Delete by id. */
    public function deleteSyllabus(int $id, string $userId): void
    {
        if ($id <= 0) throw new \InvalidArgumentException('Invalid id.');
        $stmt = $this->pdo->prepare("DELETE FROM public.syllabi WHERE syllabus_id = :id");
        $stmt->execute([':id' => $id]);
        // syllabi_programs will cascade if FK ON DELETE CASCADE; otherwise left orphan-cleanup can be added.
    }

    // -----------------------
    // Helpers
    // -----------------------

    /**
     * Placeholder: return a list of course_ids the user is allowed to see (for the “same assigned course id” rule).
     * Return null to skip narrowing (e.g., for system roles), or [] to deny all.
     */
    private function getAccessibleCourseIds(string $userId, string $role, ?int $collegeId, ?int $programId): ?array
    {
        // System roles see everything — do not narrow at course level.
        if (in_array($role, $this->SYSTEM_ROLES, true)) {
            return null;
        }

        // Chairs/Deans/Faculty (and everyone else) — narrow by explicit mapping.
        $sql = "
            SELECT uca.course_id
            FROM public.user_course_access uca
            WHERE uca.user_id_no = :uid
            AND (uca.valid_from IS NULL OR uca.valid_from <= CURRENT_DATE)
            AND (uca.valid_to   IS NULL OR uca.valid_to   >= CURRENT_DATE)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $ids  = array_map(static fn($r) => (int)$r['course_id'], $rows);

        // If no mapping rows exist, return null to fallback to role scoping
        return (count($ids) ? $ids : null);
    }

    /**
     * Add a field to SET list if value is not null. If $emptyToNull is true, empty strings become NULL.
     */
    private function maybeSet(array &$sets, array &$params, string $col, $val, bool $emptyToNull = false): void
    {
        if ($val === null) return;
        if ($emptyToNull && $val === '') $val = null;
        $sets[] = "{$col} = :{$col}";
        $params[":{$col}"] = $val;
    }
}
