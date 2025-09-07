<?php
declare(strict_types=1);

namespace App\Modules\Curricula\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class CurriculaModel
{
    private StorageInterface $db;
    private PDO $pdo;

    public function __construct(StorageInterface $db) {
        $this->db  = $db;
        $this->pdo = $db->getConnection(); // <-- use PDO from StorageInterface
    }

    public function count(?string $q): int
    {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM public.curricula c
            WHERE 1=1
        ";
        $params = [];
        if ($q !== null && $q !== '') {
            $sql .= " AND (LOWER(c.curriculum_code) LIKE :q OR LOWER(c.title) LIKE :q) ";
            $params[':q'] = '%' . $q . '%';
        }
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getPage(?string $q, int $limit, int $offset): array
    {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $sql = "
            SELECT
              c.curriculum_id,
              c.curriculum_code,
              c.title,
              c.effective_start,
              c.effective_end
            FROM public.curricula c
            WHERE 1=1
        ";
        $params = [];
        if ($q !== null && $q !== '') {
            $sql .= " AND (LOWER(c.curriculum_code) LIKE :q OR LOWER(c.title) LIKE :q) ";
            $params[':q'] = '%' . $q . '%';
        }
        $sql .= " ORDER BY c.curriculum_id DESC
                  LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT curriculum_id, curriculum_code, title, effective_start, effective_end
            FROM public.curricula
            WHERE curriculum_id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO public.curricula (curriculum_code, title, effective_start, effective_end)
            VALUES (:code, :title, :start, :end)
            RETURNING curriculum_id
        ");
        $stmt->bindValue(':code',  (string)$data['curriculum_code'], PDO::PARAM_STR);
        $stmt->bindValue(':title', (string)$data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':start', (string)$data['effective_start'], PDO::PARAM_STR);
        if (empty($data['effective_end'])) {
            $stmt->bindValue(':end', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':end', (string)$data['effective_end'], PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE public.curricula
            SET curriculum_code = :code,
                title = :title,
                effective_start = :start,
                effective_end = :end
            WHERE curriculum_id = :id
        ");
        $stmt->bindValue(':code',  (string)$data['curriculum_code'], PDO::PARAM_STR);
        $stmt->bindValue(':title', (string)$data['title'], PDO::PARAM_STR);
        $stmt->bindValue(':start', (string)$data['effective_start'], PDO::PARAM_STR);
        if (empty($data['effective_end'])) {
            $stmt->bindValue(':end', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':end', (string)$data['effective_end'], PDO::PARAM_STR);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM public.curricula WHERE curriculum_id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
