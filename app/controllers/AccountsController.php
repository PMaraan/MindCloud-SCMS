<?php
// root/app/controllers/AccountsController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\StorageInterface;
use App\Models\AccountsModel;
use App\Helpers\FlashHelper;
use App\Helpers\PasswordHelper;

final class AccountsController {
    private AccountsModel $model;

    public function __construct(StorageInterface $db) {
        $this->model = new AccountsModel($db);
    }

    /**
     * List users + show create modal.
     */
    public function index(): string {
        $search  = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $page    = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = 10; // tweak as you wish

        $offset  = ($page - 1) * $perPage;
        $result  = $this->model->getUsersPage($search, $perPage, $offset);

        $users   = $result['rows'];
        $total   = $result['total'];
        $pages   = max(1, (int)ceil($total / $perPage));
        
        // Build a tiny pager struct the view can use
        $pager = [
            'page'     => $page,
            'perPage'  => $perPage,
            'total'    => $total,
            'pages'    => $pages,
            'hasPrev'  => $page > 1,
            'hasNext'  => $page < $pages,
            'prev'     => max(1, $page - 1),
            'next'     => min($pages, $page + 1),
            'baseUrl'  => BASE_PATH . '/dashboard?page=accounts', // keep your module route
            'query'    => $search, // so we can preserve ?q=
        ];

        // Permission flags for RBAC
        $canEdit = true;
        $canDelete = true;

        // Create modal dropdown data
        $roles    = $this->model->getAllRoles();     // role_id, role_name
        $colleges = $this->model->getAllColleges();  // college_id, short_name

        // CSRF token (simple)
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        $csrf = $_SESSION['csrf_token'];

        // Render view and return HTML (keeps DashboardController flow)
        ob_start();
        // Make vars visible in the view:
        /** @var array $users */
        /** @var array $pager */
        /** @var bool $canEdit */
        /** @var bool $canDelete */
        require dirname(__DIR__) . '/views/pages/accounts/index.php';
        return (string)ob_get_clean();
    }

    /**
     * Create user (POST from Create modal).
     */
    public function create(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // CSRF check
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            FlashHelper::set('danger', 'Invalid CSRF token.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        $defaultPassword = 'password'; // you may choose to set a default password
        // Collect & validate
        $id_no   = trim((string)($_POST['id_no']   ?? ''));
        $fname   = trim((string)($_POST['fname']   ?? ''));
        $mname   = trim((string)($_POST['mname']   ?? ''));
        $lname   = trim((string)($_POST['lname']   ?? ''));
        $email   = trim((string)($_POST['email']   ?? ''));
        $passwd  = (string)($_POST['password']     ?? $defaultPassword);
        $role_id = (string)($_POST['role_id']      ?? '');
        $college_id = (string)($_POST['college_id'] ?? '');

        $errors = [];
        if ($id_no === '')   $errors[] = 'ID No is required.';
        if ($fname === '')   $errors[] = 'First name is required.';
        if ($lname === '')   $errors[] = 'Last name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($passwd === '' || strlen($passwd) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($role_id === '') $errors[] = 'Role is required.';

        if ($errors) {
            FlashHelper::set('danger', implode(' ', $errors));
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // Hash password
        $hash = PasswordHelper::hash($passwd);

        // Insert (users + user_roles) in a transaction
        $ok = $this->model->createUser([
            'id_no'      => $id_no,
            'fname'      => $fname,
            'mname'      => ($mname !== '' ? $mname : null),
            'lname'      => $lname,
            'email'      => $email,
            'password'   => $hash,
            'role_id'    => (int)$role_id,
            'college_id' => ($college_id !== '' ? (int)$college_id : null),
        ]);

        if ($ok) {
            FlashHelper::set('success', 'User created successfully.');
        } else {
            FlashHelper::set('danger', 'Failed to create user. Make sure ID/email are unique.');
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
        exit;
    }

    /**
     * Handle edit user action.
     */
    public function edit(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header("Location: " . BASE_PATH . "/dashboard?page=accounts");
            exit;
        }

        // CSRF
        $token = $_POST['csrf'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            FlashHelper::set('danger', 'Invalid CSRF token.');
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // Collect fields from POST
        $data = [
            'id_no'      => trim((string)($_POST['id_no'] ?? '')),
            'fname'      => trim((string)($_POST['fname'] ?? '')),
            'mname'      => trim((string)($_POST['mname'] ?? '')),
            'lname'      => trim((string)($_POST['lname'] ?? '')),
            'email'      => trim((string)($_POST['email'] ?? '')),
            'role_id'    => (string)($_POST['role_id'] ?? ''),
            'college_id' => (string)($_POST['college_id'] ?? ''),
        ];

        // Validate
        $errs = [];
        if ($data['id_no'] === '') $errs[] = 'Missing user ID.';
        if ($data['fname'] === '') $errs[] = 'First name is required.';
        if ($data['lname'] === '') $errs[] = 'Last name is required.';
        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errs[] = 'Valid email is required.';
        if ($data['role_id'] === '') $errs[] = 'Role is required.';
        if ($errs) {
            FlashHelper::set('danger', implode(' ', $errs));
            header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
            exit;
        }

        // Normalize nullable mname/college
        if ($data['mname'] === '') $data['mname'] = null;
        if ($data['college_id'] === '') $data['college_id'] = null;

        // Update
        // Later: check "edit_accounts" permission here
        $ok = $this->model->updateUserWithRoleCollege($data);

        if ($ok) {
            FlashHelper::set('success', 'User updated successfully.');
            // (Future hook: if role/college changed, enqueue domain updates here.)
        } else {
            FlashHelper::set('danger', 'Failed to update user.');
        }

        header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
        exit;
    }

    /**
     * Handle delete user action.
     */
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header("Location: " . BASE_PATH . "/dashboard?page=accounts");
            exit;
        }

        // TODO: implement delete in AccountsModel
        FlashHelper::set('danger', 'Delete not implemented yet.');
        header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
        exit;

        $id_no = $_POST['id_no'] ?? null;

        // Later: check "delete_accounts" permission here

        if ($id_no && $this->model->deleteUser($id_no)) {
            FlashHelper::set('success', 'User deleted successfully.');
        } else {
            FlashHelper::set('danger', 'Failed to delete user.');
        }

        header("Location: " . BASE_PATH . "/dashboard?page=accounts");
        exit;
    }
}
