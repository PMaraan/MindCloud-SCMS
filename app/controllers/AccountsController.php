<?php
// root/app/controllers/AccountsController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/FlashHelper.php';
require_once __DIR__ . '/../models/AccountsModel.php';

class AccountsController {
    private $model;

    public function __construct($db) {
        $this->model = new AccountsModel($db);
    }

    /**
     * Show Accounts page (list of users).
     */
    public function index() {
        $search = $_GET['q'] ?? null;
        $users = $this->model->getAllUsers($search);

        // Later: check if user has "view_accounts" permission
        // if (!in_array('view_accounts', $_SESSION['permissions'])) { ... }

        require __DIR__ . '/../views/pages/accounts/index.php';
    }

    /**
     * Handle edit user action.
     */
    public function edit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header("Location: " . BASE_PATH . "/dashboard?page=accounts");
            exit;
        }

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
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            FlashHelper::set('danger', 'Invalid request.');
            header("Location: " . BASE_PATH . "/dashboard?page=accounts");
            exit;
        }

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
