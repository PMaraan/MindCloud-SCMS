<?php
declare(strict_types=1);

/**
 * RTEditorController
 * Path: /app/Modules/RTEditor/Controllers/RTEditorController.php
 *
 * Renders a TipTap + Yjs editor for a syllabus_template (template_id).
 * Actions: index (view), create (insert new template), saveMeta (title), snapshot (Yjs snapshot).
 */
namespace App\Modules\RTEditor\Controllers;

use App\Models\Postgres;
use App\Modules\RTEditor\Models\RTEditorModel;
use App\Security\RBAC;

final class RTEditorController
{
    public function __construct(private Postgres $db) {}

    public function index(): string
    {
        RBAC::require('EDITOR_VIEW');

        $templateId = (int)($_GET['template_id'] ?? 0);
        $model = new RTEditorModel($this->db);
        $meta  = $templateId > 0 ? $model->findById($templateId) : null;

        // room key for Yjs = template_id when available; otherwise a temp value
        $room = $meta['template_id'] ?? ('tmp-' . bin2hex(random_bytes(4)));

        $title = $meta['title'] ?? 'Untitled Template';
        $canCreate = RBAC::check('EDITOR_CREATE');
        $canEdit   = RBAC::check('EDITOR_EDIT');

        ob_start();
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    public function create(): void
    {
        RBAC::require('EDITOR_CREATE');
        $title = trim((string)($_POST['title'] ?? 'Untitled Template'));
        $user  = (string)($_SESSION['user_id'] ?? 'system');

        $model = new RTEditorModel($this->db);
        $newId = $model->createTemplate($title, $user);

        header('Content-Type: application/json');
        echo json_encode(['ok' => $newId !== null, 'template_id' => $newId, 'title' => $title]);
    }

    public function saveMeta(): void
    {
        RBAC::require('EDITOR_EDIT');

        $templateId = (int)($_POST['template_id'] ?? 0);
        $title      = trim((string)($_POST['title'] ?? ''));

        $ok = false;
        if ($templateId > 0 && $title !== '') {
            $model = new RTEditorModel($this->db);
            $ok = $model->updateTitle($templateId, $title);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok]);
    }

    public function snapshot(): void
    {
        RBAC::require('EDITOR_EDIT');

        $templateId = (int)($_POST['template_id'] ?? 0);
        $b64        = (string)($_POST['ydoc_snapshot_b64'] ?? '');

        $ok = false;
        if ($templateId > 0 && $b64 !== '') {
            $model = new RTEditorModel($this->db);
            $ok = $model->saveSnapshot($templateId, $b64);
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok]);
    }
}
