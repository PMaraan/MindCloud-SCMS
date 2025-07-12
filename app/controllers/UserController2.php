<?php
// root/app/controllers/UserController.php

require_once __DIR__ . '/../models/StorageInterface.php';
require_once __DIR__ . '/../models/PostgresDatabase.php';
require_once __DIR__ . '/../models/MockDatabase.php';

class UserController {
    private $db;

    public function __construct() {
        $this->db = USE_MOCK
            ? new MockDatabase()
            : new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    }

    public function login($email, $password) {
        $user = $this->db->authenticate($email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id_no'];
            $_SESSION['username'] = $user['fname'] . " " . $user['lname'];
            header("Location: " . BASE_PATH . "/dashboard");
            exit;
        } else {
            echo "<script>alert('Invalid credentials'); window.location='" . BASE_PATH . "/login';</script>";
        }
    }

    public static function logout() {
        session_unset();
        session_destroy();
        header("Location: " . BASE_PATH . "/login");
        exit;
    }

    public static function requireLogin() {
        if (empty($_SESSION['user_id'])) {
            header("Location: " . BASE_PATH . "/login");
            exit;
        }
    }
}