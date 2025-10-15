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
}
