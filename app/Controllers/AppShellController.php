<?php
// /app/Controllers/AppShellController.php
/**
 * AppShellController
 *
 * Formerly "DashboardController". Hosts the main application shell:
 * - Topbar
 * - Sidebar (role-aware via ModuleRegistry)
 * - Main content slot (modules inject HTML into this area)
 *
 * This rename avoids confusion with the future "Dashboard" analytics module.
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Interfaces\StorageInterface;
use App\Models\UserModel;
use App\Helpers\FlashHelper;
use App\Security\RBAC;

final class AppShellController
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
     * Render the app shell layout and dynamic module content.
     * The active module is resolved via ?page=... and config/ModuleRegistry.php.
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
        $requestedPage = $currentPage;

        // Build sidebar-safe list
        $visibleModules = [];
        foreach ($modules as $key => $def) {
            $perm = $def['permission'] ?? null;
            if (!$perm || $rbac->has($userId, $perm)) {
                $visibleModules[$key] = $def;
            }
        }

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

            if ($controllerClass === null) {
                // Registry placeholder (e.g., "dashboard" landing)
                $contentHtml = $this->renderLandingContent($username, $displayRole);
            } elseif (class_exists($controllerClass)) {
                $test2 = 'Module controller exists';
                $controller = new $controllerClass($this->db);

                // Action dispatch (query param ?action=)
                $action  = strtolower((string)($_GET['action'] ?? 'index'));
                $allowed = [
                    'index', 'create', 'edit', 'delete',
                    'duplicate',          // <-- add
                    'savemeta', 'snapshot', 'opentemplate',
                    'programs',           // existing JSON (by department)
                    'apiprograms',        // <-- add (alias-safe JSON name)
                    'apicourses'          // <-- add (JSON courses by program)
                ];

                $actionMap = [
                    'index'        => 'index',
                    'create'       => 'create',
                    'edit'         => 'edit',
                    'delete'       => 'delete',
                    'duplicate'    => 'duplicate',    // <-- add
                    'savemeta'     => 'saveMeta',
                    'snapshot'     => 'snapshot',
                    'opentemplate' => 'openTemplate',
                    'programs'     => 'programs',     // existing JSON (by department)
                    'apiprograms'  => 'apiPrograms',  // <-- add (method in SyllabusTemplatesController)
                    'apicourses'   => 'apiCourses'    // <-- add (method in SyllabusTemplatesController)
                ];
                $method = $actionMap[$action] ?? 'index';

                if (!in_array($action, $allowed, true)) {
                    $action = 'index';
                }

                // Actions that RETURN HTML to embed inside the app shell
                $renderActions = ['index', 'opentemplate'];

                if (in_array($action, $renderActions, true)) {
                    $contentHtml = $controller->{$method}();
                } else {
                    // Mutating actions should handle redirect/flash themselves
                    $controller->{$method}();
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
        require dirname(__DIR__) . '/Views/layouts/AppShellLayout.php';
    }

    /**
     * Default landing content for the app shell when no module is selected.
     * Keep it minimal – you can swap this for a small include later if needed.
     */
    private function renderLandingContent(string $username, string $displayRole): string
    {
        $safeUser  = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeRole  = htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <div class="card shadow-sm">
            <div class="card-body">
            <h5 class="card-title mb-2">Welcome, {$safeUser}</h5>
            <p class="text-muted mb-4">{$safeRole}</p>
            <p class="mb-0">Use the sidebar to select a module. Your permissions determine which modules appear here.</p>
            </div>
        </div>
        HTML;
    }

}
