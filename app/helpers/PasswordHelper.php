<?php
// root/app/Helpers/PasswordHelper.php
declare(strict_types=1);

namespace App\Helpers;

final class PasswordHelper
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
     * Choose algorithm: prefer Argon2id if available; else BCRYPT fallback.
     * (Some Windows/XAMPP builds may lack Argon2.)
     */
    private const ALGO_FALLBACK = PASSWORD_BCRYPT;

    private static function algo(): string|int
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : self::ALGO_FALLBACK;
    }

    /**
     * Hash a password using the configured algorithm and options.
     */
    public static function hash(string $password): string
    {
        // When falling back to BCRYPT, ignore Argon options
        $algo = self::algo();
        $opts = ($algo === PASSWORD_BCRYPT)
            ? [] // you could set ['cost'=>12] if you want
            : self::$options;

        return password_hash($password, $algo, $opts);
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

        $algo = self::algo();
        $opts = ($algo === PASSWORD_BCRYPT) ? [] : self::$options;

        if (password_needs_rehash($hash, $algo, $opts)) {
            if ($rehashCallback) {
                $rehashCallback(self::hash($password));
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