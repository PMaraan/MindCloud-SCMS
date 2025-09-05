<?php
// /app/Modules/Colleges/Models/CollegesModel.php
declare(strict_types=1);

namespace App\Modules\Colleges\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class CollegesModel
{
    private PDO $pdo;
    private string $driver;

    public function __construct(StorageInterface $db) {
        $this->pdo    = $db->getConnection();
        $this->driver = (string)$this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    private function fullNameExpr(string $a): string {
        return match ($this->driver) {
            'pgsql' => "TRIM(CONCAT_WS(' ', {$a}.fname, NULLIF({$a}.mname,''), {$a}.lname))",
            'mysql' => "TRIM(CONCAT_WS(' ', {$a}.fname, NULLIF({$a}.mname,''), {$a}.lname))",
            default => "TRIM(CONCAT_WS(' ', {$a}.fname, NULLIF({$a}.mname,''), {$a}.lname))",
        };
    }

    private function limitOffsetClause(int $limit, int $offset): string {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        return match ($this->driver) {
            'pgsql','mysql' => "LIMIT {$limit} OFFSET {$offset}",
            'sqlsrv','oci'  => "OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY",
            default         => "LIMIT {$limit} OFFSET {$offset}",
        };
    }

    public function getPage(?string $q, int $limit, int $offset): array {
        $where  = ' WHERE 1=1 ';
        $params = [];
        if ($q !== null && $q !== '') {
            $where .= " AND (LOWER(c.college_name) LIKE :q OR LOWER(c.short_name) LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM colleges c{$where}");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        $pageClause = $this->limitOffsetClause($limit, $offset);
        $fullName   = $this->fullNameExpr('u');

        $sqlList = "
        SELECT
            c.college_id,
            c.short_name,
            c.college_name,
            cd.dean_id           AS dean_id_no,
            {$fullName}          AS dean_full_name
        FROM colleges c
        LEFT JOIN college_deans cd ON cd.college_id = c.college_id
        LEFT JOIN users u          ON u.id_no      = cd.dean_id
        {$where}
        ORDER BY c.college_name ASC
        {$pageClause}";
        $stmt2 = $this->pdo->prepare($sqlList);
        foreach ($params as $k => $v) $stmt2->bindValue($k, $v);
        $stmt2->execute();

        $rows = $stmt2->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        return ['rows' => $rows, 'total' => $total];
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT college_id, short_name, college_name FROM colleges WHERE college_id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        if ($this->driver === 'pgsql') {
            $stmt = $this->pdo->prepare(
                "INSERT INTO colleges (short_name, college_name)
                 VALUES (:short_name, :college_name)
                 RETURNING college_id"
            );
            $stmt->bindValue(':short_name',   trim((string)($data['short_name'] ?? '')));
            $stmt->bindValue(':college_name', trim((string)($data['college_name'] ?? '')));
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO colleges (short_name, college_name)
             VALUES (:short_name, :college_name)"
        );
        $stmt->bindValue(':short_name',   trim((string)($data['short_name'] ?? '')));
        $stmt->bindValue(':college_name', trim((string)($data['college_name'] ?? '')));
        $stmt->execute();
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE colleges
                SET short_name = :short_name,
                    college_name = :college_name
              WHERE college_id = :id"
        );
        $stmt->bindValue(':short_name',   trim((string)($data['short_name'] ?? '')));
        $stmt->bindValue(':college_name', trim((string)($data['college_name'] ?? '')));
        $stmt->bindValue(':id',           $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM colleges WHERE college_id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        return $stmt->execute();
    }
}
