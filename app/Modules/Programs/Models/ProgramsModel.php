<?php
// /app/Modules/Programs/Models/ProgramsModel.php
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
                    OR LOWER(COALESCE(d.short_name, d.department_name::text)) LIKE :q
                )
            ";
            $params[':q'] = '%' . $q . '%';
        }

        // total
        $countSql = "
            SELECT COUNT(*)
            FROM programs p
            JOIN departments d ON d.department_id = p.department_id AND d.is_college = TRUE
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
                p.department_id,
                COALESCE(d.short_name, d.department_name) AS college_label
            FROM programs p
            JOIN departments d ON d.department_id = p.department_id
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
                INSERT INTO programs (program_name, department_id)
                VALUES (:program_name, :department_id)
                RETURNING program_id
            ");
            $st->bindValue(':program_name', $data['program_name']);
            $st->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
            $st->execute();
            return (int)$st->fetchColumn();
        }

        $st = $this->pdo->prepare("
            INSERT INTO programs (program_name, department_id)
            VALUES (:program_name, :department_id)
        ");
        $st->bindValue(':program_name', $data['program_name']);
        $st->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
        $st->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function updateProgram(int $programId, array $data): void
    {
        $st = $this->pdo->prepare("
            UPDATE programs
               SET program_name = :program_name,
                   department_id   = :department_id
             WHERE program_id   = :program_id
        ");
        $st->bindValue(':program_id', $programId, PDO::PARAM_INT);
        $st->bindValue(':program_name', $data['program_name']);
        $st->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
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
        $cond = "is_college = TRUE";
        $sql  = "SELECT department_id AS id, COALESCE(short_name, department_name) AS label
                FROM departments
                WHERE {$cond}
            ORDER BY label ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
