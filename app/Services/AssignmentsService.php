<?php
// /app/Services/AssignmentsService.php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * AssignmentsService
 *
 * Cross-DB (pgsql / mysql / sqlsrv) helpers for assignment rules:
 *  • College Deans
 *      - Only departments where is_college = TRUE may have a dean.
 *      - Target user must already have the Dean role.
 *      - Ensures 1 dean per department and 1 department per dean.
 *      - Keeps user_roles.department_id in sync for the Dean role.
 *
 *  • Program Chairs
 *      - Target user must already have the Program Chair role.
 *      - Ensures 1 chair per program and 1 program per chair.
 *      - Writes program_chairs (program_id, chair_id) with proper upsert.
 *      - (Optional best-effort) Syncs user_roles.program_id for the Program Chair role
 *        if that column exists; any schema mismatch is silently ignored.
 *
 * Notes:
 *  - No schema qualifiers (assumes default schema).
 *  - Booleans are normalized across drivers.
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

    private function chairRoleId(): ?int
    {
        $stmt = $this->pdo->query("SELECT role_id FROM roles WHERE TRIM(LOWER(role_name)) = 'chair' LIMIT 1");
        $rid  = $stmt->fetchColumn();
        return $rid !== false ? (int)$rid : null;
    }

    private function hasChairRole(string $idNo, int $rid): bool
    {
        $q = $this->pdo->prepare(
            "SELECT 1 FROM user_roles WHERE id_no = :id AND role_id = :rid LIMIT 1"
        );
        $q->execute([':id' => $idNo, ':rid' => $rid]);
        return (bool)$q->fetchColumn();
    }

    private function programChairRoleId(): ?int
    {
        $stmt = $this->pdo->query("SELECT role_id FROM roles WHERE LOWER(role_name) = 'program chair' LIMIT 1");
        $rid  = $stmt->fetchColumn();
        return $rid !== false ? (int)$rid : null;
    }

    private function hasProgramChairRole(string $idNo, int $rid): bool
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

    /**
     * Assign / clear the Chair of a Program.
     * Behavior mirrors setDepartmentDean:
     * - Target user must already have the "Chair" role.
     * - Syncs user_roles.department_id for the Chair role to the program's college department_id.
     * - Ensures one chair per program and one program per chair.
     * - Clearing sets program_chairs mapping to none and user_roles.department_id (Chair role row) to NULL.
     *
     * @param int         $programId     The program to affect
     * @param string|null $newChairIdNo  The user's id_no to assign, or null/'' to clear
     * @throws \DomainException|\RuntimeException|\Throwable
     */
    public function setProgramChair(int $programId, ?string $newChairIdNo): void
    {
        $rid = $this->chairRoleId();
        if ($rid === null) {
            throw new \RuntimeException("Chair role not found.");
        }

        $programId   = (int)$programId;
        $newChairIdNo = trim((string)$newChairIdNo);
        if ($newChairIdNo === '') {
            $newChairIdNo = null; // treat empty as clear
        }
        if ($programId <= 0) {
            throw new \DomainException("Selected program does not exist.");
        }

        // Fetch the program's department_id (college department)
        $meta = $this->pdo->prepare("
            SELECT department_id
              FROM programs
             WHERE program_id = :pid
             LIMIT 1
        ");
        $meta->bindValue(':pid', $programId, PDO::PARAM_INT);
        $meta->execute();
        $dep = $meta->fetchColumn();
        if ($dep === false) {
            throw new \DomainException("Selected program does not exist.");
        }
        $departmentId = (int)$dep;

        $this->pdo->beginTransaction();
        try {
            // Current chair of this program (if any)
            $curStmt = $this->pdo->prepare("
                SELECT chair_id
                  FROM program_chairs
                 WHERE program_id = :pid
                 LIMIT 1
            ");
            $curStmt->execute([':pid' => $programId]);
            $currentChairIdNo = $curStmt->fetchColumn();
            $currentChairIdNo = ($currentChairIdNo !== false) ? (string)$currentChairIdNo : null;

            // Clearing?
            if ($newChairIdNo === null) {
                // Remove mapping
                $del = $this->pdo->prepare("DELETE FROM program_chairs WHERE program_id = :pid");
                $del->execute([':pid' => $programId]);

                $this->pdo->commit();
                return;
            }

            // Assigning: ensure target user has the Chair role
            if (!$this->hasChairRole($newChairIdNo, $rid)) {
                throw new \DomainException("Selected user does not have the Chair role.");
            }

            // A chair can only map to one program → free the new chair elsewhere
            $free = $this->pdo->prepare("
                DELETE FROM program_chairs
                 WHERE chair_id = :id AND program_id <> :pid
            ");
            $free->execute([':id' => $newChairIdNo, ':pid' => $programId]);

            // Sync user_roles.department_id for the Chair role (to program's department)
            $upd = $this->pdo->prepare("
                UPDATE user_roles
                   SET department_id = :did
                 WHERE id_no = :id AND role_id = :rid
            ");
            $upd->execute([':did' => $departmentId, ':id' => $newChairIdNo, ':rid' => $rid]);

            // Upsert mapping in program_chairs (per-driver)
            if ($this->driver === 'pgsql') {
                $upsert = $this->pdo->prepare("
                    INSERT INTO program_chairs (program_id, chair_id)
                    VALUES (:pid, :id)
                    ON CONFLICT (program_id) DO UPDATE
                      SET chair_id = EXCLUDED.chair_id
                ");
                $upsert->execute([':pid' => $programId, ':id' => $newChairIdNo]);
            } elseif ($this->driver === 'mysql') {
                // Requires UNIQUE index on program_id in program_chairs
                $upsert = $this->pdo->prepare("
                    INSERT INTO program_chairs (program_id, chair_id)
                    VALUES (:pid, :id)
                    ON DUPLICATE KEY UPDATE chair_id = VALUES(chair_id)
                ");
                $upsert->execute([':pid' => $programId, ':id' => $newChairIdNo]);
            } elseif ($this->driver === 'sqlsrv') {
                // MERGE pattern for SQL Server
                $sql = "
                    MERGE program_chairs AS target
                    USING (SELECT :pid AS program_id, :id AS chair_id) AS src
                    ON target.program_id = src.program_id
                    WHEN MATCHED THEN
                      UPDATE SET chair_id = src.chair_id
                    WHEN NOT MATCHED THEN
                      INSERT (program_id, chair_id) VALUES (src.program_id, src.chair_id);
                ";
                $upsert = $this->pdo->prepare($sql);
                $upsert->execute([':pid' => $programId, ':id' => $newChairIdNo]);
            } else {
                // Fallback: try delete+insert
                $del = $this->pdo->prepare("DELETE FROM program_chairs WHERE program_id = :pid");
                $del->execute([':pid' => $programId]);
                $ins = $this->pdo->prepare("
                    INSERT INTO program_chairs (program_id, chair_id) VALUES (:pid, :id)
                ");
                $ins->execute([':pid' => $programId, ':id' => $newChairIdNo]);
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

    /**
     * Clear college_deans mapping for a user (if any).
     * Safe to call when the user has lost the Dean role.
     */
    public function clearCollegeDeanForUser(string $idNo): void
    {
        $idNo = trim((string)$idNo);
        if ($idNo === '') return;

        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare("DELETE FROM college_deans WHERE dean_id = :id");
            $del->execute([':id' => $idNo]);

            // Also clear any user_roles.department_id entries for Dean role (best-effort).
            // If the schema doesn't have a matching row, UPDATE will simply affect 0 rows.
            $rid = $this->deanRoleId();
            if ($rid !== null) {
                $upd = $this->pdo->prepare("
                    UPDATE user_roles
                    SET department_id = NULL
                    WHERE id_no = :id AND role_id = :rid
                ");
                $upd->execute([':id' => $idNo, ':rid' => $rid]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Clear program_chairs mapping for a user (if any).
     * Safe to call when the user has lost the Chair role.
     */
    public function clearProgramChairForUser(string $idNo): void
    {
        $idNo = trim((string)$idNo);
        if ($idNo === '') return;

        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare("DELETE FROM program_chairs WHERE chair_id = :id");
            $del->execute([':id' => $idNo]);

            // Optionally clear user_roles.department_id for Chair role (best-effort).
            $rid = $this->chairRoleId();
            if ($rid !== null) {
                $upd = $this->pdo->prepare("
                    UPDATE user_roles
                    SET department_id = NULL
                    WHERE id_no = :id AND role_id = :rid
                ");
                $upd->execute([':id' => $idNo, ':rid' => $rid]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
}
