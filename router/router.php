<?php
// router.router.php

    // Load environment variables
    //require_once __DIR__ . '/../config/config.php';
    echo "router.php:  basepath1: $basePath <br>";
    function route($path){
        // Set up base path
        $basePath = BASE_PATH; // from .env

        // Define routes that don't require login
        $publicRoutes = ['/public/login.php','/auth'];

        echo "router.php: basepath2: $basePath <br>";

        // If user is not logged in AND trying to access a private route
        if (!in_array($path, $publicRoutes) && empty($_SESSION['user_id'])){
            echo "router.php: public check: $path <br>";
            echo "router.php: redirect: $basePath/app/views/login.php <br>";
            // Redirect to login
            header("Location: $basePath/app/views/login.php");
            exit;
        }
        switch ($path) {
            //login page
            case '/login':
                require_once __DIR__ . '/../app/views/login.php';
                break;
            // login authentication
            case '/auth':
                require_once __DIR__ . '/../app/lib/UserController.php';
                break;
            // dashboard page
            case '/dashboard':
                require_once __DIR__ . '/../app/views/dashboard.php';
                break;
            // accounts management page
            case '/accounts':
                require_once __DIR__ . '/../app/views/accounts.php';
                break;
            case '/public/index.php': // Automatically redirects to login page
            case '/': //Default path (homepage)
            case '': // Handles edge cases
                echo "router.php: path: $path <br>";
                if ($path !== '/login') {
                    echo "router.php: loginpath: $path <br>";
                    echo "router.php: basePath: $basePath <br>";
                    header("Location: $basePath/app/views/login.php");
                    exit;
                }
                break;              
            default:
                http_response_code(404);
                echo "router.php: 404 - Page not found <br>";
                break;
        }
    }

?>