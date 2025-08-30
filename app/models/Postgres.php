<?php
// /app/Models/Postgres.php
namespace App\Models;
use App\Interfaces\StorageInterface;

require_once __DIR__ . '/../../config/config.php';
//require_once __DIR__ . '/../interfaces/StorageInterface.php';

class Postgres implements StorageInterface{
    private \PDO $pdo;

    public function __construct() {
        $dsn = "pgsql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME;

        try {
            $this->pdo = new \PDO($dsn, DB_USER, DB_PASS, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
        } catch (\PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): \PDO {
        return $this->pdo;
    }

    public function checkPermission($userId, $permissionName): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE ur.id_no = ? AND p.permission_name = ?
            LIMIT 1
        ");

        $stmt->execute([$userId, $permissionName]);

        return $stmt->fetchColumn() !== false;
    }

    public function getPermissionGroupsByUser(string $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.category
            FROM user_roles ur
            JOIN role_permissions rp USING (role_id)
            JOIN permissions p USING (permission_id)
            WHERE ur.id_no = ?
        ");
        $stmt->execute([$userId]);

        // Returns a flat array of strings like: ['Syllabus', 'Roles', ...]
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
?>