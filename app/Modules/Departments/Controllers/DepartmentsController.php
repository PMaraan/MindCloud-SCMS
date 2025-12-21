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
    private FlashHelper $flashHelper;
    private CsrfHelper $csrfHelper;
    private RBAC $rbac;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        $this->model = new DepartmentsModel($this->db);
        $this->rbac = new RBAC($this->db);
        $this->csrfHelper = new CsrfHelper();
        $this->flashHelper = new FlashHelper();
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
    }

    public function index(): string {
        $registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $registry = is_file($registryPath) ? require $registryPath : [];
        $def = $registry['departments'] ?? []; // <- plural key

        // If access requires a permission, check user permission
        if (!empty($def['permission'])) {
            $this->rbac->require((string)$_SESSION['user_id'], (string)$def['permission']);
        }

        $uid  = (string)($_SESSION['user_id'] ?? '');
        $rbac = $this->rbac;
        $actions   = (array)($def['actions'] ?? []);
        $canCreate = isset($actions['create']) && $rbac->has($uid, (string)$actions['create']);
        $canEdit   = isset($actions['edit'])   && $rbac->has($uid, (string)$actions['edit']);
        $canDelete = isset($actions['delete']) && $rbac->has($uid, (string)$actions['delete']);

        $rawQ   = isset($_GET['q']) ? trim((string)$_GET['q']) : null; // Search query parameter
        $search = ($rawQ !== null && $rawQ !== '') ? mb_strtolower($rawQ) : null; // Normalize search
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : 'active'; // Status filter parameter
        $page    = max(1, (int)($_GET['pg'] ?? 1)); // Current page number
        $perPage = max(1, (int)(defined('UI_PER_PAGE_DEFAULT') ? UI_PER_PAGE_DEFAULT : 10)); // Items per page
        $offset  = ($page - 1) * $perPage; // Offset for database query

        $result = $this->model->getPage($search, $perPage, $offset, $status); // Db query
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
            'status'   => $status, // <-- add this for the view
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
        $this->csrfHelper->assertOrRedirect(BASE_PATH . '/dashboard?page=departments');

        $data = [
            'short_name'       => trim((string)($_POST['short_name'] ?? '')),
            'department_name'  => trim((string)($_POST['department_name'] ?? '')),
            'is_college'       => !empty($_POST['is_college']),
            'status'           => trim((string)($_POST['status'] ?? 'active')),
        ];
        $deanIdNo = trim((string)($_POST['dean_id_no'] ?? ''));

        // ...validate...

        try {
            $id = $this->model->create($data);

            if ($data['is_college'] && $deanIdNo !== '') {
                try {
                    (new \App\Services\AssignmentsService($this->db))->setDepartmentDean((int)$id, $deanIdNo);
                    $this->flashHelper->set('success', 'Department created (ID ' . (int)$id . '). Dean assigned.');
                } catch (\DomainException $e) {
                    $this->flashHelper->set('warning', 'Department created (ID ' . (int)$id . '), but dean not assigned: ' . $e->getMessage());
                }
            } else {
                // Ensure no dean mapping exists if not a college
                (new \App\Services\AssignmentsService($this->db))->setDepartmentDean((int)$id, null);
                $this->flashHelper->set('success', 'Department created (ID ' . (int)$id . ').');
            }
        } catch (\Throwable $e) {
           
        $this->redirect(BASE_PATH . '/dashboard?page=departments');
        }
    }
    
    public function edit(): void {
        $this->requireActionPermission('edit');
        \App\Helpers\CsrfHelper::assertOrRedirect(BASE_PATH . '/dashboard?page=departments');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->flashHelper->set('danger', 'Invalid ID.');
            $this->redirect(BASE_PATH . '/dashboard?page=departments');
        }

        $data = [
            'short_name'       => trim((string)($_POST['short_name'] ?? '')),
            'department_name'  => trim((string)($_POST['department_name'] ?? '')),
            'is_college'       => !empty($_POST['is_college']),
            'status'           => trim((string)($_POST['status'] ?? 'active')),
        ];
        $deanIdNo = trim((string)($_POST['dean_id_no'] ?? ''));

        // ...validate...

        try {
            $ok = $this->model->update($id, $data);

            // If not a college, force-clear the dean mapping
            if (!$data['is_college']) {
                (new \App\Services\AssignmentsService($this->db))->setDepartmentDean($id, null);
                $ok ? $this->flashHelper->set('success', 'Department updated. Dean cleared (not a college).')
                    : $this->flashHelper->set('warning', 'No changes were made. Dean cleared (not a college).');
            } else {
                // Is a college → apply dean if provided, else clear
                try {
                    (new \App\Services\AssignmentsService($this->db))->setDepartmentDean($id, $deanIdNo !== '' ? $deanIdNo : null);
                    $ok ? $this->flashHelper->set('success', 'Department updated.')
                        : $this->flashHelper->set('warning', 'No changes were made.');
                } catch (\DomainException $e) {
                    $this->flashHelper->set('warning', 'Department updated, but dean not assigned: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $this->flashHelper->set('danger', 'Update failed: ' . $e->getMessage());
        }

        $this->redirect(BASE_PATH . '/dashboard?page=departments');
    }

    public function delete(): void {
        $this->requireActionPermission('delete');
        $this->csrfHelper->assertOrRedirect(BASE_PATH . '/dashboard?page=departments');

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->flashHelper->set('danger', 'Invalid ID.');
            $this->redirect(BASE_PATH . '/dashboard?page=departments');
            return;
        }

        try {
            $ok = $this->model->delete($id);
            $ok ? $this->flashHelper->set('success', 'Department deleted.')
                : $this->flashHelper->set('warning', 'Department not found.');
        } catch (\Throwable $e) {
            $this->flashHelper->set('danger', 'Delete failed: ' . $e->getMessage());
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
            $this->rbac->require((string)$_SESSION['user_id'], $perm);
        }
    }

    private function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}
