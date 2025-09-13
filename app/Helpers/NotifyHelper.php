<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Interfaces\StorageInterface;
use App\Modules\Notifications\Models\NotificationsModel;

/**
 * NotifyHelper — simple facade to dispatch notifications from any controller,
 * similar to FlashHelper: static methods, db injected once during bootstrap.
 */
final class NotifyHelper
{
    private static ?StorageInterface $db = null;

    /** Call this once in bootstrap after you create $db */
    public static function boot(StorageInterface $db): void
    {
        self::$db = $db;
    }

    private static function model(): NotificationsModel
    {
        if (!self::$db) {
            throw new \RuntimeException('NotifyHelper not booted. Call NotifyHelper::boot($db) in bootstrap.');
        }
        return new NotificationsModel(self::$db);
    }

    /**
     * Send one notification to one user.
     */
    public static function toUser(string $idNo, string $title, string $body = '', string $url = ''): int
    {
        return self::model()->create($idNo, $title, $body, $url);
    }

    /**
     * Send the same notification to multiple users (array of id_no).
     * Returns number of inserted rows.
     */
    public static function toUsers(array $idNos, string $title, string $body = '', string $url = ''): int
    {
        return self::model()->createBulk($idNos, $title, $body, $url);
    }
}
