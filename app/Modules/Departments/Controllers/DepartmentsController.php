<?php
// /app/Modules/Departments/Controllers/DepartmentsController.php
declare(strict_types=1);

namespace App\Modules\Departments\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Helpers\FlashHelper;
use App\Models\DepartmentsModel;
use App\Models\UserModel;
use App\Services\AssignmentsService;
use App\Helpers\CsrfHelper;

final class DepartmentsController
{
    private StorageInterface $db;
    private DepartmentsModel $model;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        $this->model = new DepartmentsModel($this->db);
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
    }

    public function index(): string {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['departments'] ?? []; // <- plural key

        // If access requires a permission, check user permission
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
        $perPage = max(1, (int)(defined('UI_PER_PAGE_DEFAULT') ? UI_PER_PAGE_DEFAULT : 10));
        $offset  = ($page - 1) * $perPage;

        $result = $this->model->getPage($search, $perPage, $offset);
        $rows   = $result['rows'];
        $total  = $result['total'];

        // Compute from/to once so the partial (and the view) can reuse them
        $from = ($total > 0) ? (($page - 1) * $perPage + 1) : 0;
        $to   = ($total > 0) ? min($total, $page * $perPage) : 0;

        $pager = [
            'total'    => $total,
            'pg'       => $page,
            'perpage'  => $perPage,
            'baseUrl'  => BASE_PATH . '/dashboard?page=departments',
            'query'    => $rawQ ?? '',
            'from'     => $from,
            'to'       => $to,
        ];

        // Get a list of users who are deans.
        $deans = (new UserModel($this->db))->listUsersByRole('Dean');
        $data = [
            'rows'      => $rows,
            'pager'     => $pager,
            'canCreate' => $canCreate,
            'canEdit'   => $canEdit,
            'canDelete' => $canDelete,
            'deans'     => $deans,
        ];
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    public function create(): void {
        $this->requireActionPermission('create');
        CsrfHelper::assertOrRedirect(BASE_PATH . '/dashboard?page=departments');

        $data = [
            'short_name'   => trim((string)($_POST['short_name'] ?? '')),
            'department_name' => trim((string)($_POST['department_name'] ?? '')),
            'is_college'   => !empty($_POST['is_college']),
        ];
        $deanIdNo = trim((string)($_POST['dean_id_no'] ?? ''));

        $errors = [];
        if ($data['short_name'] === '')   $errors[] = 'Short name is required.';
        if ($data['department_name'] === '') $errors[] = 'Department name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect(BASE_PATH . '/dashboard?page=departments');
            return;
        }

        try {
            $id = $this->model->create($data);
            // Optional dean handling (non-blocking): only runs if a dean was selected
            if ($deanIdNo !== '') {
                try {
                    (new AssignmentsService($this->db))->setDepartmentDean((int)$id, $deanIdNo);
                    FlashHelper::set('success', 'Department created (ID ' . (int)$id . '). Dean assigned.');
                } catch (\DomainException $e) {
                    // Business-rule error (e.g., selected user is not a Dean)
                    FlashHelper::set('warning', 'Department created (ID ' . (int)$id . '), but dean not assigned: ' . $e->getMessage());
                }
            } else {
                FlashHelper::set('success', 'Department created (ID ' . (int)$id . ').');
            }
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Create failed: ' . $e->getMessage());
        }

        $this->redirect(BASE_PATH . '/dashboard?page=departments');
    }

    public function edit(): void {
        $this->requireActionPermission('edit');
        CsrfHelper::assertOrRedirect(BASE_PATH . '/dashboard?page=departments');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirect(BASE_PATH . '/dashboard?page=departments');
            return;
        }

        $data = [
            'short_name'   => trim((string)($_POST['short_name'] ?? '')),
            'department_name' => trim((string)($_POST['department_name'] ?? '')),
            'is_college'   => !empty($_POST['is_college']),
        ];
        $deanIdNo = trim((string)($_POST['dean_id_no'] ?? ''));

        $errors = [];
        if ($data['short_name'] === '')   $errors[] = 'Short name is required.';
        if ($data['department_name'] === '') $errors[] = 'Department name is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            $this->redirect(BASE_PATH . '/dashboard?page=departments');
            return;
        }

        try {
            $ok = $this->model->update($id, $data);
            try {
                (new AssignmentsService($this->db))->setDepartmentDean($id, $deanIdNo !== '' ? $deanIdNo : null);
                $ok ? FlashHelper::set('success', 'Department updated.')
                    : FlashHelper::set('warning', 'No changes were made.');
            } catch (\DomainException $e) {
                FlashHelper::set('warning', 'Department updated, but dean not assigned: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Update failed: ' . $e->getMessage());
        }

        $this->redirect(BASE_PATH . '/dashboard?page=departments');
    }

    public function delete(): void {
        $this->requireActionPermission('delete');
        CsrfHelper::assertOrRedirect(BASE_PATH . '/dashboard?page=departments');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid ID.');
            $this->redirect(BASE_PATH . '/dashboard?page=departments');
            return;
        }

        try {
            $ok = $this->model->delete($id);
            $ok ? FlashHelper::set('success', 'Department deleted.')
                : FlashHelper::set('warning', 'Department not found.');
        } catch (\Throwable $e) {
            FlashHelper::set('danger', 'Delete failed: ' . $e->getMessage());
        }

        $this->redirect(BASE_PATH . '/dashboard?page=departments');
    }

    private function requireActionPermission(string $key): void {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['departments'] ?? []; // <- plural key
        $actions = (array)($def['actions'] ?? []);
        $perm = (string)($actions[$key] ?? '');
        if ($perm !== '') {
            (new RBAC($this->db))->require((string)$_SESSION['user_id'], $perm);
        }
    }

    private function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}
