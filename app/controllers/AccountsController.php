<?php
// root/app/controllers/AccountsController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\StorageInterface;
use App\Models\AccountsModel;
use App\Helpers\FlashHelper;

final class AccountsController {
    private AccountsModel $model;

    public function __construct(StorageInterface $db) {
        $this->model = new AccountsModel($db);
    }

    /**
     * Show Accounts page (list of users).
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
     * Handle edit user action.
     */
    public function edit(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header("Location: " . BASE_PATH . "/dashboard?page=accounts");
            exit;
        }

        // TODO: implement update in AccountsModel
        FlashHelper::set('danger', 'Edit not implemented yet.');
        header('Location: ' . BASE_PATH . '/dashboard?page=accounts');
        exit;

        // Collect fields from POST
        $data = [
            'id_no'   => $_POST['id_no'],
            'fname'   => $_POST['fname'],
            'mname'   => $_POST['mname'] ?? null,
            'lname'   => $_POST['lname'],
            'email'   => $_POST['email']
        ];

        // Later: check "edit_accounts" permission here

        if ($this->model->updateUser($data)) {
            FlashHelper::set('success', 'User updated successfully.');
        } else {
            FlashHelper::set('danger', 'Failed to update user.');
        }

        header("Location: " . BASE_PATH . "/dashboard?page=accounts");
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
