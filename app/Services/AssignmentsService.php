<?php
// /app/Services/AssignmentsService.php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * AssignmentsService (Departments-unified, no schema qualifiers)
 * - Enforces: only departments with is_college = TRUE can have a dean.
 * - Cross-DB: pgsql / mysql / sqlsrv supported.
 */
final class AssignmentsService
{
    private PDO $pdo;
    private string $driver;

    public function __construct(StorageInterface $db)
    {
        $this->pdo    = $db->getConnection();
        $this->driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    private function deanRoleId(): ?int
    {
        $stmt = $this->pdo->query("SELECT role_id FROM roles WHERE LOWER(role_name) = 'dean' LIMIT 1");
        $rid  = $stmt->fetchColumn();
        return $rid !== false ? (int)$rid : null;
    }

    private function hasDeanRole(string $idNo, int $rid): bool
    {
        $q = $this->pdo->prepare(
            "SELECT 1 FROM user_roles WHERE id_no = :id AND role_id = :rid LIMIT 1"
        );
        $q->execute([':id' => $idNo, ':rid' => $rid]);
        return (bool)$q->fetchColumn();
    }

    /** Normalize DB booleans from pg/mysql/sqlsrv to PHP bool */
    private function toBool(mixed $v): bool
    {
        return $v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true';
    }

    /**
     * Assign / clear the dean of a department.
     * - Only departments where is_college = TRUE may have a dean.
     * - Target user must already have the Dean role.
     * - Keeps user_roles.department_id in sync (Dean role row).
     * - Ensures one dean per department and one department per dean.
     */
    public function setDepartmentDean(int $departmentId, ?string $newDeanIdNo): void
    {
        $rid = $this->deanRoleId();
        if ($rid === null) {
            throw new \RuntimeException("Dean role not found.");
        }

        $departmentId = (int)$departmentId;
        $newDeanIdNo  = trim((string)$newDeanIdNo);
        if ($newDeanIdNo === '') {
            $newDeanIdNo = null; // treat empty as "clear dean"
        }
        if ($departmentId <= 0) {
            throw new \DomainException("Selected department does not exist.");
        }

        // Verify department exists + read is_college
        $meta = $this->pdo->prepare("
            SELECT is_college
              FROM departments
             WHERE department_id = :id
             LIMIT 1
        ");
        $meta->bindValue(':id', $departmentId, PDO::PARAM_INT);
        $meta->execute();
        $flag = $meta->fetchColumn();
        if ($flag === false) {
            throw new \DomainException("Selected department does not exist.");
        }
        $isCollege = $this->toBool($flag);

        // If assigning to a non-college → reject
        if ($newDeanIdNo !== null && !$isCollege) {
            throw new \DomainException("Cannot assign a dean to a non-college department.");
        }

        $this->pdo->beginTransaction();
        try {
            // Current dean of this department (if any)
            $curStmt = $this->pdo->prepare("
                SELECT dean_id
                  FROM college_deans
                 WHERE department_id = :did
                 LIMIT 1
            ");
            $curStmt->execute([':did' => $departmentId]);
            $currentDeanIdNo = $curStmt->fetchColumn();
            $currentDeanIdNo = ($currentDeanIdNo !== false) ? (string)$currentDeanIdNo : null;

            // Clearing?
            if ($newDeanIdNo === null) {
                // Remove mapping
                $del = $this->pdo->prepare("DELETE FROM college_deans WHERE department_id = :did");
                $del->execute([':did' => $departmentId]);

                // Clear old dean’s department_id (Dean role row)
                if ($currentDeanIdNo !== null) {
                    $upd = $this->pdo->prepare("
                        UPDATE user_roles
                           SET department_id = NULL
                         WHERE id_no = :id AND role_id = :rid
                    ");
                    $upd->execute([':id' => $currentDeanIdNo, ':rid' => $rid]);
                }

                $this->pdo->commit();
                return;
            }

            // Assigning: ensure target user has the Dean role
            if (!$this->hasDeanRole($newDeanIdNo, $rid)) {
                throw new \DomainException("Selected user does not have the Dean role.");
            }

            // If dean changed, clear old dean’s department_id
            if ($currentDeanIdNo !== null && $currentDeanIdNo !== $newDeanIdNo) {
                $c = $this->pdo->prepare("
                    UPDATE user_roles
                       SET department_id = NULL
                     WHERE id_no = :id AND role_id = :rid
                ");
                $c->execute([':id' => $currentDeanIdNo, ':rid' => $rid]);
            }

            // A dean can only map to one department → free the new dean elsewhere
            $free = $this->pdo->prepare("
                DELETE FROM college_deans
                 WHERE dean_id = :id AND department_id <> :did
            ");
            $free->execute([':id' => $newDeanIdNo, ':did' => $departmentId]);

            // Sync user_roles.department_id for the Dean role
            $upd = $this->pdo->prepare("
                UPDATE user_roles
                   SET department_id = :did
                 WHERE id_no = :id AND role_id = :rid
            ");
            $upd->execute([':did' => $departmentId, ':id' => $newDeanIdNo, ':rid' => $rid]);

            // Upsert mapping in college_deans (per-driver)
            if ($this->driver === 'pgsql') {
                $upsert = $this->pdo->prepare("
                    INSERT INTO college_deans (department_id, dean_id)
                    VALUES (:did, :id)
                    ON CONFLICT (department_id) DO UPDATE
                      SET dean_id = EXCLUDED.dean_id
                ");
                $upsert->execute([':did' => $departmentId, ':id' => $newDeanIdNo]);
            } elseif ($this->driver === 'mysql') {
                // Requires UNIQUE index on department_id in college_deans
                $upsert = $this->pdo->prepare("
                    INSERT INTO college_deans (department_id, dean_id)
                    VALUES (:did, :id)
                    ON DUPLICATE KEY UPDATE dean_id = VALUES(dean_id)
                ");
                $upsert->execute([':did' => $departmentId, ':id' => $newDeanIdNo]);
            } elseif ($this->driver === 'sqlsrv') {
                // MERGE pattern for SQL Server
                $sql = "
                    MERGE college_deans AS target
                    USING (SELECT :did AS department_id, :id AS dean_id) AS src
                    ON target.department_id = src.department_id
                    WHEN MATCHED THEN
                      UPDATE SET dean_id = src.dean_id
                    WHEN NOT MATCHED THEN
                      INSERT (department_id, dean_id) VALUES (src.department_id, src.dean_id);
                ";
                $upsert = $this->pdo->prepare($sql);
                $upsert->execute([':did' => $departmentId, ':id' => $newDeanIdNo]);
            } else {
                // Fallback: try delete+insert
                $del = $this->pdo->prepare("DELETE FROM college_deans WHERE department_id = :did");
                $del->execute([':did' => $departmentId]);
                $ins = $this->pdo->prepare("
                    INSERT INTO college_deans (department_id, dean_id) VALUES (:did, :id)
                ");
                $ins->execute([':did' => $departmentId, ':id' => $newDeanIdNo]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Back-compat alias. */
    public function setCollegeDean(int $collegeId, ?string $newDeanIdNo): void
    {
        $this->setDepartmentDean($collegeId, $newDeanIdNo);
    }
}
