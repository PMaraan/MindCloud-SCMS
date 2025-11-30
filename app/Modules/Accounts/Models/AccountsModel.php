<?php
// /app/Modules/Accounts/Models/AccountsModel.php
declare(strict_types=1);

namespace App\Modules\Accounts\Models;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * AccountsModel
 *
 * Backward-compat for the new departments schema:
 * - Accepts array keys `department_id` OR legacy `college_id`.
 * - Returns both `department_short_name` and alias `college_short_name` for existing views.
 * - Filters “colleges” as departments WHERE is_college = TRUE (driver-safe).
 */
final class AccountsModel {
    private PDO $pdo;
    private string $driver;

    public function __construct(StorageInterface $db) {
        $this->pdo = $db->getConnection();
        $this->driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /** Driver-safe boolean TRUE literal for WHERE clauses. */
    private function sqlBoolTrue(): string {
        switch ($this->driver) {
            case 'sqlsrv': return '1';
            case 'mysql':  return 'TRUE'; // also equals 1 in MySQL
            case 'pgsql':
            default:       return 'TRUE';
        }
    }

    /** Driver-safe boolean FALSE literal for WHERE clauses. */
    private function sqlBoolFalse(): string {
        switch ($this->driver) {
            case 'sqlsrv': return '0';
            case 'mysql':  return 'FALSE';
            case 'pgsql':
            default:       return 'FALSE';
        }
    }

    /**
     * Return a SQL snippet for LIMIT/OFFSET depending on the driver.
     * Both $limit and $offset are clamped to sensible values.
     */
    private function limitOffsetClause(int $limit, int $offset): string {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        switch ($this->driver) {
            case 'sqlsrv': // SQL Server 2012+
            case 'oci':    // Oracle 12c+
                return "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
            case 'mysql':
            case 'pgsql':
            default:
                return "LIMIT $limit OFFSET $offset";
        }
    }

    /** Map legacy keys -> new keys without breaking callers. */
    private function normalizeUserRolePayload(array $data): array {
        // accept either `department_id` or legacy `college_id`
        if (!array_key_exists('department_id', $data) && array_key_exists('college_id', $data)) {
            $data['department_id'] = $data['college_id']; // may be null
        }
        return $data;
    }

    /**
     * Create a user with role & (nullable) department. Returns bool.
     * Expects keys: id_no, fname, mname|null, lname, email, password(hash), role_id, department_id|null
     * Backward-compat: accepts legacy `college_id`.
     */
    public function createUser(array $data): bool {
        try {
            $this->pdo->beginTransaction();

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

            $data = $this->normalizeUserRolePayload($data);

            $stmt2 = $this->pdo->prepare("
                INSERT INTO user_roles (id_no, role_id, department_id)
                VALUES (:id_no, :role_id, :department_id)
            ");
            $stmt2->execute([
                ':id_no'         => $data['id_no'],
                ':role_id'       => (int)$data['role_id'],
                ':department_id' => $data['department_id'] === null ? null : (int)$data['department_id'],
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // error_log('createUser failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Return a list of colleges from departments (is_college = TRUE).
     * Columns: department_id, short_name, department_name
     */
    public function getAllColleges(): array {
        $true = $this->sqlBoolTrue();
        $sql = "SELECT department_id, short_name, department_name
                  FROM departments
                 WHERE is_college = $true
              ORDER BY short_name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Roles list. */
    public function getAllRoles(): array {
        $stmt = $this->pdo->query('SELECT role_id, role_name FROM roles ORDER BY role_name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Simple, non-paginated list (kept for completeness).
     * Still returns alias `college_short_name` for backward-compat.
     */
    public function getAllUsers(?string $q = null, int $limit = 100, int $offset = 0): array {
        $where  = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            $where .= "
              AND (
                    TRIM(u.id_no)      LIKE :q
                 OR LOWER(u.fname)     LIKE :q
                 OR LOWER(u.mname)     LIKE :q
                 OR LOWER(u.lname)     LIKE :q
                 OR LOWER(u.email)     LIKE :q
                 OR LOWER(r.role_name) LIKE :q
                 OR LOWER(d.short_name)LIKE :q
              )
            ";
            $params[':q'] = '%' . $q . '%';
        }

        $pageClause = $this->limitOffsetClause($limit, $offset);
        $sql = "
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                COALESCE(r.role_name, '')  AS role_name,
                COALESCE(d.short_name, '') AS department_short_name,
                COALESCE(d.short_name, '') AS college_short_name -- legacy alias for views
            FROM users u
       LEFT JOIN user_roles ur ON u.id_no = ur.id_no
       LEFT JOIN roles r       ON ur.role_id = r.role_id
       LEFT JOIN departments d ON ur.department_id = d.department_id
            $where
        ORDER BY u.lname ASC, u.fname ASC
            $pageClause
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** One user (with legacy alias). */
    public function getUserById(string $idNo): ?array {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                r.role_name,
                d.short_name AS department_short_name,
                d.short_name AS college_short_name -- legacy alias
            FROM users u
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r       ON ur.role_id = r.role_id
       LEFT JOIN departments d ON ur.department_id = d.department_id
           WHERE u.id_no = :id_no
           LIMIT 1
        ");
        $stmt->execute([':id_no' => $idNo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Paginated list + count.
     * Returns `department_short_name` AND `college_short_name` for backward-compat.
     *
     * @return array{rows: array<int,array<string,mixed>>, total: int}
     */
    public function getUsersPage(?string $q, int $limit, int $offset, string $status = 'active'): array {
        $limit  = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $where  = ' WHERE 1=1 ';
        $params = [];

        if ($q !== null && $q !== '') {
            $where .= "
              AND (
                    TRIM(u.id_no)      LIKE :q
                 OR LOWER(u.fname)     LIKE :q
                 OR LOWER(u.mname)     LIKE :q
                 OR LOWER(u.lname)     LIKE :q
                 OR LOWER(u.email)     LIKE :q
                 OR LOWER(r.role_name) LIKE :q
                 OR LOWER(d.short_name)LIKE :q
              )
            ";
            $params[':q'] = '%' . $q . '%';
        }

        // Status filter
        if ($status !== 'all') {
            $where .= " AND u.status = :status ";
            $params[':status'] = $status;
        }

        // total
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

        // page
        $pageClause = $this->limitOffsetClause($limit, $offset);
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
                COALESCE(d.short_name, '') AS department_short_name,
                COALESCE(d.short_name, '') AS college_short_name -- legacy alias for views
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

        $rows = $stmtUserList->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (defined('APP_ENV') && APP_ENV === 'dev') {
            error_log("accounts.page rows=" . count($rows) . " total={$total} limit={$limit} offset={$offset}");
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /** Basic user-only update. */
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

    /**
     * Update user + replace single role mapping (compatible with legacy code).
     * Accepts `department_id` or legacy `college_id`.
     */
    public function updateUserWithRoleCollege(array $data): bool {
        // keep legacy method name for now; internally normalize to department
        $data = $this->normalizeUserRolePayload($data);

        try {
            $this->pdo->beginTransaction();

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

            $del = $this->pdo->prepare("DELETE FROM user_roles WHERE id_no = :id_no");
            $del->execute([':id_no' => $data['id_no']]);

            $ins = $this->pdo->prepare("
                INSERT INTO user_roles (id_no, role_id, department_id)
                VALUES (:id_no, :role_id, :department_id)
            ");
            $ins->execute([
                ':id_no'         => $data['id_no'],
                ':role_id'       => (int)$data['role_id'],
                ':department_id' => $data['department_id'] === null ? null : (int)$data['department_id'],
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('updateUserWithRoleCollege failed: ' . $e->getMessage());
            return false;
        }
    }

    /** Delete a user. */
    public function deleteUser(string $idNo): bool {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id_no = :id_no");
        return $stmt->execute([':id_no' => $idNo]);
    }

    /**
     * Return departments.
     * - $onlyColleges = true  -> only departments where is_college = TRUE (colleges)
     * - $onlyColleges = false -> only departments where is_college = FALSE (non-colleges)
     */
    public function getDepartments(bool $onlyColleges): array {
        $true  = $this->sqlBoolTrue();
        $false = $this->sqlBoolFalse();

        $where = $onlyColleges ? "WHERE is_college = $true" : "WHERE is_college = $false";

        $sql = "
            SELECT department_id, short_name, department_name, is_college
            FROM departments
            $where
        ORDER BY short_name ASC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Find role_id by role_name (case-insensitive). Returns null if not found.
     */
    public function findRoleIdByName(string $name): ?int {
        $sql = "SELECT role_id FROM roles WHERE LOWER(role_name) = :n LIMIT 1";
        $st  = $this->pdo->prepare($sql);
        $st->execute([':n' => mb_strtolower(trim($name))]);
        $rid = $st->fetchColumn();
        return $rid !== false ? (int)$rid : null;
    }

    /**
     * Update the user and replace their single role mapping.
     * Accepts: id_no, fname, mname|null, lname, email, role_id(int), department_id(int|null)
     * This is the canonical method (no routing to legacy).
     */
    public function updateUserWithRoleDepartment(array $data): bool {
        // Normalize inputs (defense-in-depth)
        $idNo   = trim((string)($data['id_no'] ?? ''));
        $fname  = trim((string)($data['fname'] ?? ''));
        $mname  = array_key_exists('mname', $data) ? ($data['mname'] === '' ? null : (string)$data['mname']) : null;
        $lname  = trim((string)($data['lname'] ?? ''));
        $email  = trim((string)($data['email'] ?? ''));
        $roleId = (int)($data['role_id'] ?? 0);
        $deptId = isset($data['department_id']) && $data['department_id'] !== '' ? (int)$data['department_id'] : null;

        if ($idNo === '' || $fname === '' || $lname === '' || $email === '' || $roleId <= 0) {
            return false;
        }

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
                ':fname' => $fname,
                ':mname' => $mname,
                ':lname' => $lname,
                ':email' => $email,
                ':id_no' => $idNo,
            ]);

            // 2) Enforce single role per user (MVP): replace mapping
            $del = $this->pdo->prepare("DELETE FROM user_roles WHERE id_no = :id_no");
            $del->execute([':id_no' => $idNo]);

            $ins = $this->pdo->prepare("
                INSERT INTO user_roles (id_no, role_id, department_id)
                VALUES (:id_no, :role_id, :department_id)
            ");
            $ins->execute([
                ':id_no'         => $idNo,
                ':role_id'       => $roleId,
                ':department_id' => $deptId,
            ]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('updateUserWithRoleDepartment failed: ' . $e->getMessage());
            return false;
        }
    }
}
