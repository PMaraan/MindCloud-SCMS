<?php
    // root/router/router.php

    function route($path, $db){
        // Set up base path from config (inherited from root/public/index.php)
        $basePath = BASE_PATH;

        // Define routes that don't require login
        $publicRoutes = [
            '/'             => __DIR__ . '/../app/views/login.php',
            '/login'        => __DIR__ . '/../app/views/login.php',
            '/auth'         => __DIR__ . '/../app/controllers/UserController.php',
            '/templatebuilder' => __DIR__ . '/../app/views/TemplateBuilder.php'
        ];

        //check if path exists in public routes
        if (isset($publicRoutes[$path])) {
            require $publicRoutes[$path];
            return;
        }

        // Private dashboard routes
        // The array values are just module names
        // The real address mappings are in dashboard controller
        $dashboardRoutes = [
            '/dashboard' => 'dashboard',
            '/accounts'  => 'accounts',
            '/programs'  => 'programs'
            // Add more pages here...
        ];

        if (isset($dashboardRoutes[$path])) {
            if (empty($_SESSION['user_id'])) {
                header("Location: {$basePath}/login");
                exit;
            }

            // Role/permission checks:
            //if (!userHasPermission($_SESSION['role'], $path)) {
            //    require __DIR__ . '/../app/views/404.php';
            //    exit;
            //}

            require_once __DIR__ . '/../app/controllers/DashboardController.php';
            $controller = new DashboardController($db);
            $controller->render($dashboardRoutes[$path]);
            return;
        }

        // Not found -> 404
        http_response_code(404);
        require __DIR__ . '/../app/views/404.php';
    }

?>