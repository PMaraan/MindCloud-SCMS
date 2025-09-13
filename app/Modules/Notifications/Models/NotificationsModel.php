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
        // Unread first (is_read=false → 0), then read; newest first within each group
        $sql = <<<SQL
        SELECT id, title, body, url, is_read, created_at
        FROM notifications
        WHERE user_id_no = :idno
        ORDER BY CASE WHEN is_read = FALSE THEN 0 ELSE 1 END, created_at DESC
        LIMIT {$limit}
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idno', $idNo, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Count unread notifications for the given user id_no (CHAR(13)).
     */
    public function countUnreadForUserIdNo(string $idNo): int
    {
        // normalize to exactly 13 chars (DB column is CHAR(13))
        $idNo = substr(str_pad(trim($idNo), 13, ' ', STR_PAD_RIGHT), 0, 13);

        $sql = "SELECT COUNT(*)::int AS cnt FROM notifications WHERE user_id_no = :idno AND is_read = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idno', $idNo, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Insert a single notification for one user (id_no: CHAR(13)).
     * Returns the inserted notification id.
     */
    public function create(string $idNo, string $title, string $body = '', string $url = ''): int
    {
        $idNo = substr(str_pad(trim($idNo), 13, ' ', STR_PAD_RIGHT), 0, 13);

        $sql = "INSERT INTO notifications (user_id_no, title, body, url) 
                VALUES (:idno, :title, :body, :url)
                RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idno', $idNo, PDO::PARAM_STR);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':body', $body, PDO::PARAM_STR);
        $stmt->bindValue(':url', $url, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['id'] ?? 0);
    }

    /**
     * Insert notifications for many users in one transaction.
     * Returns the number of inserted rows.
     */
    public function createBulk(array $idNos, string $title, string $body = '', string $url = ''): int
    {
        if (empty($idNos)) return 0;

        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO notifications (user_id_no, title, body, url) VALUES (:idno, :title, :body, :url)";
            $stmt = $this->pdo->prepare($sql);

            $count = 0;
            foreach ($idNos as $idNo) {
                $idNo = substr(str_pad(trim((string)$idNo), 13, ' ', STR_PAD_RIGHT), 0, 13);
                if ($idNo === '') continue;

                $stmt->bindValue(':idno', $idNo, PDO::PARAM_STR);
                $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                $stmt->bindValue(':body', $body, PDO::PARAM_STR);
                $stmt->bindValue(':url', $url, PDO::PARAM_STR);
                $stmt->execute();
                $count++;
            }
            $this->pdo->commit();
            return $count;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Mark a set of notifications as read for the given user id_no.
     * Returns the number of updated rows.
     */
    public function markReadBulk(string $idNo, array $ids): int
    {
        $idNo = substr(str_pad(trim($idNo), 13, ' ', STR_PAD_RIGHT), 0, 13);
        // sanitize ids -> unique ints
        $ids = array_values(array_unique(array_map(static fn($v) => (int)$v, $ids)));
        if (empty($ids)) return 0;

        // dynamic placeholders
        $ph = [];
        foreach ($ids as $i => $_) { $ph[] = ':id' . $i; }
        $in = implode(',', $ph);

        $sql = "UPDATE notifications
                SET is_read = TRUE
                WHERE user_id_no = :idno AND id IN ($in)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':idno', $idNo, PDO::PARAM_STR);
        foreach ($ids as $i => $val) {
            $stmt->bindValue(':id' . $i, $val, PDO::PARAM_INT);
        }
        $stmt->execute();
        return (int)$stmt->rowCount();
    }
}
