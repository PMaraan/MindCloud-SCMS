<?php
// root/app/controllers/DashboardController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\StorageInterface;
use App\Models\UserModel;
use App\Helpers\FlashHelper;
use App\Security\RBAC;

final class DashboardController
{
    private StorageInterface $db;
    private UserModel $userModel;
    private array $modules;

    public function __construct(StorageInterface $db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);

        // Load module registry
        $registryPath = dirname(__DIR__, 2) . '/config/ModuleRegistry.php';
        $this->modules = is_file($registryPath) ? require $registryPath : [];
        /*
        if (is_file($registryPath)) {
            $this->modules = require $registryPath;
        } else {
            $this->modules = [];
            error_log('ModuleRegistry not found at: ' . $registryPath);
        }
        */
    }
    
    /**
     * Render the dashboard layout with dynamic content, CSS, and JS.
     *
     * @param string $requestedPage The page name relative to /views/pages/
     */
    public function render(string $requestedPage = 'dashboard'): void {
        // 1. Authentication check
        if (empty($_SESSION['user_id'])) {
            FlashHelper::set('danger', 'You must log in first.');
            header("Location: " . BASE_PATH ."/login");
            exit;
        }

        // 2. Load user profile
        $user = $this->userModel->getUserProfile($_SESSION['user_id']);
        if (!$user) {
            FlashHelper::set('danger', 'Account not found.');
            session_destroy();
            header("Location: " . BASE_PATH . "/login");
            exit;
        }

        $username = trim(
            $user['fname'] . ' ' . 
            (!empty($user['mname']) ? $user['mname'] . '. ' : '') . 
            $user['lname']
        );
        $displayRole = trim(($user['college_short_name'] ?? '') . ' ' . $user['role_name']); 
        
        // 3. Modules & RBAC
        $modules = $this->modules;
        $rbac = new RBAC($this->db);
        $userId = (string)$_SESSION['user_id'];

        // Compute current page once (for sidebar active state, etc.)
        $currentPage = (isset($_GET['page']) && is_string($_GET['page']) && $_GET['page'] !== '')
            ? $_GET['page']
            : 'dashboard';

        // Build sidebar-safe list
        $visibleModules = [];
        foreach ($modules as $key => $def) {
            $perm = $def['permission'] ?? null;
            if (!$perm || $rbac->has($userId, $perm)) {
                $visibleModules[$key] = $def;
            }
        }

        /*
        // Prepare debug vars (optional)
        $controllerClass = null;
        $controller      = null;
        $test1 = '';
        $test2 = '';

        $contentHtml = '';
        if (isset($modules[$requestedPage])) {
            $controllerClass = $modules[$requestedPage]['controller'] ?? null;
            $requiredPerm    = $modules[$requestedPage]['permission'] ?? null;
            $test1 = 'The requested page exists'; // for debugging only. remove for production ...

            // RBAC check
            if ($requiredPerm) {
                $rbac->require((string)$_SESSION['user_id'], $requiredPerm);
            }

            if ($controllerClass && class_exists($controllerClass)) {
                $test2 = 'Module controller exists';
                $controller = new $controllerClass($this->db);
                $action = strtolower((string)($_GET['action'] ?? 'index'));
                $allowed = ['index','create','edit','delete']; // whitelist

                if (!in_array($action, $allowed, true)) {
                    $action = 'index';
                }
                if ($action === 'index') {
                    $contentHtml = $controller->index();
                } else {
                    // Actions are side-effect handlers that redirect; they don’t need to return HTML
                    $controller->{$action}();
                    return; // prevent falling through to layout after redirect
                }
            } else {
                $contentHtml = '<div class="alert alert-warning">Module controller not found.</div>';
            }
        } else {
            $contentHtml = '<div class="alert alert-danger">404 - Page Not Found</div>';
        }
// */

        // 4. Resolve requested module/controller
        $contentHtml     = '';
        $controllerClass = null;
        $controller      = null;

        // (Optional) debug strings for dev
        $test1 = '';
        $test2 = '';

        if (isset($modules[$requestedPage])) {
            $controllerClass = $modules[$requestedPage]['controller'] ?? null;
            $requiredPerm    = $modules[$requestedPage]['permission'] ?? null;
            $test1 = 'The requested page exists';

            // Gate module view with RBAC
            if ($requiredPerm) {
                $rbac->require($userId, $requiredPerm);
            }

            if ($controllerClass && class_exists($controllerClass)) {
                $test2 = 'Module controller exists';
                $controller = new $controllerClass($this->db);

                // Action dispatch (query param ?action=)
                $action  = strtolower((string)($_GET['action'] ?? 'index'));
                $allowed = ['index', 'create', 'edit', 'delete'];

                if (!in_array($action, $allowed, true)) {
                    $action = 'index';
                }

                if ($action === 'index') {
                    // Render HTML content for the region
                    $contentHtml = $controller->index();
                } else {
                    // Mutating actions should handle redirect/flash themselves
                    $controller->{$action}();
                    return; // prevent rendering layout after redirect
                }
            } else {
                $contentHtml = '<div class="alert alert-warning">Module controller not found.</div>';
            }
        } else {
            $contentHtml = '<div class="alert alert-danger">404 - Page Not Found</div>';
        }

        // 5. Get flash messages
        $flashMessage = FlashHelper::get();

        // 6. Make data visible to layout:
        $viewData = [
            'currentPage'     => $currentPage,
            'modules'         => $modules,
            'visibleModules'  => $visibleModules,
            'contentHtml'     => $contentHtml,
            'flashMessage'    => $flashMessage,
            'username'        => $username,
            'displayRole'     => $displayRole,
            // Debug (use in dev only)
            'controllerClass' => $controllerClass,
            'controller'      => $controller,
            'test1'           => $test1,
            'test2'           => $test2,
        ];
        extract($viewData, EXTR_SKIP);

        // 7. Render the layout
        require dirname(__DIR__) . '/views/layouts/DashboardLayout.php';
    }
}
