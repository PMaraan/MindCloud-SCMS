<?php
// root/app/models/UserModel.php
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

        
        /*
        // Get role & college in one query to reduce calls
        $stmtUserDetails = $this->pdo->prepare("
            SELECT 
                r.role_id, 
                r.role_name, 
                c.short_name AS college_short, 
                c.college_name
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN colleges c ON ur.college_id = c.college_id
            WHERE ur.id_no = ?
            LIMIT 1
        ");
        $stmtUserDetails->execute([$user['id_no']]);
        $userDetails = $stmtUserDetails->fetch(\PDO::FETCH_ASSOC);

        return array_merge($user, [
            'role_id'       => $userDetails['role_id'] ?? null,
            'role_name'     => $userDetails['role_name'] ?? null,
            'college_short' => $userDetails['college_short'] ?? null,
            'college_name'  => $userDetails['college_name'] ?? null
        ]);
        */
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
    public function getUserProfile(int|string $userId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                r.role_id,
                r.role_name,
                c.short_name AS college_short_name,
                c.college_name
            FROM users u
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN colleges c ON ur.college_id = c.college_id
            WHERE u.id_no = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
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
