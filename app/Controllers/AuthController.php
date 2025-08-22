<?php
// root/app/controllers/AuthController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\StorageInterface;
use App\Models\UserModel;
use App\Helpers\FlashHelper;

class AuthController
{
    private StorageInterface $db;
    private $userModel;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
    }

    /**
     * Show login form OR handle login POST
     */
    public function login(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $usernameOrEmail = trim($_POST['username'] ?? ''); // Form field is called username
            $password = trim($_POST['password'] ?? '');

            if ($username === '' || $password === '') {
                FlashHelper::set('danger', 'Username and password are required.');
                header("Location: " . BASE_PATH . "/login");
                exit;
            }

            // Let UserModel do the heavy lifting (verifies + optional rehash)
            $user = $this->userModel->authenticate($usernameOrEmail, $password);

            if ($user !== false) {
                // valid login
                $_SESSION['user_id'] = $user['id_no'];
                $_SESSION['username'] = $user['username'] ?? ($user['email'] ?? '');
                $_SESSION['role_id']  = $user['role_id'] ?? null;

                FlashHelper::set('success', 'Welcome back, ' . htmlspecialchars($user['fname'] ?? 'user'));
                header('Location: ' . BASE_PATH . '/dashboard');
                exit;
            } else {
                FlashHelper::set('danger', 'Invalid username or password.');
                header('Location: ' . BASE_PATH . '/login');
                exit;
            }
        }

        // GET request to show login view
        require __DIR__ . '/../views/login.php';
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
