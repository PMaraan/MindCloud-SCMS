<?php
// /app/bootstrap.php

// 1. Load config (defines APP_ENV, DB_DRIVER, BASE_PATH, etc.)
require_once dirname(__DIR__) . '/config/config.php';

// 1b. Error handling
if (APP_ENV === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1'); // also log to error_log for convenience
    ini_set('error_log', dirname(__DIR__) . '/storage/logs/dev_error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1'); // still log errors in production
    // For Linux prod you may later switch to /var/log/mindcloud/prod_error.log
    ini_set('error_log', dirname(__DIR__) . '/storage/logs/prod_error.log');
}

// 2. Load autloader (PSR-4)
require_once __DIR__ . '/autoload.php';

// 3. Sessions (needed for Auth/RBAC)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3b. Request ID for correlation
if (empty($_SERVER['REQUEST_ID'])) {
    $_SERVER['REQUEST_ID'] = bin2hex(random_bytes(8));
}

// 3c. Initialize our Logger
use App\Helpers\Logger as MCLogger;
MCLogger::init(
    ini_get('error_log') ?: null,
    APP_ENV === 'dev' ? 'debug' : 'info', // threshold
    APP_ENV,
    $_SERVER['REQUEST_ID']
);

// Catch uncaught exceptions
set_exception_handler(function (\Throwable $e) {
    // Log with your helper if present; otherwise fall back to error_log
    if (function_exists('App\Helpers\logger')) {
        \App\Helpers\logger()->exception($e, 'critical');
    } else {
        error_log('Uncaught exception: ' . $e);
    }

    http_response_code(500);
    if (APP_ENV === 'dev') {
        echo '<pre>Uncaught exception: ' . htmlspecialchars((string)$e) . '</pre>';
    } else {
        echo '<h1>500 Internal Server Error</h1>';
    }
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (function_exists('App\Helpers\logger')) {
            \App\Helpers\logger()->log('emergency', $err['message'], [
                'file' => $err['file'],
                'line' => $err['line'],
                'type' => $err['type'],
            ]);
        } else {
            error_log(sprintf('Fatal error: %s in %s on line %d', $err['message'], $err['file'], $err['line']));
        }

        http_response_code(500);
        if (APP_ENV === 'dev') {
            echo '<pre>Fatal error: ' . htmlspecialchars($err['message'])
               . ' in ' . htmlspecialchars($err['file'])
               . ' on line ' . (int)$err['line'] . '</pre>';
        } else {
            echo '<h1>500 Internal Server Error</h1>';
        }
    }
});



// 4. Database via factory (autoload will find it if case matches)
use App\Factories\DatabaseFactory;
/** @var \App\Interfaces\StorageInterface $db */
$db = DatabaseFactory::create(DB_DRIVER);

// 5. Start router (function-style for now)
require_once dirname(__DIR__) . '/Router/router.php';

// Dispatch the request (normalize method default)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
route($_SERVER['REQUEST_URI'] ?? '/', $db, $method);

//route($_SERVER['REQUEST_URI'], $db);

// Always include global flash renderer
//require_once __DIR__ . '/views/layouts/partials/FlashGlobal.php';

// Return database connection for controllers
// return \App\Factories\DatabaseFactory::create(DB_DRIVER);
