<?php
// root/app/models/AccountsModel.php
declare(strict_types=1);

namespace App\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class AccountsModel {
    private PDO $pdo;

    public function __construct(StorageInterface $db) {
        // $db is your PDO instance from DatabaseFactory
        $this->pdo = $db->getConnection();
    }

    /**
     * Create a user with role & (optional) college. Returns bool.
     * Expects keys: id_no, fname, mname|null, lname, email, password(hash), role_id, college_id|null
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
                INSERT INTO user_roles (id_no, role_id, college_id)
                VALUES (:id_no, :role_id, :college_id)
            ");
            $stmt2->execute([
                ':id_no'      => $data['id_no'],
                ':role_id'    => $data['role_id'],
                ':college_id' => $data['college_id'],
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
     * Return a list of colleges (college_id, short_name, college_name).
     */
    public function getAllColleges(): array {
        $stmt = $this->pdo->query('SELECT college_id, short_name, college_name FROM colleges ORDER BY short_name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Return a list of users (joined with role & college). Optional search.
     * @param string|null $q
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsers(?string $q = null, int $limit = 100, int $offset = 0): array {
        $sql = "
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                r.role_name,
                c.short_name AS college_short_name
            FROM users u
            LEFT JOIN user_roles ur ON u.id_no = ur.id_no
            LEFT JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN colleges c ON ur.college_id = c.college_id
        ";

        // Add search filter if provided
        $params = [];
        if ($q !== null && $q !== '') {
            $sql .= " WHERE 
                        u.id_no ILIKE :q OR
                        u.fname ILIKE :q OR
                        u.mname ILIKE :q OR
                        u.lname ILIKE :q OR
                        u.email ILIKE :q OR
                        r.role_name ILIKE :q OR
                        c.short_name ILIKE :q";
            $params[':q'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY u.lname ASC, u.fname ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        // bind scalar params
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
            LEFT JOIN colleges c ON ur.college_id = c.college_id
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
        // ---- 1) Total count (respecting search) ----
        $countSql = "
            SELECT COUNT(*) AS total
              FROM users u
            LEFT JOIN user_roles ur ON u.id_no = ur.id_no
            LEFT JOIN roles r       ON ur.role_id = r.role_id
            LEFT JOIN colleges c    ON ur.college_id = c.college_id
             WHERE 1=1
        ";
        $countParams = [];

        if ($q !== null && $q !== '') {
            $countSql .= "
                AND (
                    u.id_no::text ILIKE :q
                     OR u.fname ILIKE :q
                     OR u.mname ILIKE :q
                     OR u.lname ILIKE :q
                     OR u.email ILIKE :q
                     OR r.role_name ILIKE :q
                     OR c.short_name ILIKE :q
                )
            ";
            $countParams[':q'] = '%' . $q . '%';
        }

        $stmt = $this->pdo->prepare($countSql);
        foreach ($countParams as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        // ---- 2) Page rows ----
        $listSql = "
            SELECT
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                ur.role_id,
                ur.college_id,
                COALESCE(r.role_name, '')       AS role_name,
                COALESCE(c.short_name, '')      AS college_short_name
              FROM users u
            LEFT JOIN user_roles ur ON u.id_no = ur.id_no
            LEFT JOIN roles r       ON ur.role_id = r.role_id
            LEFT JOIN colleges c    ON ur.college_id = c.college_id
             WHERE 1=1
        ";
        $listParams = [];

        if ($q !== null && $q !== '') {
            $listSql .= "
                AND (
                    u.id_no::text ILIKE :q
                    OR u.fname ILIKE :q
                    OR u.mname ILIKE :q
                    OR u.lname ILIKE :q
                    OR u.email ILIKE :q
                    OR r.role_name ILIKE :q
                    OR c.short_name ILIKE :q
                )
            ";
            $listParams[':q'] = '%' . $q . '%';
        }

        $listSql .= " ORDER BY u.lname ASC, u.fname ASC LIMIT :limit OFFSET :offset";

        $stmt2 = $this->pdo->prepare($listSql);
        foreach ($listParams as $k => $v) $stmt2->bindValue($k, $v);
        $stmt2->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt2->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt2->execute();

        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
        // expects: id_no, fname, mname|null, lname, email, role_id(int), college_id(int|null)
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
                INSERT INTO user_roles (id_no, role_id, college_id)
                VALUES (:id_no, :role_id, :college_id)
            ");
            $ins->execute([
                ':id_no'      => $data['id_no'],
                ':role_id'    => (int)$data['role_id'],
                ':college_id' => $data['college_id'] === null ? null : (int)$data['college_id'],
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
