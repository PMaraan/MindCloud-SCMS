<?php
// app/Services/AssignmentsService.php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * AssignmentsService (Departments-unified)
 *
 * Responsibilities:
 *  - Manage dean <-> department assignment consistently.
 *  - Enforce: only rows with departments.is_college = TRUE can have a dean.
 *  - Keep user_roles.department_id in sync for the Dean role.
 *
 * Notes:
 *  - This version replaces `college_*` usage with `department_*`.
 *  - A backwards-compat method setCollegeDean(...) calls setDepartmentDean(...).
 *  - No DB triggers are required; all integrity is enforced here.
 */
final class AssignmentsService
{
    private PDO $pdo;

    public function __construct(StorageInterface $db)
    {
        $this->pdo = $db->getConnection();
    }

    /**
     * Locate the role_id for role_name = 'dean' (case-insensitive).
     * Returns null if the role does not exist.
     */
    private function deanRoleId(): ?int
    {
        $stmt = $this->pdo->query("SELECT role_id FROM roles WHERE LOWER(role_name) = 'dean' LIMIT 1");
        $rid = $stmt->fetchColumn();
        return $rid !== false ? (int)$rid : null;
    }

    /**
     * Check if a user already has the Dean role.
     */
    private function hasDeanRole(string $idNo, int $rid): bool
    {
        $q = $this->pdo->prepare(
            "SELECT 1 FROM user_roles WHERE id_no = :id AND role_id = :rid LIMIT 1"
        );
        $q->execute([':id' => $idNo, ':rid' => $rid]);
        return (bool)$q->fetchColumn();
    }

    /**
     * Check if a department exists and is marked as a college (departments.is_college = TRUE).
     * Throws \DomainException with a user-friendly message if not valid for dean assignment.
     */
    private function assertDepartmentIsCollege(int $departmentId): void
    {
        $st = $this->pdo->prepare(
            "SELECT is_college FROM departments WHERE department_id = :id"
        );
        $st->bindValue(':id', $departmentId, PDO::PARAM_INT);
        $st->execute();
        $val = $st->fetchColumn();

        if ($val === false) {
            throw new \DomainException('Selected department does not exist.');
        }

        // Normalize boolean from PG/MySQL
        $isCollege = ($val === true || $val === 1 || $val === '1' || $val === 't');
        if (!$isCollege) {
            throw new \DomainException('Deans can only be assigned to Colleges.');
        }
    }

