<?php
// root/app/helpers/PasswordHelper.php

class PasswordHelper
{
    /**
     * Default hashing options.
     * Adjust here if you want to increase security or change performance.
     */
    private static array $options = [
        'memory_cost' => 1 << 16, // 65536 KB = 64 MB
        'time_cost'   => 4,       // Iterations
        'threads'     => 1        // Parallelism
    ];

    /**
     * Algorithm to use for hashing.
     * You can change this in the future (e.g., PASSWORD_BCRYPT).
     */
    private const ALGO = PASSWORD_ARGON2ID;

    /**
     * Hash a password using the configured algorithm and options.
     */
    public static function hash(string $password): string
    {
        return password_hash($password, self::ALGO, self::$options);
    }

    /**
     * Verify a password against its stored hash.
     * Automatically checks if the hash needs rehashing and updates if needed.
     *
     * @param string   $password The plain-text password
     * @param string   $hash     The stored password hash
     * @param callable $rehashCallback Optional function to store the new hash if rehashed
     *
     * Example rehashCallback:
     * function($newHash) use ($userId, $db) { $db->updateUserPassword($userId, $newHash); }
     */
    public static function verify(string $password, string $hash, callable $rehashCallback = null): bool
    {
        if (!password_verify($password, $hash)) {
            return false;
        }

        // Check if the hash needs rehashing
        if (password_needs_rehash($hash, self::ALGO, self::$options)) {
            if ($rehashCallback) {
                $newHash = self::hash($password);
                $rehashCallback($newHash);
            }
        }

        return true;
    }
}

/*
// hash on user creation
// lace this code on the account model/user model
// or what file handles account creation ...
require_once __DIR__ . '/../helpers/PasswordHelper.php';

$newPassword = 'MySecurePassword123';
$hash = PasswordHelper::hash($newPassword);

// Store $hash in DB

*/