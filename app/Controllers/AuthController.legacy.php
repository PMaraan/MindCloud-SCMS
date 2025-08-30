<?php
// /app/Controllers/AuthController.php
declare(strict_types=1);

namespace App\Controllers\Legacy; // added legacy to avoid namespace collisions

use App\Interfaces\StorageInterface;
use App\Models\UserModel;
use App\Helpers\FlashHelper;
// use App\Security\RBAC; // optional preload

final class AuthController
{
    private StorageInterface $db;
    private UserModel $userModel;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * GET: show login form
     * POST: authenticate and redirect
     */
    public function login(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $usernameOrEmail = trim((string)$_POST['email'] ?? ''); // Form field is called email
            $password = (string)($_POST['password'] ?? '');

            if ($usernameOrEmail === '' || $password === '') {
                FlashHelper::set('danger', 'Email and password are required.');
                header("Location: " . BASE_PATH . "/login");
                exit;
            }

            // Let UserModel do the heavy lifting (verifies + optional rehash)
            $user = $this->userModel->authenticate($usernameOrEmail, $password);

            if ($user !== false) {
                // valid login
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id_no'];

                FlashHelper::set('success', 'Welcome back, ' . htmlspecialchars($user['fname'] ?? 'user'));
                header('Location: ' . BASE_PATH . '/dashboard');
                exit;
            } else {
                FlashHelper::set('danger', 'Invalid username or password.');
                header('Location: ' . BASE_PATH . '/login');
                exit;
            }
        }

        // If server request type is GET then show login view
        require dirname(__DIR__) . '/views/login.php';
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        session_destroy();
        header("Location: " . BASE_PATH . "/login");
        exit;
    }
}
