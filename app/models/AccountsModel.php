<?php
// root/app/models/AccountsModel.php

class AccountsModel {
    private $pdo;

    public function __construct($db) {
        // $db is your PDO instance from DatabaseFactory
        $this->pdo = $db;
    }

    /**
     * Fetch all users, optionally filtered by search term.
     *
     * @param string|null $search
     * @return array
     */
    public function getAllUsers(?string $search = null): array {
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
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN colleges c ON ur.college_id = c.college_id
        ";

        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE 
                        u.id_no ILIKE :search OR
                        u.fname ILIKE :search OR
                        u.mname ILIKE :search OR
                        u.lname ILIKE :search OR
                        u.email ILIKE :search OR
                        r.role_name ILIKE :search OR
                        c.short_name ILIKE :search";
            $params[':search'] = "%$search%";
        }

        $sql .= " ORDER BY u.lname, u.fname";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
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

    /**
     * Delete a user.
     */
    public function deleteUser(string $idNo): bool {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id_no = :id_no");
        return $stmt->execute([':id_no' => $idNo]);
    }
}
