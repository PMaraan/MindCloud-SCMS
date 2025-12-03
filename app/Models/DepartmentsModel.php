<?php
// app/Models/DepartmentsModel.php
// PURPOSE: Low-level CRUD for the departments table (no schema qualifiers).
// STYLE:   Cross-DB (pgsql/mysql/sqlsrv). getPage returns ['total','rows'].

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class DepartmentsModel
{
    private PDO $pdo;
    private string $driver;

    public function __construct(private StorageInterface $db)
    {
        $this->pdo    = $db->getConnection();
        $this->driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private function limitOffsetClause(int $limit, int $offset): string
    {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        return match ($this->driver) {
            'pgsql', 'mysql' => "LIMIT $limit OFFSET $offset",
            'sqlsrv'         => "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY",
            default          => "LIMIT $limit OFFSET $offset",
        };
    }

    private function deanFullNameExpr(): string
    {
        return match ($this->driver) {
            'mysql' =>
                "TRIM(CONCAT(COALESCE(u.fname,''),' ',COALESCE(u.mname,''),' ',COALESCE(u.lname,'')))",
            'sqlsrv' =>
                "LTRIM(RTRIM(CONCAT(COALESCE(u.fname,''),' ',COALESCE(u.mname,''),' ',COALESCE(u.lname,''))))",
            default => // pgsql
                "TRIM(COALESCE(u.fname,'') || ' ' || COALESCE(u.mname,'') || ' ' || COALESCE(u.lname,''))",
        };
    }

    /**
     * Paginated list with optional search.
     * Returns: ['total' => int, 'rows' => array[]]
     * - Search matches department_name, short_name (case-insensitive).
     * - Includes dean info via LEFT JOIN on college_deans: dean_id_no, dean_full_name.
     */
    public function getPage(
        ?string $q, 
        int $limit, 
        int $offset,
        string $status = 'active' // <-- add status param, default to 'active'
    ): array {
        $where  = ' WHERE 1=1 ';
        $params = [];
        if ($q !== null && $q !== '') {
            $where .= ' AND (LOWER(d.department_name) LIKE :q OR LOWER(d.short_name) LIKE :q) ';
            $params[':q'] = '%' . mb_strtolower($q) . '%';
        }
        // Status filter
        if ($status !== 'all') {
            $where .= ' AND d.status = :status ';
            $params[':status'] = $status;
        }

        $countSql = "SELECT COUNT(*) FROM departments d {$where}";
        $st = $this->pdo->prepare($countSql);
        $st->execute($params);
        $total = (int)$st->fetchColumn();

        $pageClause = $this->limitOffsetClause($limit, $offset);
        $fullName   = $this->deanFullNameExpr();

        $rowsSql = "
            SELECT
                d.department_id,
                d.short_name,
                d.department_name,
                d.is_college,
                d.status,
                cd.dean_id                AS dean_id_no,
                {$fullName}               AS dean_full_name
            FROM departments d
            LEFT JOIN college_deans cd
            ON cd.department_id = d.department_id
            LEFT JOIN users u
            ON u.id_no = cd.dean_id
            {$where}
            ORDER BY LOWER(d.department_name) ASC, LOWER(d.short_name) ASC
            {$pageClause}
        ";
        $st2 = $this->pdo->prepare($rowsSql);
        foreach ($params as $k => $v) $st2->bindValue($k, $v);
        $st2->execute();

        $rows = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return ['total' => $total, 'rows' => $rows];
    }

    /** Create department; returns new department_id. */
    public function create(array $data): int
    {
        $short  = trim((string)($data['short_name'] ?? ''));
        $name   = trim((string)($data['department_name'] ?? ''));
        $isCol  = !empty($data['is_college']);
        $status = in_array($data['status'] ?? '', ['active', 'archived'], true)
            ? $data['status'] : 'active';

        if ($short === '' || $name === '') {
            throw new \InvalidArgumentException('short_name and department_name are required.');
        }

        if ($this->driver === 'pgsql') {
            $sql = "
                INSERT INTO departments (short_name, department_name, is_college, status)
                VALUES (:short, :name, :is_college, :status)
                RETURNING department_id
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':short', $short);
            $stmt->bindValue(':name',  $name);
            $stmt->bindValue(':is_college', $isCol, PDO::PARAM_BOOL);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        }

        $sql = "
            INSERT INTO departments (short_name, department_name, is_college, status)
            VALUES (:short, :name, :is_college, :status)
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':short', $short);
        $stmt->bindValue(':name',  $name);
        $stmt->bindValue(':is_college', $isCol, PDO::PARAM_BOOL);
        $stmt->bindValue(':status', $status);
        $stmt->execute();

        return (int)$this->pdo->lastInsertId();
    }

    /** Update department; returns true if a row changed. */
    public function update(int $id, array $data): bool
    {
        $short  = trim((string)($data['short_name'] ?? ''));
        $name   = trim((string)($data['department_name'] ?? ''));
        $isCol  = !empty($data['is_college']);
        $status = in_array($data['status'] ?? '', ['active', 'archived'], true)
            ? $data['status'] : 'active';

        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid department_id.');
        }
        if ($short === '' || $name === '') {
            throw new \InvalidArgumentException('short_name and department_name are required.');
        }

        $sql = "
            UPDATE departments
            SET short_name      = :short,
                department_name = :name,
                is_college      = :is_college,
                status          = :status
            WHERE department_id   = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':short', $short);
        $stmt->bindValue(':name',  $name);
        $stmt->bindValue(':is_college', $isCol, PDO::PARAM_BOOL);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':id',    $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /** Delete department; returns true if a row was deleted. */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM departments WHERE department_id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** Dropdown helpers for Accounts module */
    public function getColleges(): array
    {
        $cond = ($this->driver === 'pgsql') ? "is_college = TRUE" : "is_college = 1";
        $sql  = "SELECT department_id, short_name, department_name
                   FROM departments
                  WHERE {$cond}
               ORDER BY short_name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getNonColleges(): array
    {
        $cond = ($this->driver === 'pgsql') ? "is_college = FALSE" : "is_college = 0";
        $sql  = "SELECT department_id, short_name, department_name
                   FROM departments
                  WHERE {$cond}
               ORDER BY short_name ASC";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
