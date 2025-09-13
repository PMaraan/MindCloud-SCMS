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

    public function index(): string
    {
        // Current user (AuthController stores in $_SESSION['user_id'])
        $idNo = (string)($_SESSION['user_id'] ?? '');
        if ($idNo === '') {
            return '<div class="container py-4"><div class="alert alert-danger">Unauthorized.</div></div>';
        }

        // Filters & pagination
        $status = strtolower((string)($_GET['status'] ?? 'all'));
        if (!in_array($status, ['all','unread','read'], true)) $status = 'all';

        $pg     = max(1, (int)($_GET['pg'] ?? 1));
        $limit  = 10;
        $offset = ($pg - 1) * $limit;

        // Query via model (read-only)
        $model = new NotificationsModel($this->db);
        $total = $model->countForUser($idNo, $status);
        $rows  = $model->listForUser($idNo, $offset, $limit, $status);
        $pages = (int)max(1, (int)ceil($total / $limit));

        // Standard pager structure used by your modules
        $pager = [
            'baseUrl' => BASE_PATH . '/dashboard?page=notifications',
            'pg'      => $pg,
            'perPage' => $limit,
            'total'   => $total,
            'pages'   => $pages,
            'query'   => '',
            'status'  => $status,
        ];

        // Render view
        ob_start();
        require dirname(__DIR__) . '/Views/index.php';
        return (string)ob_get_clean();
    }
    /**
     * GET /notifications/latest
     * Returns JSON: { items: [ {id,title,body,url,is_read,created_at}, ... ] }
     */
    public function latestJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // DEV: allow manual override for testing e.g. /notifications/latest?debug_id_no=2025-01-20001
        $debugId = isset($_GET['debug_id_no']) ? (string)$_GET['debug_id_no'] : '';
        $idNo = $debugId !== '' ? trim($debugId) : (string)($_SESSION['user_id'] ?? '');

        if ($idNo === '') {
            http_response_code(401);
            echo json_encode(['items' => []], JSON_UNESCAPED_SLASHES);
            return;
        }

        $items = (new \App\Modules\Notifications\Models\NotificationsModel($this->db))
            ->latestForUserIdNo($idNo, 5);

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

    /**
     * GET /notifications/unread-count
     * Returns JSON: { total_unread: <int> }
     * Supports ?debug_id_no=2025-01-20001 for quick testing (same as latestJson).
     */
    public function unreadCountJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $debugId = isset($_GET['debug_id_no']) ? (string)$_GET['debug_id_no'] : '';
        $debugId = trim($debugId);

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
            echo json_encode(['total_unread' => 0], JSON_UNESCAPED_SLASHES);
            return;
        }

        $count = (new \App\Modules\Notifications\Models\NotificationsModel($this->db))
            ->countUnreadForUserIdNo($normalize($idNo));

        echo json_encode(['total_unread' => (int)$count], JSON_UNESCAPED_SLASHES);
    }

    /**
     * POST /notifications/mark-read
     * Body: { "ids": [1,2,3] }
     * Marks given IDs as read for the logged in user.
     * Returns: { "updated": <int> }
     */
    public function markReadJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $idNo = (string)($_SESSION['user_id'] ?? '');
        if ($idNo === '') {
            http_response_code(401);
            echo json_encode(['updated' => 0], JSON_UNESCAPED_SLASHES);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        $ids  = is_array($data['ids'] ?? null) ? $data['ids'] : [];

        try {
            $model = new \App\Modules\Notifications\Models\NotificationsModel($this->db);
            $updated = $model->markReadBulk($idNo, $ids);
            echo json_encode(['updated' => (int)$updated], JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['updated' => 0], JSON_UNESCAPED_SLASHES);
        }
    }
}
