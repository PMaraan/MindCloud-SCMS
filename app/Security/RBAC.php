<?php
// /app/Security/RBAC.php
declare(strict_types=1);

namespace App\Security;

use App\Interfaces\StorageInterface;
use PDO;

final class RBAC
{
    private StorageInterface $db;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Load all permission names for a user (no session caching for MVP).
     * Returns a set-like array: ['AccountViewing' => true, 'AccountCreation' => true, ...]
     */
    public function load(string $userId): array
    {
        $pdo = $this->db->getConnection();
        $sql = "
            SELECT p.permission_name
              FROM users u
              JOIN user_roles       ur ON u.id_no = ur.id_no
              JOIN role_permissions rp ON ur.role_id = rp.role_id
              JOIN permissions      p  ON rp.permission_id = p.permission_id
             WHERE u.id_no = :uid
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);

        $perms = [];
        while ($name = $stmt->fetchColumn()) {
            if ($name !== false && $name !== null && $name !== '') {
                $perms[(string)$name] = true;
            }
        }
        return $perms;
    }

    /**
     * Quick check helper (loads from DB each time for MVP).
     */
    public function has(string $userId, string $permissionName): bool
    {
        $perms = $this->load($userId);
        return isset($perms[$permissionName]);
    }

    /**
     * Enforce a permission or 403 immediately.
     */
    public function require(string $userId, string $permissionName): void
    {
        if (!$this->has($userId, $permissionName)) {
            http_response_code(403);
            echo '<h1>403 Forbidden</h1>';
            exit;
        }
    }
}
