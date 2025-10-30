<?php
declare(strict_types=1);

/**
 * RTEditorController (rebuild clean)
 * Path: /app/Modules/RTEditor/Controllers/RTEditorController.php
 *
 * Goal: render a bare page with a contenteditable area that we can type into.
 * No DB, no TipTap, no Yjs. We'll add them later.
 */
namespace App\Modules\RTEditor\Controllers;

use App\Interfaces\StorageInterface;
use App\Modules\RTEditor\Models\RTEditorModel;

final class RTEditorController
{
    private StorageInterface $db;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db; // not used yet
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Minimal render: provide view variables and include the view.
     * No RBAC gating here to eliminate variables while we debug typing.
     * (Sidebar visibility is still driven by your ModuleRegistry+RBAC.)
     */
    public function index(): string
    {
        // Hard-set a page title and a flag we'll use in the view
        $pageTitle = 'RT Editor (Clean Build)';
        $canEdit   = true; // force editable for now

        ob_start();
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    /**
     * Save (snapshot) TipTap JSON from the editor.
     * Accepts POST with:
     *  - scope: "template" | "syllabus"
     *  - id: template_id or syllabus_id (int)
     *  - json: TipTap JSON string
     *  - filename: optional string
     */
    public function snapshot(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
                return;
            }

            // If you have a CSRF helper, enforce it here:
            // \App\Helpers\CsrfHelper::assertJson(); // or your variant

            $raw  = file_get_contents('php://input');
            $data = json_decode($raw ?: 'null', true);
            if (!$data || !is_array($data)) {
                // fallback to form-encoded POST
                $data = $_POST;
            }

            $scope    = (string)($data['scope'] ?? '');
            $id       = (int)($data['id'] ?? 0);
            $filename = isset($data['filename']) ? trim((string)$data['filename']) : null;

            if ($id <= 0) {
                throw new \InvalidArgumentException('Missing or invalid id.');
            }

            // Accept TipTap JSON as object/array or string
            $content = $data['json'] ?? $data['content'] ?? null;
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $content = $decoded;
                }
            }
            if (!is_array($content) && !is_object($content)) {
                throw new \InvalidArgumentException('Missing or invalid TipTap JSON.');
            }

            // Use your existing model
            $model = new \App\Modules\RTEditor\Models\RTEditorModel($this->db);

            if ($scope === 'template') {
                // Option A: if you added the convenience method
                $ok = $model->saveTemplateContent($id, $content, $filename);

                // Option B (no convenience): uncomment instead
                // $ok = $model->updateTemplate($id, ['content' => $content, 'filename' => $filename]);
            } elseif ($scope === 'syllabus') {
                // Option A:
                $ok = $model->saveSyllabusContent($id, $content, $filename);

                // Option B (no convenience): uncomment instead
                // $ok = $model->updateSyllabus($id, ['content' => $content, 'filename' => $filename]);
            } else {
                throw new \InvalidArgumentException('Invalid scope; must be "template" or "syllabus".');
            }

            if (!$ok) {
                throw new \RuntimeException('Save failed (no changes or record not found).');
            }

            echo json_encode(['ok' => true, 'saved' => ['scope' => $scope, 'id' => $id]]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function openTemplate(): string
    {
        // Permissions: view templates is enough to open for editing
        (new \App\Security\RBAC($this->db))->require((string)$_SESSION['user_id'], \App\Config\Permissions::SYLLABUSTEMPLATES_VIEW);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Invalid template id'];
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates');
            exit;
        }

        // Use RTEditorModel for loading (database-agnostic, pure PDO)
        $model = new \App\Modules\RTEditor\Models\RTEditorModel($this->db);
        $tpl   = $model->getTemplateForEdit($id);
        if (!$tpl) {
            $_SESSION['flash'] = ['type'=>'danger','message'=>'Template not found'];
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates');
            exit;
        }

        // Prepare payload for hydration
        $loaded = [
            'kind'   => 'template',
            'id'     => (int)$tpl['template_id'],
            'title'  => (string)($tpl['title'] ?? 'Untitled'),
            'status' => (string)($tpl['status'] ?? 'draft'),
            // allow string or decoded array; front-end will handle both
            'content'=> $tpl['content'] ?? null,
            'filename' => (string)($tpl['filename'] ?? ''),
            'version'  => (string)($tpl['version'] ?? ''),
        ];

        // Reuse your existing editor page (whatever your default render method uses)
        // Pass $loaded to the view (we only need to embed JSON; no new view file added)
        $ASSET_BASE = (defined('BASE_PATH') ? BASE_PATH : '') . '/public';
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        // If your controller has a standard render() for index/editor page, reuse it.
        // Otherwise require the same existing editor view you already open at ?page=rteditor
        ob_start();
        $loadedJson = json_encode($loaded, JSON_UNESCAPED_UNICODE);
        // The existing editor view will read this <script> for hydration
        echo '<script id="rt-loaded-content" type="application/json">'. $esc($loadedJson) .'</script>';
        // fall through to existing render
        $htmlOfEditor = $this->index('index', [
            'ASSET_BASE' => $ASSET_BASE,
            'esc'        => $esc,
            // anything else your view expects...
        ]);
        // Prepend the hydration tag before the closing </body> would also be fine; here we just echo it above.
        // Combine and return
        echo $htmlOfEditor;
        return (string)ob_get_clean();
    }

}
