<?php
// /app/Modules/Auth/Controllers/AuthController.php
declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Interfaces\StorageInterface;
use App\Models\UserModel;
use App\Helpers\FlashHelper;

final class AuthController
{
    private StorageInterface $db;
    private UserModel $userModel;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
        if (session_status() !== \PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * GET: show login form
     * POST: authenticate and redirect
     */
    public function login(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST') {
            // CSRF check
            $token    = (string)($_POST['csrf'] ?? '');
            $expected = (string)($_SESSION['csrf_token_login'] ?? '');

            if ($token === '' || $expected === '' || !hash_equals($expected, $token)) {
                \App\Helpers\FlashHelper::set('danger', 'Your session expired. Please try again.');
                header('Location: ' . BASE_PATH . '/login');
                exit;
            }
            unset($_SESSION['csrf_token_login']); // one-time token

            $usernameOrEmail = trim((string)($_POST['email'] ?? '')); // Change this if you are also using username
            $password        = (string)($_POST['password'] ?? '');

            if ($usernameOrEmail === '' || $password === '') {
                FlashHelper::set('danger', 'Email and password are required.');
                header("Location: " . BASE_PATH . "/login");
                exit;
            }

            // UserModel (verifies + optional rehash)
            $user = $this->userModel->authenticate($usernameOrEmail, $password);

            if ($user !== false) {
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

        // GET ensure CSRF token exists and show login view
        $_SESSION['csrf_token_login'] = bin2hex(random_bytes(32));
        // Legacy: require dirname(__DIR__, 3) . '/Views/login.php';
        require __DIR__ . '/../Views/login.php';
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header("Location: " . BASE_PATH . "/login");
        exit;
    }
}