    /**
     * Assign a dean to a department (which MUST be a college), or clear if $newDeanIdNo is null/''.
     *  - Enforces: only users who ALREADY have the Dean role can be assigned.
     *  - Keeps user_roles.department_id consistent (for the Dean role row).
     *  - Guarantees one dean per department, and one department per dean.
     */
    public function setDepartmentDean(int $departmentId, ?string $newDeanIdNo): void
    {
        $rid = $this->deanRoleId();
        if ($rid === null) {
            throw new \RuntimeException("Dean role not found.");
        }

        // Normalize inputs
        $departmentId = (int)$departmentId;
        $newDeanIdNo  = trim((string)$newDeanIdNo);
        if ($newDeanIdNo === '') {
            $newDeanIdNo = null;
        }

        if ($departmentId <= 0) {
            throw new \DomainException("Selected department does not exist.");
        }

        // --- Ensure department exists in *departments* ---
        $ex = $this->pdo->prepare("SELECT 1 FROM public.departments WHERE department_id = :id LIMIT 1");
        $ex->bindValue(':id', $departmentId, \PDO::PARAM_INT);
        $ex->execute();
        if (!$ex->fetchColumn()) {
            throw new \DomainException("Selected department does not exist.");
        }

        $this->pdo->beginTransaction();
        try {
            // Current dean of this department (mapping table is now department_deans)
            $curStmt = $this->pdo->prepare("
                SELECT dean_id
                FROM public.department_deans
                WHERE department_id = :did
                LIMIT 1
            ");
            $curStmt->execute([':did' => $departmentId]);
            $currentDeanIdNo = $curStmt->fetchColumn();
            $currentDeanIdNo = ($currentDeanIdNo !== false) ? (string)$currentDeanIdNo : null;

            // If clearing dean (newDeanIdNo is null)
            if ($newDeanIdNo === null) {
                // Remove mapping
                $del = $this->pdo->prepare("DELETE FROM public.department_deans WHERE department_id = :did");
                $del->execute([':did' => $departmentId]);

                // Also clear old dean’s department_id (column name kept for compatibility)
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

            // Enforce: only users with Dean role can be assigned
            if (!$this->hasDeanRole($newDeanIdNo, $rid)) {
                throw new \DomainException("Selected user does not have the Dean role.");
            }

            // If dean changed, clear old dean’s department_id
            if ($currentDeanIdNo !== null && $currentDeanIdNo !== $newDeanIdNo) {
                $c = $this->pdo->prepare("
                    UPDATE public.user_roles
                        SET department_id = NULL
                    WHERE id_no = :id AND role_id = :rid
                ");
                $c->execute([':id' => $currentDeanIdNo, ':rid' => $rid]);
            }

            // A dean can be mapped to only one department: free the new dean elsewhere
            $free = $this->pdo->prepare("
                DELETE FROM public.department_deans
                    WHERE dean_id = :id AND department_id <> :did
            ");
            $free->execute([':id' => $newDeanIdNo, ':did' => $departmentId]);

            // Ensure user_roles.department_id reflects the assignment (column name retained)
            $upd = $this->pdo->prepare("
                UPDATE public.user_roles
                    SET department_id = :did
                WHERE id_no = :id AND role_id = :rid
            ");
            $upd->execute([':did' => $departmentId, ':id' => $newDeanIdNo, ':rid' => $rid]);

            // Upsert mapping in department_deans
            $upsert = $this->pdo->prepare("
                INSERT INTO public.department_deans (department_id, dean_id)
                VALUES (:did, :id)
                ON CONFLICT (department_id) DO UPDATE
                    SET dean_id = EXCLUDED.dean_id
            ");
            $upsert->execute([':did' => $departmentId, ':id' => $newDeanIdNo]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * BACKWARD COMPAT: old signature in “Colleges” code paths.
     * Internally redirects to setDepartmentDean().
     */
    public function setCollegeDean(int $collegeId, ?string $newDeanIdNo): void
    {
        // In the unified schema, department_id == department_id for college rows.
        $this->setDepartmentDean($collegeId, $newDeanIdNo);
    }

    /**
     * Call from Accounts when roles change.
     * - If user is no longer Dean, clear mapping and user_roles.department_id for Dean role.
     * - If user becomes Dean and $newDepartmentId provided, assign them.
     *
     * $newRoleNames: array of strings (role_name labels after the change).
     * $newDepartmentId: department_id to assign for Dean role (nullable).
     */
    public function onUserRolesChanged(string $idNo, array $newRoleNames, ?int $newDepartmentId): void
    {
        $rid = $this->deanRoleId();
        if ($rid === null) return;

        $hasDean = false;
        foreach ($newRoleNames as $r) {
            if (mb_strtolower($r) === 'dean') { $hasDean = true; break; }
        }

        $this->pdo->beginTransaction();
        try {
            if (!$hasDean) {
                // User lost the Dean role: clear mapping and clear department_id for Dean role row
                $d = $this->pdo->prepare("DELETE FROM department_deans WHERE dean_id = :id");
                $d->execute([':id' => $idNo]);

                $u = $this->pdo->prepare("
                    UPDATE user_roles
                     SET department_id = NULL
                     WHERE id_no = :id AND role_id = :rid
                ");
                $u->execute([':id' => $idNo, ':rid' => $rid]);

                $this->pdo->commit();
                return;
            }

            // If became/kept Dean and a department was chosen, assign; else just ensure no stale mapping
            if ($newDepartmentId !== null && $newDepartmentId > 0) {
                $this->pdo->commit();                 // end txn before nested call
                $this->setDepartmentDean($newDepartmentId, $idNo);
                return;
            } else {
                // If no department selected, ensure no stale mapping remains
                $d = $this->pdo->prepare("DELETE FROM department_deans WHERE dean_id = :id");
                $d->execute([':id' => $idNo]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Call from Accounts when a (Dean) user's department changes via UI.
     * If user is a Dean and a department was set, assign. If cleared, unassign.
     */
    public function onUserDepartmentChanged(string $idNo, ?int $newDepartmentId): void
    {
        $rid = $this->deanRoleId();
        if ($rid === null) return;

        // Is the user a Dean?
        $q = $this->pdo->prepare(
            "SELECT 1 FROM user_roles WHERE id_no = :id AND role_id = :rid LIMIT 1"
        );
        $q->execute([':id' => $idNo, ':rid' => $rid]);
        $isDean = (bool)$q->fetchColumn();
        if (!$isDean) return;

        if ($newDepartmentId !== null && $newDepartmentId > 0) {
            $this->setDepartmentDean($newDepartmentId, $idNo);
        } else {
            // Clearing department for a dean => remove mapping + clear user_roles.department_id (Dean role)
            $this->pdo->beginTransaction();
            try {
                $d = $this->pdo->prepare("DELETE FROM department_deans WHERE dean_id = :id");
                $d->execute([':id' => $idNo]);

                $u = $this->pdo->prepare(
                    "UPDATE user_roles
                     SET department_id = NULL
                     WHERE id_no = :id AND role_id = :rid"
                );
                $u->execute([':id' => $idNo, ':rid' => $rid]);

                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }
    }
}
