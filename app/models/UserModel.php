<?php
// /app/Models/UserModel.php
declare(strict_types=1);

namespace App\Models;

use App\Interfaces\StorageInterface;;
use App\Helpers\PasswordHelper;
use PDO;

final class UserModel {
    /** @var \PDO */
    private PDO $pdo;

    public function __construct(StorageInterface $db) {
        // StorageInterface::getConnection() must return a PDO
        $this->pdo = $db->getConnection();
    }

    /**
     * Authenticate user by email & password.
     * Returns full user details with role & college if valid, false otherwise.
     * Automatically rehashes password if needed.
     */
    public function authenticate(string $usernameOrEmail, string $password): array|false {
        // If you have a username column:
        /*
        $stmt = $this->pdo->prepare(
            'SELECT * 
            FROM users 
            WHERE email = ? OR username = ? 
            LIMIT 1'
        );
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        // */

        $stmt = $this->pdo->prepare('
            SELECT * 
            FROM users 
            WHERE email = ?
            LIMIT 1
        ');
        $stmt->execute([$usernameOrEmail]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // Verify + rehash if algo changed
        if (!PasswordHelper::verify(
            $password,
            $user['password'],
            function (string $newHash) use ($user): void {
                // Rehash callback
                $this->rehashPassword($user['id_no'], $newHash);
            }
        )) {
            return false;
        }

        return $user;
        
    }

    /**
     * Get role permissions (list of permission keys) for a role.
     */
    public function getRolePermissions(int|string|null $role_id): array {
        if (empty($role_id)) return [];

        $stmt = $this->pdo->prepare("
            SELECT p.permission_key
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE rp.role_id = ?
        ");
        $stmt->execute([$role_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Get full user profile (joins role & college).
     */
    public function getUserProfile(int|string $userId): ?array
    {
        // A single row with the user's basic info + one role row + optional department
        // If you want "all roles", you can group_agg later; this mirrors your original LIMIT 1 approach.
        $sql = "
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                r.role_id,
                r.role_name,

                d.department_id,
                d.short_name       AS department_short_name,
                d.department_name  AS department_name,
                d.is_college       AS department_is_college,

                -- For compatibility with existing module vars:
                -- Treat 'college_*' as the same department when is_college = TRUE; else NULL.
                CASE WHEN d.is_college THEN d.department_id   ELSE NULL END AS college_id,
                CASE WHEN d.is_college THEN d.short_name      ELSE NULL END AS college_short_name,
                CASE WHEN d.is_college THEN d.department_name ELSE NULL END AS college_name,

                -- Program context for Chairs (if any single mapping exists)
                pc.program_id      AS program_id
            FROM users u
            JOIN user_roles ur ON ur.id_no = u.id_no
            JOIN roles r       ON r.role_id = ur.role_id
            LEFT JOIN departments d ON d.department_id = ur.department_id
            LEFT JOIN program_chairs pc ON pc.chair_id = u.id_no
            WHERE u.id_no = :id_no
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_no' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** List users who have the 'Dean' role (for dropdowns). */
    public function listUsersByRole(string $roleName): array
    {
        $sql = "
          SELECT u.id_no, u.fname, u.mname, u.lname
          FROM users u
          JOIN user_roles ur ON ur.id_no = u.id_no
          JOIN roles r       ON r.role_id = ur.role_id
          WHERE LOWER(r.role_name) = LOWER(:r)
          ORDER BY u.lname ASC, u.fname ASC
        ";
        $st = $this->pdo->prepare($sql);
        $st->bindValue(':r', $roleName);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Update password hash after login rehash.
     */
    public function rehashPassword(string $userId, string $newHash): bool {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id_no = ?
        ");
        return $stmt->execute([$newHash, $userId]);
    }

    /**
     * Set password manually (for admin resets or changes).
     */
    public function setPassword(string $userId, string $newPassword): bool {
        $hash = PasswordHelper::hash($newPassword);

        $stmt = $this->pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id_no = ?
        ");
        return $stmt->execute([$hash, $userId]);
    }
}
