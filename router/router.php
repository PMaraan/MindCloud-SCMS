<?php
    // /Router/router.php

    use App\Controllers\DashboardController;
    use App\Modules\Auth\Controllers\AuthController;
    use App\Interfaces\StorageInterface;
    use App\Modules\Notifications\Controllers\NotificationsController;
    use App\Modules\Settings\Controllers\SettingsController;

    /**
     * Build an absolute URL from BASE_PATH and a path.
     */
    function url(string $path = '/'): string {
        $p = '/' . ltrim($path, '/');
        $base = BASE_PATH; // normalized in config.php (no trailing slash, may be '')
        return ($base === '' ? '' : $base) . $p;
    }

    /**
     * Strip BASE_PATH from the request path and normalize slashes.
     */
    function normalize_path(string $uri): string {
        $rawPath = parse_url($uri, PHP_URL_PATH) ?? '/';
        // If app is under a subfolder, remove that prefix
        if (BASE_PATH !== '' && str_starts_with($rawPath, BASE_PATH)) {
            $rawPath = substr($rawPath, strlen(BASE_PATH)) ?: '/';
        }
        // Normalize to leading slash, no trailing slash (except root)
        $path = '/' . ltrim($rawPath, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    // Route maps by HTTP method
    $publicRoutes = [
        'GET' => [
            '/'       => [AuthController::class, 'login'],    // show login
            '/login'  => [AuthController::class, 'login'],    // show login
        ],
        'POST' => [
            '/login'  => [AuthController::class, 'login'],    // handle login submit
            '/logout' => [AuthController::class, 'logout'],
        ],
    ];

    $privateRoutes = [
        'GET' => [
            '/dashboard' => [DashboardController::class, 'render'],
            '/notifications/latest' => [NotificationsController::class, 'latestJson'],
            '/notifications/unread-count' => [NotificationsController::class, 'unreadCountJson'],
            '/api/settings/get'  => [SettingsController::class, 'getPreference'],
            // add more GET private routes here
        ],
        'POST' => [
            '/dashboard' => [DashboardController::class, 'render'],
            '/notifications/mark-read' => [NotificationsController::class, 'markReadJson'],
            '/api/settings/save' => [SettingsController::class, 'savePreference'],
            // add POST private routes here
        ],
    ];
    
    /**
     * Simple dispatcher.
     *
     * @param string $uri
     * @param mixed  $db
     * @param string $method
     */
    function route(string $uri, StorageInterface $db, string $method = 'GET'): void
    {
        $method = strtoupper($method);
        $path = normalize_path($uri);

        global $publicRoutes, $privateRoutes;

        // 1) Public routes
        if (isset($publicRoutes[$method][$path])) {
            [$controller, $action] = $publicRoutes[$method][$path];
            (new $controller($db))->$action();
            return;
        }

        // 2) Private routes (require login)
        if (isset($privateRoutes[$method][$path])) {
            if (empty($_SESSION['user_id'])) {
                header('Location: ' . url('/login'));
                exit;
            }
            [$controller, $action] = $privateRoutes[$method][$path];
            // preserve your page param convention
            $page = $_GET['page'] ?? 'dashboard';
            (new $controller($db))->$action($page);
            return;
        }

        // 3) Method not allowed? (path exists under other method)
        $allPaths = array_merge(array_keys($publicRoutes['GET'] + $privateRoutes['GET']), array_keys($publicRoutes['POST'] + $privateRoutes['POST']));
        if (in_array($path, $allPaths, true)) {
            http_response_code(405);
            echo '<h1>405 Method Not Allowed</h1>';
            return;
        }

        // 4) 404
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
    }