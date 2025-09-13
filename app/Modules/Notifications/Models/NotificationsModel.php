<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Interfaces\StorageInterface;
use PDO;

final class NotificationsModel
{
    private StorageInterface $db;
    private PDO $pdo;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        $this->pdo = $db->getConnection();
    }

    /**
     * Get latest notifications for a user id_no (CHAR(13)).
     * Returns: array of ['id','title','body','url','is_read','created_at']
     */
    public function latestForUserIdNo(string $idNo, int $limit = 5): array
    {
        // normalize to exactly 13 chars (trim spaces from session, DB stores CHAR(13))
        $idNo = substr(str_pad(trim($idNo), 13, ' ', STR_PAD_RIGHT), 0, 13);

        $limit = max(1, min($limit, 20)); // bounded safety
        $sql = <<<SQL
        SELECT id, title, body, url, is_read, created_at
        FROM notifications
        WHERE user_id_no = :idno
        ORDER BY created_at DESC
        LIMIT {$limit}
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idno', $idNo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
