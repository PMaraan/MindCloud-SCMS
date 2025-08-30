<?php
// app/Modules/Colleges/Models/CollegesModel.php
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
        $this->driver = (string)$this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME); // 'pgsql','mysql','sqlsrv','oci',...
    }

    private function limitOffsetClause(int $limit, int $offset): string
    {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        return match ($this->driver) {
            'pgsql','mysql' => "LIMIT {$limit} OFFSET {$offset}",
            'sqlsrv','oci'  => "OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY",
            default         => "LIMIT {$limit} OFFSET {$offset}",
        };
    }

    /**
     * Example paged query skeleton; change the FROM/SELECT for your module.
     * Returns ['rows'=>array, 'total'=>int].
     */
    public function getPage(?string $q, int $limit, int $offset): array
    {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $where  = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            $where .= " AND (LOWER(t.name) LIKE :q OR LOWER(t.code) LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        // Count
        $sqlCount = "SELECT COUNT(*) FROM your_table t" . $where;
        $stmt = $this->pdo->prepare($sqlCount);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        // Rows
        $pageClause = $this->limitOffsetClause($limit, $offset);
        $sqlList = "
            SELECT t.*
            FROM your_table t
            " . $where . "
            ORDER BY t.name ASC
            " . $pageClause;

        $stmt2 = $this->pdo->prepare($sqlList);
        foreach ($params as $k => $v) $stmt2->bindValue($k, $v);
        $stmt2->execute();

        $rows = $stmt2->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        return ['rows' => $rows, 'total' => $total];
    }
}