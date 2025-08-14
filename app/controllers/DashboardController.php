<?php
// root/app/controllers/DashboardController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: " . BASE_PATH ."/login");
    exit;
}

// require_once __DIR__ . '/../bootstrap.php'; // Gives us $db from DatabaseFactory
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../helpers/FlashHelper.php';

class DashboardController
{
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
    }
    
    /**
     * Render the dashboard layout with dynamic content, CSS, and JS.
     *
     * @param string $page The page name relative to /views/pages/
     */
    public function render(string $page = 'dashboard')
    {
        // 1. Load user profile
        $user = $this->userModel->getUserProfile($_SESSION['user_id']);

        $username = trim(
            $user['fname'] . ' ' . 
            (!empty($user['mname']) ? $user['mname'] . '. ' : '') . 
            $user['lname']
        );
        $displayRole = trim(($user['college_short_name'] ?? '') . ' ' . $user['role']); 
        
        // 2. Get user permissions for sidebar
        $permissionGroups = $this->db->getPermissionGroupsByUser($_SESSION['user_id']);

        // 3. Map sidebar labels to their corresponding page names
        $mapper = [
            'Accounts'  => 'accounts/Accounts', // replace the name to {module}/index
            'Roles'     => 'roles/Roles',
            'Colleges'  => 'colleges/Colleges',
            'Faculty'   => 'faculty/Faculty',
            'Programs'  => 'programs/Programs',
            'Courses'   => 'courses/Courses',
            'Templates' => 'templates/Templates',
            'Syllabus'  => 'syllabus/AllSyllabus'
        ];

        // 4. Resolve the content file, CSS, and JS paths
        $pageContent = __DIR__ . '/../views/pages/' . $page . '/index.php'; // add a default address
        $pageCss     = '/public/assets/css/pages/' . $page . '.css';
        $pageJs      = '/public/assets/js/pages/' . $page . '.js';

        // If files don’t exist, set them to null
        if (!file_exists($pageContent)) {
            $pageContent = __DIR__ . '/../views/404.php';
            $pageCss = null;
            $pageJs = null;
        }
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $pageCss)) {
            $pageCss = null;
        }
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $pageJs)) {
            $pageJs = null;
        }

        // 5. Get flash messages
        $flashMessage = FlashHelper::get();

        // 6. Pass all data to the layout
        require __DIR__ . '/../views/layouts/DashboardLayout.php';
    }
}
