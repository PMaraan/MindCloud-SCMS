<?php
// router.router.php

    // Load environment variables
    require_once __DIR__ . '/../config/config.php';

    function route($path){
        // Set up base path
        $basePath = BASE_PATH; // from .env

        // Define routes that don't require login
        $publicRoutes = ['/public/login.php','/auth'];

        // If user is not logged in AND trying to access a private route
        if (!in_array($path, $publicRoutes) && empty($_SESSION['user_id'])){
            echo "public check: $path <br>";
            echo "redirect: $basePath/app/views/login.php";
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
            case '/index.php': // Automatically redirects to login page
            case '/': //Default path (homepage)
            case '': // Handles edge cases
                echo "path: $path";
                if ($path !== '/login') {
                    echo "loginpath: $path";
                    //header("Location: $basePath/login.php");
                    //exit;
                }
                break;              
            default:
                http_response_code(404);
                echo "404 - Page not found";
                break;
        }
    }

?>