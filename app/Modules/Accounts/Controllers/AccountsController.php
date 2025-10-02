<?php
// /app/Modules/Accounts/Controllers/AccountsController.php
declare(strict_types=1);

namespace App\Modules\Accounts\Controllers;

use App\Interfaces\StorageInterface;
use App\Modules\Accounts\Models\AccountsModel;
use App\Helpers\FlashHelper;
use App\Helpers\PasswordHelper;
use App\Security\RBAC;
use App\Helpers\NotifyHelper;
use App\Services\AssignmentsService;

/**
 * AccountsController
 *
 * Handles Accounts module list/create/edit/delete.
 * - Departments-first schema (colleges are departments with is_college = TRUE)
 * - Uses the global pagination component contract ($pager['total','pg','perpage','baseUrl',...])
 * - No caching: always queries DB for fresh state (ISO 25010 - reliability/ maintainability)
 * - After save: sync dean <-> college via AssignmentsService
 */
final class AccountsController
{
    private StorageInterface $db;
    private AccountsModel $model;
    private AssignmentsService $assignments;

    /** Cached per-request Dean role id (no persistence across requests). */
    private ?int $deanRoleId = null;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        $this->model = new AccountsModel($db);
        $this->assignments = new AssignmentsService($db);

        if (defined('APP_ENV') && APP_ENV === 'dev') {
            error_log('AccountsController using model: ' . get_class($this->model));
        }
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Resolve the numeric role_id for the 'dean' role (case-insensitive).
     * Recomputed each request; stored only on this controller instance.
     */
    private function deanRoleId(): ?int
    {
        if ($this->deanRoleId !== null) {
            return $this->deanRoleId;
        }
        $rid = $this->model->findRoleIdByName('dean'); // expects LOWER(name) match in the model
        $this->deanRoleId = $rid;
        return $rid;
    }

