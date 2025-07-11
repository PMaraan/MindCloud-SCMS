<?php
// router.router.php

    function route($path){
        // Define routes that don't require login
        $publicRoutes = ['login','/auth'];

        // If user is not logged in AND trying to access a private route
        if (!in_array($path, $publicRoutes) && empty($_SESSION['user_id'])){
            // Redirect to login
            header('Location: /login');
            exit;
        }
        switch ($path) {
            //login page
            case '/login':
                require_once __DIR__ . '/../app/views/login.php';
                break;
            // login authentication
            case '/auth':
                require_once __DIR__ . '/../app/lib/login_auth.php';
                break;
            // dashboard page
            case '/dashboard':
                require_once __DIR__ . '/../app/views/dashboard.php';
                break;
            // accounts management page
            case '/accounts':
                require_once __DIR__ . '/../app/views/accounts.php';
                break;
            case '/': //Default path (homepage)
            case ''; // Handles edge cases
                header('Location: /login');
            default:
                http_response_code(404);
                echo "404 - Page not found";
                break;
        }
    }

?>