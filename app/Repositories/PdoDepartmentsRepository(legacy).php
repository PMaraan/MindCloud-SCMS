<?php
// app/Repositories/PdoDepartmentsRepository.php
// PURPOSE: Concrete implementation of DepartmentsRepositoryInterface using PDO.
// Works with Postgres/MySQL (SQL kept generic). Wraps queries in transactions
// where multiple statements must succeed together.

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\StorageInterface;
use PDO;

final class PdoDepartmentsRepository implements DepartmentsRepositoryInterface
{
    private PDO $pdo;

    public function __construct(StorageInterface $db) {
        $this->pdo = $db->getConnection();
    }

    public function exists(int $departmentId): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM departments WHERE department_id = :id');
        $st->bindValue(':id', $departmentId, PDO::PARAM_INT);
        $st->execute();
        return (bool)$st->fetchColumn();
    }

    public function isCollege(int $departmentId): bool
    {
        $st = $this->pdo->prepare('SELECT is_college FROM departments WHERE department_id = :id');
        $st->bindValue(':id', $departmentId, PDO::PARAM_INT);
        $st->execute();
        $val = $st->fetchColumn();
        // Normalize truthy values from PG (t/1/true) and MySQL (1)
        return $val === true || $val === 1 || $val === '1' || $val === 't';
    }

    public function assignDean(int $departmentId, string $deanIdNo): void
    {
        $this->pdo->beginTransaction();
        try {
            // 1) Remove any dean mapping this person had (only 1 dean slot globally)
            $st = $this->pdo->prepare('DELETE FROM department_deans WHERE dean_id = :dean');
            $st->bindValue(':dean', $deanIdNo);
            $st->execute();

            // 2) Remove any dean mapped to this department (only 1 dean per department)
            $st = $this->pdo->prepare('DELETE FROM department_deans WHERE department_id = :dep');
            $st->bindValue(':dep', $departmentId, PDO::PARAM_INT);
            $st->execute();

            // 3) Insert new mapping
            $st = $this->pdo->prepare('
                INSERT INTO department_deans(department_id, dean_id)
                VALUES(:dep, :dean)
            ');
            $st->bindValue(':dep',  $departmentId, PDO::PARAM_INT);
            $st->bindValue(':dean', $deanIdNo);
            $st->execute();

            // 4) Reflect in user_roles for the "Dean" role (optional but typical)
            //    This is case-insensitive on role_name. Adjust if your schema differs.
            $st = $this->pdo->prepare("
                UPDATE user_roles
                SET department_id = :dep
                WHERE id_no = :dean
                  AND role_id IN (SELECT role_id FROM roles WHERE LOWER(role_name) = 'dean')
            ");
            $st->bindValue(':dep',  $departmentId, PDO::PARAM_INT);
            $st->bindValue(':dean', $deanIdNo);
            $st->execute();

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function clearDeanForDepartment(int $departmentId): void
    {
        $this->pdo->beginTransaction();
        try {
            // Find current dean (if any)
            $st = $this->pdo->prepare('SELECT dean_id FROM department_deans WHERE department_id = :dep');
            $st->bindValue(':dep', $departmentId, PDO::PARAM_INT);
            $st->execute();
            $dean = $st->fetchColumn();

            // Clear mapping
            $st = $this->pdo->prepare('DELETE FROM department_deans WHERE department_id = :dep');
            $st->bindValue(':dep', $departmentId, PDO::PARAM_INT);
            $st->execute();

            // Optionally clear the dean's user_roles.department_id (for Dean role only)
            if ($dean) {
                $st = $this->pdo->prepare("
                    UPDATE user_roles
                    SET department_id = NULL
                    WHERE id_no = :dean
                      AND role_id IN (SELECT role_id FROM roles WHERE LOWER(role_name) = 'dean')
                ");
                $st->bindValue(':dean', $dean);
                $st->execute();
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
