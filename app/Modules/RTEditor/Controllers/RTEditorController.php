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
