<?php
// root/app/controllers/DashboardController.php
namespace App\Controllers;
use FlashHelper;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



require_once __DIR__ . '/../helpers/FlashHelper.php';

class DashboardController
{
    private $db;
    private $userModel;
    private $modules;

    public function __construct($db) {
        $this->db = $db;
        require_once __DIR__ . '/../models/UserModel.php';
        $this->userModel = new \UserModel($db);

        // Load module registry
        $registryPath = __DIR__ . '/../../config/ModuleRegistry.php';
        if (is_file($registryPath)) {
            $this->modules = require $registryPath;
        } else {
            $this->modules = [];
            error_log('ModuleRegistry not found at: ' . $registryPath);
        }

    }
    
    /**
     * Render the dashboard layout with dynamic content, CSS, and JS.
     *
     * @param string $page The page name relative to /views/pages/
     */
    public function render(string $requestedPage = 'dashboard')
    {
        // 1. Authentication check
        if (empty($_SESSION['user_id'])) {
            \FlashHelper::set('danger', 'You must log in first.');
            header("Location: " . BASE_PATH ."/login");
            exit;
        }

        // 2. Load user profile
        $user = $this->userModel->getUserProfile($_SESSION['user_id']);
        if (!$user) {
            \FlashHelper::set('danger', 'Account not found.');
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

        // 4. Load requested module controller (if exists)
        $contentHtml = '';
        if (isset($modules[$requestedPage])) {
            $controllerClass = $modules[$requestedPage]['controller'];
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass($this->db);
                $contentHtml = $controller->index(); // module’s index returns rendered HTML
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

        // 6. Render the layout
        require __DIR__ . '/../views/layouts/DashboardLayout.php';
    }
}
