<?php
// /app/Modules/Colleges/Controllers/CollegesController.php
declare(strict_types=1);

namespace App\Modules\Colleges\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Helpers\FlashHelper;
use App\Modules\Colleges\Models\CollegesModel;

final class CollegesController
{
    private StorageInterface $db;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
    }

    public function index(): string {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['colleges'] ?? []; // <- plural key

        if (!empty($def['permission'])) {
            (new RBAC($this->db))->require((string)$_SESSION['user_id'], (string)$def['permission']);
        }

        $uid  = (string)($_SESSION['user_id'] ?? '');
        $rbac = new RBAC($this->db);
        $actions   = (array)($def['actions'] ?? []);
        $canCreate = isset($actions['create']) && $rbac->has($uid, (string)$actions['create']);
        $canEdit   = isset($actions['edit'])   && $rbac->has($uid, (string)$actions['edit']);
        $canDelete = isset($actions['delete']) && $rbac->has($uid, (string)$actions['delete']);

        $rawQ   = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $search = ($rawQ !== null && $rawQ !== '') ? mb_strtolower($rawQ) : null;
        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $model  = new CollegesModel($this->db);
        $result = $model->getPage($search, $perPage, $offset);
        $rows   = $result['rows'];
        $total  = $result['total'];

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
            'baseUrl'  => BASE_PATH . '/dashboard?page=colleges', // <- plural page
            'query'    => $rawQ,
        ];

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

    public function create(): void {
        $this->requireActionPermission('create');
        $this->assertCsrf();

        $data = [
            'short_name'   => trim((string)($_POST['short_name'] ?? '')),
            'college_name' => trim((string)($_POST['college_name'] ?? '')),
        ];

        $errors = [];
        if ($data['short_name'] === '')   $errors[] = 'Short name is required.';
        if ($data['college_name'] === '') $errors[] = 'College name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect(BASE_PATH . '/dashboard?page=colleges');
            return;
        }

        try {
            $id = (new CollegesModel($this->db))->create($data);
            FlashHelper::set('success', 'College created (ID ' . (int)$id . ').');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Create failed: ' . $e->getMessage());
        }

        $this->redirect(BASE_PATH . '/dashboard?page=colleges');
    }

    public function edit(): void {
        $this->requireActionPermission('edit');
        $this->assertCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirect(BASE_PATH . '/dashboard?page=colleges');
            return;
        }

        $data = [
            'short_name'   => trim((string)($_POST['short_name'] ?? '')),
            'college_name' => trim((string)($_POST['college_name'] ?? '')),
        ];

        $errors = [];
        if ($data['short_name'] === '')   $errors[] = 'Short name is required.';
        if ($data['college_name'] === '') $errors[] = 'College name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect(BASE_PATH . '/dashboard?page=colleges');
            return;
        }

        try {
            $ok = (new CollegesModel($this->db))->update($id, $data);
            $ok ? FlashHelper::set('success', 'College updated.')
                : FlashHelper::set('warning', 'No changes were made.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
        }

        $this->redirect(BASE_PATH . '/dashboard?page=colleges');
    }

    public function delete(): void {
        $this->requireActionPermission('delete');
        $this->assertCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirect(BASE_PATH . '/dashboard?page=colleges');
            return;
        }

        try {
            $ok = (new CollegesModel($this->db))->delete($id);
            $ok ? FlashHelper::set('success', 'College deleted.')
                : FlashHelper::set('warning', 'College not found.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Delete failed: ' . $e->getMessage());
        }

        $this->redirect(BASE_PATH . '/dashboard?page=colleges');
    }

    private function requireActionPermission(string $key): void {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['colleges'] ?? []; // <- plural key
        $actions = (array)($def['actions'] ?? []);
        $perm = (string)($actions[$key] ?? '');
        if ($perm !== '') {
            (new RBAC($this->db))->require((string)$_SESSION['user_id'], $perm);
        }
    }

    private function assertCsrf(): void
    {
        $token = (string)($_POST['csrf'] ?? '');
        $sess  = (string)($_SESSION['csrf_token'] ?? '');
        if ($token === '' || $sess === '' || !hash_equals($sess, $token)) {
            \App\Helpers\FlashHelper::set('danger', 'Invalid CSRF token.');
            $this->redirect(BASE_PATH . '/dashboard?page=colleges');
            exit;
        }

        // Rotate after a successful POST
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }


    private function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}
