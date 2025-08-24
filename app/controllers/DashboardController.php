<?php
// root/app/controllers/DashboardController.php
declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\StorageInterface;
use App\Models\UserModel;
use App\Helpers\FlashHelper;

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
        
        // 3. Load available modules
        $modules = $this->modules;

        // Prepare debug vars (optional)
        $controllerClass = null;
        $controller      = null;
        $test1 = '';
        $test2 = '';

        $contentHtml = '';
        if (isset($modules[$requestedPage])) {
            $controllerClass = $modules[$requestedPage]['controller'] ?? null;
            $test1 = 'The requested page exists';

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

        // 4. Load requested module controller (if exists)
        $contentHtml = '';
        if (isset($modules[$requestedPage])) {
            $controllerClass = $modules[$requestedPage]['controller'] ?? null;

            // try requiring the file manually before checking
            /*
            $controllerFile = __DIR__ . '/' . basename($requestedPage) . 'Controller.php';
            if (is_file($controllerFile)) {
                require_once $controllerFile;
            }
            */

            $test1 = 'The requested page exists';

            if (class_exists($controllerClass)) {
                $test2 = 'Module controller exists';
                $controller = new $controllerClass($this->db);
                $contentHtml = $controller->index(); // module’s index returns rendered HTML
            } else {
                $contentHtml = '<h3>Module Controller not found</h3>';
            }
        } else {
            $contentHtml = '<h3>404 - Page Not Found</h3>';
        }

        /*
        // 2. Get user permissions for sidebar
        $permissionGroups = $this->db->getPermissionGroupsByUser($_SESSION['user_id']);

        // Get user role for RBAC checks
        $role_id = $user['role_id'] ?? null; // Might need a new role Model...

        // 3. Map sidebar labels to their corresponding page names
         // Load mapper
        $mapper = require __DIR__ . '/../config/PageMapper.php';

        // pick requested page config
        $pageConfig = $mapper[$requestedPage] ?? null;

        if (!$pageConfig) {
            $pageContent = __DIR__ . '/../views/404.php';
            $pageCss = null;
            $pageJs = null;
            $pagePermissions = [];
        } else {
            $pageContent = __DIR__ . '/../views/pages/' . $pageConfig['page'] . '/index.php';
            $pageCss = $pageConfig['css'];
            $pageJs = $pageConfig['js'];
            $pagePermissions = $pageConfig['permissions'];
        }

        // If files don’t exist, set them to null
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $pageCss)) {
            $pageCss = null;
        }
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $pageJs)) {
            $pageJs = null;
        }

        // Later on, replace with permissions fetched by role_id
        $permissionGroups = $this->userModel->getRolePermissions($role_id);
*/

        // 5. Get flash messages
        $flashMessage = FlashHelper::get();

        // Make data visible to layout:
        $viewData = [
            'modules'         => $modules,
            'contentHtml'     => $contentHtml,
            'flashMessage'    => $flashMessage,
            // optional debug
            'controllerClass' => $controllerClass,
            'controller'      => $controller,
            'test1'           => $test1,
            'test2'           => $test2,
        ];
        extract($viewData, EXTR_SKIP);

        // 6. Render the layout
        require dirname(__DIR__) . '/views/layouts/DashboardLayout.php';
    }
}
