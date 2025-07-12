<?php
// root/router/router.php

    // echo "router.php:  basepath1: $basePath <br>";                               // delete for production
    function route($path){
        // Set up base path from config (inherited from root/public/index.php)
        $basePath = BASE_PATH;

        // Define routes that don't require login
        $publicRoutes = ['/public/login.php','/auth'];

        // echo "router.php: basepath2: $basePath <br>";                            // delete for production

        // If page is private AND user is not logged in 
        if (!in_array($path, $publicRoutes) && empty($_SESSION['user_id'])){
            // echo "router.php: public check: $path <br>";                         // delete for production
            // echo "router.php: redirect: $basePath/app/views/login.php <br>";     // delete for production
            // Redirect to login
            header("Location: $basePath/app/views/login.php");
            exit;
        }

        // Route handling
        switch ($path) {
            //login page
            case '/':       // delete if this breaks the code
            case '/login':
                require_once __DIR__ . '/../app/views/login.php';
                break;

            // login authentication
            case '/auth':
                require_once __DIR__ . '/../app/lib/UserController.php';
                break;
            /*
            // new authentication route (OOP usercontroller)
            case '/auth':
                require_once __DIR__ . '/../app/controllers/UserController.php';
                $controller = new UserController();
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $controller->login($_POST['email'], $_POST['password']);
                }
                break;

            // logout
            case '/logout':
                require_once __DIR__ . '/../app/controllers/UserController.php'; // usercontroller is not yet set up for oop
                UserController::logout();
                break;
            */
            // dashboard page
            case '/dashboard':
                require_once __DIR__ . '/../app/views/dashboard.php';
                break;

            // accounts management page
            case '/accounts':
                require_once __DIR__ . '/../app/views/accounts.php';
                break;

            // case '/': //Default path (homepage)          (uncomment if code is broken; else delete)    
            case '/public/index.php': // Automatically redirects to login page            
            case '': // Handles edge cases
                echo "router.php: path: $path <br>";
                if ($path !== '/login') {
                    // echo "router.php: loginpath: $path <br>";                    // delete for production
                    // echo "router.php: basePath: $basePath <br>";                 // delete for production
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