<?php
// /app/Modules/Settings/Controllers/SettingsController.php
declare(strict_types=1);

namespace App\Modules\Settings\Controllers;

use App\Interfaces\StorageInterface;
use PDO;

final class SettingsController
{
    private StorageInterface $db;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Resolve the current user's id_no (CHAR(13)) to match user_preferences.user_id.
     * - Prefer session 'id_no' if present.
     * - If only numeric user_id is in session, map it to id_no via DB.
     */
    private function currentUserIdNo(): ?string
    {
        // Preferred: you've standardized to store id_no directly in $_SESSION['user_id']
        $sid = $_SESSION['user_id'] ?? null;
        if (is_string($sid) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{5}$/', $sid)) {
            return $sid;
        }

        // Fallbacks (if some legacy code sets these)
        $idNo = $_SESSION['id_no'] ?? ($_SESSION['user']['id_no'] ?? null);
        if (is_string($idNo) && $idNo !== '') {
            return $idNo;
        }

        return null; // not authenticated / not found
    }

    /** GET /api/settings/get?key=dark_mode */
    public function getPreference(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $userIdNo = $this->currentUserIdNo();
        if ($userIdNo === null) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            return;
        }

        $key = (string)($_GET['key'] ?? '');
        if ($key === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing key']);
            return;
        }

        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('SELECT pref_value FROM user_preferences WHERE user_id = :uid AND pref_key = :k');
        $stmt->execute([':uid' => $userIdNo, ':k' => $key]);
        $val = $stmt->fetchColumn();

        echo json_encode(['ok' => true, 'enabled' => ($val === '1')]);
    }

    /** POST /api/settings/save  JSON: { key: string, value: "0"|"1" } */
    public function savePreference(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $userIdNo = $this->currentUserIdNo();
        if ($userIdNo === null) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);

        $key = isset($data['key']) ? (string)$data['key'] : '';
        $val = isset($data['value']) ? (string)$data['value'] : '';

        if ($key === '' || ($val !== '0' && $val !== '1')) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
            return;
        }

        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO user_preferences (user_id, pref_key, pref_value)
             VALUES (:uid, :k, :v)
             ON CONFLICT (user_id, pref_key) DO UPDATE SET pref_value = EXCLUDED.pref_value'
        );
        $stmt->execute([':uid' => $userIdNo, ':k' => $key, ':v' => $val]);

        echo json_encode(['ok' => true]);
    }
}
