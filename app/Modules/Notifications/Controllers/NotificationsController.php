<?php
declare(strict_types=1);

namespace App\Modules\Notifications\Controllers;

use App\Interfaces\StorageInterface;
use App\Modules\Notifications\Models\NotificationsModel;

final class NotificationsController
{
    private StorageInterface $db;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * GET /notifications/latest
     * Returns JSON: { items: [ {id,title,body,url,is_read,created_at}, ... ] }
     */
    public function latestJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // DEV OVERRIDE: allow ?debug_id_no=2025-01-20001 to test without login/session
        $debugId = isset($_GET['debug_id_no']) ? (string)$_GET['debug_id_no'] : '';
        $debugId = trim($debugId);

        // normalize id_no to CHAR(13): it must be exactly 13 chars (pad/right-trim later in model)
        $normalize = static function (string $v): string {
            return substr(str_pad($v, 13, ' ', STR_PAD_RIGHT), 0, 13);
        };

        $idNo = '';
        if ($debugId !== '') {
            $idNo = $debugId;
        } elseif (!empty($_SESSION['id_no'])) {
            $idNo = (string)$_SESSION['id_no'];
        } elseif (!empty($_SESSION['user_id_no'])) {
            $idNo = (string)$_SESSION['user_id_no'];
        } elseif (!empty($_SESSION['user_id'])) {
            $idNo = (string)$_SESSION['user_id'];
        }

        $idNo = trim($idNo);
        if ($idNo === '') {
            http_response_code(401);
            echo json_encode(['items' => []], JSON_UNESCAPED_SLASHES);
            return;
        }

        $items = (new \App\Modules\Notifications\Models\NotificationsModel($this->db))
            ->latestForUserIdNo($normalize($idNo), 5);

        $safe = array_map(static function(array $n): array {
            return [
                'id'         => (int)($n['id'] ?? 0),
                'title'      => (string)($n['title'] ?? ''),
                'body'       => (string)($n['body'] ?? ''),
                'url'        => (string)($n['url'] ?? ''),
                'is_read'    => (bool)($n['is_read'] ?? false),
                'created_at' => (string)($n['created_at'] ?? ''),
            ];
        }, $items);

        echo json_encode(['items' => $safe], JSON_UNESCAPED_SLASHES);
    }
}