    /**
     * Render the Accounts list.
     *
     * @return string HTML for the module region
     */
    public function index(): string
    {
        // RBAC: view list
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], 'AccountViewing');

        // Search & paging
        $rawQ   = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $search = ($rawQ !== null && $rawQ !== '') ? mb_strtolower($rawQ) : null;

        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = max(1, (int)(defined('UI_PER_PAGE_DEFAULT') ? UI_PER_PAGE_DEFAULT : 10));
        $offset  = ($page - 1) * $perPage;

        $result = $this->model->getUsersPage($search, $perPage, $offset);
        $users  = $result['rows'];
        $total  = $result['total'];

        // Global paginator contract
        $pager = [
            'total'   => $total,
            'pg'      => $page,
            'perpage' => $perPage,
            'baseUrl' => BASE_PATH . '/dashboard?page=accounts',
            'query'   => $rawQ,
            'from'    => $total > 0 ? (($page - 1) * $perPage + 1) : 0,
            'to'      => $total > 0 ? min($total, $page * $perPage) : 0,
        ];

        // Action gating from ModuleRegistry
        $registry = require dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        $actions  = $registry['accounts']['actions'] ?? [];

        $rbac = new RBAC($this->db);
        $uid  = (string)$_SESSION['user_id'];

        $canCreate = !empty($actions['create']) && $rbac->has($uid, $actions['create']);
        $canEdit   = !empty($actions['edit'])   && $rbac->has($uid, $actions['edit']);
        $canDelete = !empty($actions['delete']) && $rbac->has($uid, $actions['delete']);

        // Dropdown data
        $roles        = $this->model->getAllRoles();
        $colleges     = $this->model->getDepartments(true);   // is_college = TRUE
        $departments  = $this->model->getDepartments(false);  // is_college = FALSE

        // CSRF
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        $csrf = $_SESSION['csrf_token'];

        if (defined('APP_ENV') && APP_ENV === 'dev') {
            error_log("accounts.index total={$total} rows=" . count($users));
            if (!empty($users)) {
                error_log("accounts.index firstRow=" . json_encode($users[0], JSON_UNESCAPED_SLASHES));
            }
        }

        // Render view
        ob_start();
        // Make vars visible in the view:
        /** @var array  $users */
        /** @var array  $pager */
        /** @var bool   $canCreate */
        /** @var bool   $canEdit */
        /** @var bool   $canDelete */
        /** @var array  $roles */
        /** @var array  $colleges      is_college = TRUE */
        /** @var array  $departments   is_college = FALSE */
        /** @var string $csrf */
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    /**
     * Create user (POST from Create modal).
     */
    public function create(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], 'AccountCreation');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // CSRF check (form must post `csrf`)
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            FlashHelper::set('danger', 'Invalid CSRF token.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        $defaultPassword = 'password';

        // Collect & validate
        $id_no         = trim((string)($_POST['id_no']   ?? ''));
        $fname         = trim((string)($_POST['fname']   ?? ''));
        $mname         = trim((string)($_POST['mname']   ?? ''));
        $lname         = trim((string)($_POST['lname']   ?? ''));
        $email         = trim((string)($_POST['email']   ?? ''));
        $passwd        = (string)($_POST['password']     ?? $defaultPassword);
        $role_id       = (string)($_POST['role_id']      ?? '');
        $department_id = (string)($_POST['department_id'] ?? ''); // optional

        $errors = [];
        if ($id_no === '')                                   $errors[] = 'ID No is required.';
        if ($fname === '')                                   $errors[] = 'First name is required.';
        if ($lname === '')                                   $errors[] = 'Last name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($passwd === '' || strlen($passwd) < 6)           $errors[] = 'Password must be at least 6 characters.';
        if ($role_id === '')                                 $errors[] = 'Role is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // Hash password & insert
        $hash = PasswordHelper::hash($passwd);

        $ok = $this->model->createUser([
            'id_no'         => $id_no,
            'fname'         => $fname,
            'mname'         => ($mname === '') ? null : $mname,
            'lname'         => $lname,
            'email'         => $email,
            'password'      => $hash,
            'role_id'       => (int)$role_id,
            'department_id' => ($department_id === '') ? null : (int)$department_id,
        ]);

        if ($ok) {
            // If created as Dean and a college was selected, propagate to Departments module.
            $deanRid = $this->deanRoleId();
            if ($deanRid !== null && (int)$role_id === $deanRid && $department_id !== '') {
                try {
                    $this->assignments->setDepartmentDean((int)$department_id, $id_no);
                } catch (\Throwable $e) {
                    // Don’t fail the whole request; just flash a warning.
                    FlashHelper::set('warning', 'User created, but dean assignment could not be updated: ' . $e->getMessage());
                }
            }

            FlashHelper::set('success', 'User created successfully.');

            $currentAdminIdNo = (string)($_SESSION['user_id'] ?? '');
            $targetIdNo       = (string)$id_no;

            NotifyHelper::toUsers(
                array_filter([$currentAdminIdNo, $targetIdNo]),
                'Account created',
                'A new account has been created. ID No: ' . $targetIdNo,
                BASE_PATH . '/dashboard?page=accounts&q=' . urlencode($targetIdNo)
            );
        } else {
            FlashHelper::set('danger', 'Failed to create user. Make sure ID/email are unique.');
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
        exit;
    }

    /**
     * Edit user (POST from Edit modal).
     */
    public function edit(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], 'AccountModification');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // CSRF
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            FlashHelper::set('danger', 'Invalid CSRF token.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // Collect & validate
        $data = [
            'id_no'         => trim((string)($_POST['id_no'] ?? '')),
            'fname'         => trim((string)($_POST['fname'] ?? '')),
            'mname'         => trim((string)($_POST['mname'] ?? '')),
            'lname'         => trim((string)($_POST['lname'] ?? '')),
            'email'         => trim((string)($_POST['email'] ?? '')),
            'role_id'       => (string)($_POST['role_id'] ?? ''),
            'department_id' => (string)($_POST['department_id'] ?? ''), // optional
        ];

        $errs = [];
        if ($data['id_no'] === '')                                         $errs[] = 'Missing user ID.';
        if ($data['fname'] === '')                                         $errs[] = 'First name is required.';
        if ($data['lname'] === '')                                         $errs[] = 'Last name is required.';
        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errs[] = 'Valid email is required.';
        if ($data['role_id'] === '')                                       $errs[] = 'Role is required.';

        if ($errs) {
            FlashHelper::set('danger', implode(' ', $errs));
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // Normalize nullable
        if ($data['mname'] === '')             $data['mname'] = null;
        if ($data['department_id'] === '')     $data['department_id'] = null;

        $ok = $this->model->updateUserWithRoleDepartment($data);

        if ($ok) {
            // If edited as Dean and a college was selected, propagate to Departments module.
            $deanRid = $this->deanRoleId();
            if ($deanRid !== null && (int)$data['role_id'] === $deanRid && $data['department_id'] !== null) {
                try {
                    $this->assignments->setDepartmentDean((int)$data['department_id'], (string)$data['id_no']);
                } catch (\Throwable $e) {
                    FlashHelper::set('warning', 'User updated, but dean assignment could not be updated: ' . $e->getMessage());
                }
            }

            FlashHelper::set('success', 'User updated successfully.');

            $currentAdminIdNo = (string)($_SESSION['user_id'] ?? '');
            $targetIdNo       = (string)$data['id_no'];

            NotifyHelper::toUsers(
                array_filter([$currentAdminIdNo, $targetIdNo]),
                'Account updated',
                'Account with ID No: ' . $targetIdNo . ' was updated.',
                BASE_PATH . '/dashboard?page=accounts&q=' . urlencode($targetIdNo)
            );
        } else {
            FlashHelper::set('danger', 'Failed to update user.');
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
        exit;
    }

    /**
     * Delete user (POST from Delete modal).
     */
    public function delete(): void
    {
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], 'AccountDeletion');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // CSRF
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            FlashHelper::set('danger', 'Invalid CSRF token.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        $id_no = trim((string)($_POST['id_no'] ?? ''));

        if ($id_no === '') {
            FlashHelper::set('danger', 'Missing user ID.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // Optional guard: prevent deleting your own account
        if (!empty($_SESSION['user_id']) && trim((string)$_SESSION['user_id']) === $id_no) {
            FlashHelper::set('danger', 'You cannot delete your own account while logged in.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        $ok = $this->model->deleteUser($id_no);

        if ($ok) {
            FlashHelper::set('success', 'User deleted successfully.');

            $currentAdminIdNo = (string)($_SESSION['user_id'] ?? '');
            $targetIdNo       = (string)$id_no;

            NotifyHelper::toUsers(
                [$currentAdminIdNo],
                'Account deleted',
                'Account ' . $targetIdNo . ' was deleted.',
                BASE_PATH . '/dashboard?page=accounts&q=' . urlencode($targetIdNo)
            );
        } else {
            FlashHelper::set('danger', 'Failed to delete user.');
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
        exit;
    }
}
