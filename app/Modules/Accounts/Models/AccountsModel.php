<?php
// /app/Modules/Accounts/Models/AccountsModel.php
declare(strict_types=1);

namespace App\Modules\Accounts\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class AccountsModel {
    private PDO $pdo;
    private string $driver;

    public function __construct(StorageInterface $db) {
        // $db is your PDO instance from DatabaseFactory
        $this->pdo = $db->getConnection();
        $this->driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Return a SQL snippet for LIMIT/OFFSET depending on the driver.
     * Both $limit and $offset are clamped to sensible values.
     */
    private function limitOffsetClause(int $limit, int $offset): string {
        // Both values already clamped below; safe to inline.
        switch ($this->driver) {
            case 'pgsql':
            case 'mysql':
                return "LIMIT $limit OFFSET $offset";
            case 'sqlsrv': // SQL Server 2012+
                return "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
            case 'oci':    // Oracle 12c+
                return "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
            default:       // Best-effort default
                return "LIMIT $limit OFFSET $offset";
        }
    }

    /**
     * Create a user with role & (optional) college. Returns bool.
     * Expects keys: id_no, fname, mname|null, lname, email, password(hash), role_id, department_id|null
     */
    public function createUser(array $data): bool {
        try {
            $this->pdo->beginTransaction();

            // Ensure email/id_no uniqueness at DB level too (unique indexes recommended)
            $stmt = $this->pdo->prepare("
                INSERT INTO users (id_no, fname, mname, lname, email, password)
                VALUES (:id_no, :fname, :mname, :lname, :email, :password)
            ");
            $stmt->execute([
                ':id_no'    => $data['id_no'],
                ':fname'    => $data['fname'],
                ':mname'    => $data['mname'],
                ':lname'    => $data['lname'],
                ':email'    => $data['email'],
                ':password' => $data['password'],
            ]);

            // user_roles
            $stmt2 = $this->pdo->prepare("
                INSERT INTO user_roles (id_no, role_id, department_id)
                VALUES (:id_no, :role_id, :department_id)
            ");
            $stmt2->execute([
                ':id_no'      => $data['id_no'],
                ':role_id'    => $data['role_id'],
                ':department_id' => $data['department_id'],
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Optional: logger() if you added it
            // \App\Helpers\logger()->error('Create user failed', ['err' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Return a list of colleges (department_id, short_name, college_name).
     */
    public function getAllColleges(): array {
        $stmt = $this->pdo->query('SELECT department_id, short_name, college_name FROM colleges ORDER BY short_name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Return a list of users (joined with role & college), optional search, limit/offset.
     * @param string|null $q  lowercased search term or null
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsers(?string $q = null, int $limit = 100, int $offset = 0): array {
        $where  = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            $where .= "
            AND (
                    TRIM(u.id_no)         LIKE :q
                OR  LOWER(u.fname)        LIKE :q
                OR  LOWER(u.mname)        LIKE :q
                OR  LOWER(u.lname)        LIKE :q
                OR  LOWER(u.email)        LIKE :q
                OR  LOWER(r.role_name)    LIKE :q
                OR  LOWER(c.short_name)   LIKE :q
            )
            ";
            $params[':q'] = '%' . $q . '%';
        }

        $sql = "
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                COALESCE(r.role_name, '')  AS role_name,
                COALESCE(c.short_name, '') AS college_short_name
            FROM users u
        LEFT JOIN user_roles ur ON u.id_no = ur.id_no
        LEFT JOIN roles r       ON ur.role_id = r.role_id
        LEFT JOIN departments d ON ur.department_id = d.department_id
            $where
        ORDER BY u.lname ASC, u.fname ASC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Return a list of roles (role_id, role_name).
     */
    public function getAllRoles(): array {
        $stmt = $this->pdo->query('SELECT role_id, role_name FROM roles ORDER BY role_name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Fetch a single user by ID number.
     */
    public function getUserById(string $idNo): ?array {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                r.role_name,
                c.short_name AS college_short_name
            FROM users u
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN departments d ON ur.department_id = d.department_id
            WHERE u.id_no = :id_no
            LIMIT 1
        ");
        $stmt->execute([':id_no' => $idNo]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Fetch users with optional search, returns rows and total count.
     * @return array{rows: array<int,array<string,mixed>>, total: int}
     */
    public function getUsersPage(?string $q, int $limit, int $offset): array {
        // Defense-in-depth
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        // Build WHERE pieces portably (LOWER() LIKE) and trim id_no (CHAR(13))
        $where = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            $where .= "
            AND (
                TRIM(u.id_no) LIKE :q
                OR LOWER(u.fname)      LIKE :q
                OR LOWER(u.mname)      LIKE :q
                OR LOWER(u.lname)      LIKE :q
                OR LOWER(u.email)      LIKE :q
                OR LOWER(r.role_name)  LIKE :q
                OR LOWER(c.short_name) LIKE :q
            )";
            $params[':q'] = '%' . $q . '%'; // $q should already be lowercased by controller
        }

        // Dev logging (optional)
        if (defined('APP_ENV') && APP_ENV === 'dev') {
            $probe = $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            error_log('Probe users count = ' . (int)$probe);
        }

        // ---- 1) Total count (respecting search) ----
        $stmtCount = $this->pdo->prepare("
            SELECT COUNT(*) AS total
            FROM users u
            LEFT JOIN user_roles ur ON u.id_no = ur.id_no
            LEFT JOIN roles r       ON ur.role_id = r.role_id
            LEFT JOIN departments d ON ur.department_id = d.department_id
            $where
        ");
        foreach ($params as $k => $v) $stmtCount->bindValue($k, $v);
        $stmtCount->execute();
        $total = (int)$stmtCount->fetchColumn();

        // ---- 2) Page rows ----
        $pageClause = $this->limitOffsetClause($limit, $offset); // Build page clause for pagination
        $stmtUserList = $this->pdo->prepare("
            SELECT
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                ur.role_id,
                ur.department_id,
                COALESCE(r.role_name, '')  AS role_name,
                COALESCE(c.short_name, '') AS college_short_name
            FROM users u
            LEFT JOIN user_roles ur ON u.id_no = ur.id_no
            LEFT JOIN roles r       ON ur.role_id = r.role_id
            LEFT JOIN departments d ON ur.department_id = d.department_id
            $where
            ORDER BY u.lname ASC, u.fname ASC
            $pageClause
        ");
        foreach ($params as $k => $v) $stmtUserList->bindValue($k, $v);
        $stmtUserList->execute();

        $rows = $stmtUserList->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Dev logging (optional)
        if (defined('APP_ENV') && APP_ENV === 'dev') {
            error_log("accounts.page rows=" . count($rows) . " total={$total} limit={$limit} offset={$offset}");
        }

        // Return the result
        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Update a user (for Edit modal).
     */
    public function updateUser(array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET fname = :fname,
                mname = :mname,
                lname = :lname,
                email = :email
            WHERE id_no = :id_no
        ");
        return $stmt->execute([
            ':fname' => $data['fname'],
            ':mname' => $data['mname'],
            ':lname' => $data['lname'],
            ':email' => $data['email'],
            ':id_no' => $data['id_no']
        ]);
    }

    public function updateUserWithRoleCollege(array $data): bool {
        // expects: id_no, fname, mname|null, lname, email, role_id(int), department_id(int|null)
        try {
            $this->pdo->beginTransaction();

            // 1) Update users
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET fname = :fname,
                    mname = :mname,
                    lname = :lname,
                    email = :email
                WHERE id_no = :id_no
            ");
            $stmt->execute([
                ':fname' => $data['fname'],
                ':mname' => $data['mname'],
                ':lname' => $data['lname'],
                ':email' => $data['email'],
                ':id_no' => $data['id_no'],
            ]);

            // 2) Enforce single role per user (MVP): replace mapping
            $del = $this->pdo->prepare("DELETE FROM user_roles WHERE id_no = :id_no");
            $del->execute([':id_no' => $data['id_no']]);

            $ins = $this->pdo->prepare("
                INSERT INTO user_roles (id_no, role_id, department_id)
                VALUES (:id_no, :role_id, :department_id)
            ");
            $ins->execute([
                ':id_no'      => $data['id_no'],
                ':role_id'    => (int)$data['role_id'],
                ':department_id' => $data['department_id'] === null ? null : (int)$data['department_id'],
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // TEMP: log so you can see the reason while in dev
            error_log('updateUserWithRoleCollege failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser(string $idNo): bool {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id_no = :id_no");
        return $stmt->execute([':id_no' => $idNo]);
    }
}
