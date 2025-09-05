<?php
// app/Services/AssignmentsService.php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\StorageInterface;
use PDO;

final class AssignmentsService
{
    private PDO $pdo;

    public function __construct(StorageInterface $db)
    {
        $this->pdo = $db->getConnection();
    }

    private function deanRoleId(): ?int
    {
        $stmt = $this->pdo->query("SELECT role_id FROM roles WHERE LOWER(role_name) = 'dean' LIMIT 1");
        $rid = $stmt->fetchColumn();
        return $rid !== false ? (int)$rid : null;
    }

    private function hasDeanRole(string $idNo, int $rid): bool
    {
        $q = $this->pdo->prepare("SELECT 1 FROM user_roles WHERE id_no = :id AND role_id = :rid LIMIT 1");
        $q->execute([':id' => $idNo, ':rid' => $rid]);
        return (bool)$q->fetchColumn();
    }

    /**
     * Assign a dean to a college (or clear if $newDeanIdNo is null/'').
     * Enforces: only users who ALREADY have the Dean role can be assigned.
     * Keeps user_roles.college_id consistent.
     */
    public function setCollegeDean(int $collegeId, ?string $newDeanIdNo): void
    {
        $rid = $this->deanRoleId();
        if ($rid === null) {
            throw new \RuntimeException("Dean role not found.");
        }

        // Normalize input
        $newDeanIdNo = trim((string)$newDeanIdNo);
        if ($newDeanIdNo === '') $newDeanIdNo = null;

        $this->pdo->beginTransaction();
        try {
            // Current dean of this college
            $curStmt = $this->pdo->prepare("SELECT dean_id FROM college_deans WHERE college_id = :cid");
            $curStmt->execute([':cid' => $collegeId]);
            $currentDeanIdNo = $curStmt->fetchColumn();
            $currentDeanIdNo = ($currentDeanIdNo !== false) ? (string)$currentDeanIdNo : null;

            // If clearing dean
            if ($newDeanIdNo === null) {
                // Remove mapping for this college
                $del = $this->pdo->prepare("DELETE FROM college_deans WHERE college_id = :cid");
                $del->execute([':cid' => $collegeId]);

                // Also clear old dean’s college_id in user_roles (for Dean role)
                if ($currentDeanIdNo !== null) {
                    $upd = $this->pdo->prepare("UPDATE user_roles SET college_id = NULL WHERE id_no = :id AND role_id = :rid");
                    $upd->execute([':id' => $currentDeanIdNo, ':rid' => $rid]);
                }

                $this->pdo->commit();
                return;
            }

            // Enforce: only users with Dean role can be assigned as dean
            if (!$this->hasDeanRole($newDeanIdNo, $rid)) {
                throw new \DomainException("Selected user does not have the Dean role.");
            }

            // If dean changed, clear old dean’s college_id
            if ($currentDeanIdNo !== null && $currentDeanIdNo !== $newDeanIdNo) {
                $c = $this->pdo->prepare("UPDATE user_roles SET college_id = NULL WHERE id_no = :id AND role_id = :rid");
                $c->execute([':id' => $currentDeanIdNo, ':rid' => $rid]);
            }

            // A dean can be mapped to only one college: free the new dean elsewhere
            $free = $this->pdo->prepare("DELETE FROM college_deans WHERE dean_id = :id AND college_id <> :cid");
            $free->execute([':id' => $newDeanIdNo, ':cid' => $collegeId]);

            // Ensure user_roles.college_id reflects the assignment for the Dean role
            $upd = $this->pdo->prepare("UPDATE user_roles SET college_id = :cid WHERE id_no = :id AND role_id = :rid");
            $upd->execute([':cid' => $collegeId, ':id' => $newDeanIdNo, ':rid' => $rid]);

            // Upsert mapping in college_deans
            $sql = "INSERT INTO college_deans (college_id, dean_id)
                    VALUES (:cid, :id)
                    ON CONFLICT (college_id) DO UPDATE
                    SET dean_id = EXCLUDED.dean_id";
            $x = $this->pdo->prepare($sql);
            $x->execute([':cid' => $collegeId, ':id' => $newDeanIdNo]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Call from Accounts when roles change.
     * - If user is no longer Dean, clear mapping and user_roles.college_id for Dean role.
     * - If user becomes Dean and $newCollegeId provided, assign them.
     */
    public function onUserRolesChanged(string $idNo, array $newRoleNames, ?int $newCollegeId): void
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
                $d = $this->pdo->prepare("DELETE FROM college_deans WHERE dean_id = :id");
                $d->execute([':id' => $idNo]);

                $u = $this->pdo->prepare("UPDATE user_roles SET college_id = NULL WHERE id_no = :id AND role_id = :rid");
                $u->execute([':id' => $idNo, ':rid' => $rid]);
                $this->pdo->commit();
                return;
            }

            // If became Dean and a college was chosen, assign; else just ensure no stale mapping
            if ($newCollegeId !== null && $newCollegeId > 0) {
                $this->pdo->commit();                 // end txn before nested call
                $this->setCollegeDean($newCollegeId, $idNo);
                return;
            } else {
                $d = $this->pdo->prepare("DELETE FROM college_deans WHERE dean_id = :id");
                $d->execute([':id' => $idNo]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Call from Accounts when a (Dean) user's college changes.
     */
    public function onUserCollegeChanged(string $idNo, ?int $newCollegeId): void
    {
        $rid = $this->deanRoleId();
        if ($rid === null) return;

        // Is the user a Dean?
        $q = $this->pdo->prepare("SELECT 1 FROM user_roles WHERE id_no = :id AND role_id = :rid LIMIT 1");
        $q->execute([':id' => $idNo, ':rid' => $rid]);
        $isDean = (bool)$q->fetchColumn();
        if (!$isDean) return;

        if ($newCollegeId !== null && $newCollegeId > 0) {
            $this->setCollegeDean($newCollegeId, $idNo);
        } else {
            $this->pdo->beginTransaction();
            try {
                $d = $this->pdo->prepare("DELETE FROM college_deans WHERE dean_id = :id");
                $d->execute([':id' => $idNo]);

                $u = $this->pdo->prepare("UPDATE user_roles SET college_id = NULL WHERE id_no = :id AND role_id = :rid");
                $u->execute([':id' => $idNo, ':rid' => $rid]);

                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }
    }
}
