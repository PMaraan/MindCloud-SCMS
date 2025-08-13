<?php
// app/models/UserModel.php

// Load dependencies
require_once __DIR__ . '/../helpers/PasswordHelper.php';

class UserModel {
    /** @var \PDO */
    private $pdo;

    public function __construct(StorageInterface $db) {
        $this->pdo = $db->getConnection(); // Always get PDO from storage interface
    }

    /**
     * Authenticate user by email & password.
     * Returns full user details with role & college if valid, false otherwise.
     * Automatically rehashes password if needed.
     */
    public function authenticate(string $email, string $password) {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM users 
            WHERE email = ?
            LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // Check if passwords match
        // Also rehashes the password automatically if the hashing algo is changed
        if (!PasswordHelper::verify(
            $password,
            $user['password'],
            function ($newHash) use ($user) {
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
     * Get full user profile from database
     */
    public function getUserProfile($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                r.role_name AS role,
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
    public function rehashPassword(string $userId, string $newHash): bool
    {
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
    public function setPassword(string $userId, string $newPassword): bool
    {
        $hash = PasswordHelper::hash($newPassword);

        $stmt = $this->pdo->prepare("
            UPDATE users
            SET password = ?
            WHERE id_no = ?
        ");
        return $stmt->execute([$hash, $userId]);
    }
}
