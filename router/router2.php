<?php
    // root/router/router.php

    use App\Controllers\DashboardController;
    use App\Controllers\AuthController;

    // Define route maps    
    $publicRoutes = [
        '/'        => [AuthController::class, 'login'],
        '/login'   => [AuthController::class, 'login'],
        '/logout'  => [AuthController::class, 'logout'],
    ];

    $privateRoutes = [
        '/dashboard' => [DashboardController::class, 'render'],
        // add more private routes here
    ];
    
    function route(string $uri, $db)
    {
        $path = parse_url($uri, PHP_URL_PATH);

        global $publicRoutes, $privateRoutes;

        // 1. Check public routes
        if (isset($publicRoutes[$path])) {
            [$controller, $method] = $publicRoutes[$path];
            return (new $controller($db))->$method();
        }

        // 2. Check private routes (require login)
        if (isset($privateRoutes[$path])) {
            if (empty($_SESSION['user_id'])) {
                header("Location: " . BASE_PATH . "/login");
                exit;
            }

            [$controller, $method] = $privateRoutes[$path];
            $page = $_GET['page'] ?? 'dashboard';
            return (new $controller($db))->$method($page);
        }

        // 3. 404 fallback
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
    }