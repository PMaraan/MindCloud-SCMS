<?php
// /app/Modules/Courses/Models/CoursesModel.php
declare(strict_types=1);

namespace App\Modules\Courses\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class CoursesModel
{
    public function __construct(private StorageInterface $db) {}

    public function getPage(?string $q, int $limit, int $offset): array
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
                OR LOWER(col.short_name)      LIKE :q
            )
            ";
            $params[':q'] = '%' . $q . '%';
        }

        $countSql = "
          SELECT COUNT(*) AS cnt
            FROM public.courses c
            JOIN public.curricula cur ON cur.curriculum_id = c.curriculum_id
       LEFT JOIN public.colleges  col ON col.college_id   = c.college_id
           $where
        ";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $rowsSql = "
            SELECT
                c.course_id,
                c.course_code,
                c.course_name,
                c.college_id,
                c.curriculum_id,
                cur.curriculum_code,
                cur.title AS curriculum_title,
                col.short_name AS college_short
            FROM public.courses c
            JOIN public.curricula cur ON cur.curriculum_id = c.curriculum_id
            LEFT JOIN public.colleges  col ON col.college_id   = c.college_id
            $where
            ORDER BY LOWER(c.course_code), LOWER(c.course_name)
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($rowsSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        return $this->db->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $pdo = $this->db->getConnection();
        $sql = "
            INSERT INTO public.courses (course_code, course_name, college_id, curriculum_id)
            VALUES (:code, :name, :college_id, :curriculum_id)
            RETURNING course_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':code', $data['course_code']);
        $stmt->bindValue(':name', $data['course_name']);
        if ($data['college_id'] === null) {
            $stmt->bindValue(':college_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':college_id', (int)$data['college_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':curriculum_id', (int)$data['curriculum_id'], PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $pdo = $this->db->getConnection();
        $sql = "
          UPDATE public.courses
             SET course_code   = :code,
                 course_name   = :name,
                 college_id    = :college_id,
                 curriculum_id = :curriculum_id
           WHERE course_id     = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':code', $data['course_code']);
        $stmt->bindValue(':name', $data['course_name']);
        if ($data['college_id'] === null) {
            $stmt->bindValue(':college_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':college_id', (int)$data['college_id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':curriculum_id', (int)$data['curriculum_id'], PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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
}
