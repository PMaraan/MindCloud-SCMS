<?php
declare(strict_types=1);

namespace App\Modules\Programs\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class ProgramsModel
{
    private PDO $pdo;
    private string $driver;

    public function __construct(StorageInterface $db)
    {
        $this->pdo    = $db->getConnection();
        $this->driver = (string)$this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    private function pageClause(int $limit, int $offset): string
    {
        if (in_array($this->driver, ['pgsql', 'mysql'], true)) {
            return " LIMIT {$limit} OFFSET {$offset} ";
        }
        return " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY ";
    }

    /** @return array{0: list<array<string,mixed>>, 1: int} */
    public function getProgramsPage(?string $q, int $limit, int $offset): array
    {
        $where = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            $where .= "
                AND (
                    LOWER(p.program_name) LIKE :q
                    OR LOWER(COALESCE(c.short_name, c.college_name::text)) LIKE :q
                )
            ";
            $params[':q'] = '%' . $q . '%';
        }

        // total
        $countSql = "
            SELECT COUNT(*)
            FROM programs p
            JOIN colleges c ON c.college_id = p.college_id
            {$where}
        ";
        $st = $this->pdo->prepare($countSql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->execute();
        $total = (int)$st->fetchColumn();

        // page
        $sql = "
            SELECT
                p.program_id,
                p.program_name,
                p.college_id,
                COALESCE(c.short_name, c.college_name) AS college_label
            FROM programs p
            JOIN colleges c ON c.college_id = p.college_id
            {$where}
            ORDER BY p.program_name ASC, p.program_id ASC
            " . $this->pageClause($limit, $offset);

        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return [$rows, $total];
    }

    public function createProgram(array $data): int
    {
        if ($this->driver === 'pgsql') {
            $st = $this->pdo->prepare("
                INSERT INTO programs (program_name, college_id)
                VALUES (:program_name, :college_id)
                RETURNING program_id
            ");
            $st->bindValue(':program_name', $data['program_name']);
            $st->bindValue(':college_id', $data['college_id'], PDO::PARAM_INT);
            $st->execute();
            return (int)$st->fetchColumn();
        }

        $st = $this->pdo->prepare("
            INSERT INTO programs (program_name, college_id)
            VALUES (:program_name, :college_id)
        ");
        $st->bindValue(':program_name', $data['program_name']);
        $st->bindValue(':college_id', $data['college_id'], PDO::PARAM_INT);
        $st->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updateProgram(int $programId, array $data): void
    {
        $st = $this->pdo->prepare("
            UPDATE programs
               SET program_name = :program_name,
                   college_id   = :college_id
             WHERE program_id   = :program_id
        ");
        $st->bindValue(':program_id', $programId, PDO::PARAM_INT);
        $st->bindValue(':program_name', $data['program_name']);
        $st->bindValue(':college_id', $data['college_id'], PDO::PARAM_INT);
        $st->execute();
    }

    public function deleteProgram(int $programId): void
    {
        $st = $this->pdo->prepare("DELETE FROM programs WHERE program_id = :id");
        $st->bindValue(':id', $programId, PDO::PARAM_INT);
        $st->execute();
    }

    /** For dropdowns */
    public function getCollegesList(): array
    {
        $sql = "SELECT college_id AS id, COALESCE(short_name, college_name) AS label
                  FROM colleges
              ORDER BY label ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
