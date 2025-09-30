<?php
// app/Models/DepartmentsModel.php
// PURPOSE: Low-level CRUD for the departments table.
// STYLE:   Mirrors the CoursesModel pattern (getPage returns ['total','rows']).

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class DepartmentsModel
{
    public function __construct(private StorageInterface $db) {}

    /**
     * Paginated list with optional search.
     * Returns: ['total' => int, 'rows' => array[]]
     *
     * - Search matches department_name, short_name (case-insensitive).
     * - Includes dean info via LEFT JOIN:
     *     dean_id_no, dean_full_name (nullable if unassigned)
     */
    public function getPage(?string $q, int $limit, int $offset): array
    {
        $pdo = $this->db->getConnection();

        $where  = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            // Lowercase once; we use LOWER(column) LIKE :q in SQL
            $params[':q'] = '%' . mb_strtolower($q) . '%';
            $where .= "
              AND (
                   LOWER(d.department_name) LIKE :q
                OR LOWER(d.short_name)      LIKE :q
              )
            ";
        }

        // ---- total (count distinct because of LEFT JOINs) ----
        $countSql = "
            SELECT COUNT(DISTINCT d.department_id)
              FROM public.departments d
            LEFT JOIN public.department_deans dd ON dd.department_id = d.department_id
            LEFT JOIN public.users u ON u.id_no = dd.dean_id
            $where
        ";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // ---- rows ----
        // dean_full_name is formatted "Last, First M." if mname exists, else "Last, First"
        // If there is no dean, dean_id_no and dean_full_name will be NULL.
        $rowsSql = "
            SELECT
                d.department_id,
                d.short_name,
                d.department_name,
                d.is_college,
                dd.dean_id            AS dean_id_no,
                CASE
                  WHEN u.id_no IS NULL THEN NULL
                  ELSE
                    TRIM(
                      COALESCE(u.lname, '') || ', ' ||
                      COALESCE(u.fname, '') ||
                      CASE
                        WHEN u.mname IS NOT NULL AND u.mname <> '' THEN
                          ' ' || LEFT(u.mname, 1) || '.'
                        ELSE
                          ''
                      END
                    )
                END AS dean_full_name
            FROM public.departments d
            LEFT JOIN public.department_deans dd ON dd.department_id = d.department_id
            LEFT JOIN public.users u ON u.id_no = dd.dean_id
            $where
            ORDER BY LOWER(d.department_name), LOWER(d.short_name)
            LIMIT :lim OFFSET :off
        ";
        $stmt = $pdo->prepare($rowsSql);
        // re-bind search params if any
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Normalize is_college to bool for convenience in views
        foreach ($rows as &$r) {
            if (array_key_exists('is_college', $r)) {
                $r['is_college'] = (bool)$r['is_college'];
            }
        }

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * Create a department. Returns new department_id.
     * Required keys in $data: short_name, department_name
     * Optional: is_college (truthy/falsey)
     */
    public function create(array $data): int
    {
        $pdo   = $this->db->getConnection();
        $short = trim((string)($data['short_name'] ?? ''));
        $name  = trim((string)($data['department_name'] ?? ''));
        $isCol = !empty($data['is_college']);

        if ($short === '' || $name === '') {
            throw new \InvalidArgumentException('short_name and department_name are required.');
        }

        $sql = "
            INSERT INTO public.departments (short_name, department_name, is_college)
            VALUES (:short, :name, :is_college)
            RETURNING department_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':short', $short);
        $stmt->bindValue(':name',  $name);
        $stmt->bindValue(':is_college', $isCol, PDO::PARAM_BOOL);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Update a department by id. Returns true if a row changed.
     */
    public function update(int $id, array $data): bool
    {
        $pdo   = $this->db->getConnection();
        $short = trim((string)($data['short_name'] ?? ''));
        $name  = trim((string)($data['department_name'] ?? ''));
        $isCol = !empty($data['is_college']);

        if ($id <= 0) {
            throw new \InvalidArgumentException('Invalid department_id.');
        }
        if ($short === '' || $name === '') {
            throw new \InvalidArgumentException('short_name and department_name are required.');
        }

        $sql = "
            UPDATE public.departments
               SET short_name      = :short,
                   department_name = :name,
                   is_college      = :is_college
             WHERE department_id   = :id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':short', $short);
        $stmt->bindValue(':name',  $name);
        $stmt->bindValue(':is_college', $isCol, PDO::PARAM_BOOL);
        $stmt->bindValue(':id',    $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a department by id. Returns true if a row was deleted.
     */
    public function delete(int $id): bool
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM public.departments WHERE department_id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
