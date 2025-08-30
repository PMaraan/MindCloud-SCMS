<?php
// app/Modules/Colleges/Controllers/CollegesController.php
declare(strict_types=1);

namespace App\Modules\Colleges\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Helpers\FlashHelper;

final class CollegesController
{
    private StorageInterface $db;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function index(): string {
        // Resolve registry (for permission keys & actions)
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['colleges'] ?? [];

        // Gate view permission if configured
        if (!empty($def['permission'])) {
            (new RBAC($this->db))->require((string)$_SESSION['user_id'], (string)$def['permission']);
        }

        // Compute action gates (DB each request; no caching)
        $uid = (string)($_SESSION['user_id'] ?? '');
        $rbac = new RBAC($this->db);
        $actions = (array)($def['actions'] ?? []);
        $canCreate = isset($actions['create']) && $rbac->has($uid, (string)$actions['create']);
        $canEdit   = isset($actions['edit'])   && $rbac->has($uid, (string)$actions['edit']);
        $canDelete = isset($actions['delete']) && $rbac->has($uid, (string)$actions['delete']);

        // Optional search + pagination skeleton (you can remove if not needed)
        $rawQ   = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $search = ($rawQ !== null && $rawQ !== '') ? mb_strtolower($rawQ) : null;
        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        // TODO: call your model here when ready
        // $model = new \App\Modules\Colleges\Models\CollegesModel($this->db);
        // $result = $model->getPage($search, $perPage, $offset);
        // $rows   = $result['rows'];
        // $total  = $result['total'];

        // Placeholder empty list
        $rows  = [];
        $total = 0;

        $pages = max(1, (int)ceil($total / $perPage));
        $pager = [
            'page'     => $page,
            'perPage'  => $perPage,
            'total'    => $total,
            'pages'    => $pages,
            'hasPrev'  => $page > 1,
            'hasNext'  => $page < $pages,
            'prev'     => max(1, $page - 1),
            'next'     => min($pages, $page + 1),
            // Preserve module key in all links:
            'baseUrl'  => BASE_PATH . '/dashboard?page=colleges',
            'query'    => $rawQ,
        ];

        // Expose to view
        $data = [
            'rows'      => $rows,
            'pager'     => $pager,
            'canCreate' => $canCreate,
            'canEdit'   => $canEdit,
            'canDelete' => $canDelete,
        ];
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    // Example stubs (uncomment/implement when you need them):
    // public function create(): void {}
    // public function edit(): void {}
    // public function delete(): void {}
}