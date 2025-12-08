<?php
// /config/config.php

// Load .env file if it exists
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    $env = parse_ini_file($envFile);
    if ($env === false) {
        die(".env file syntax is invalid.");
    }
    foreach ($env as $key => $value) {
        // Expose to getenv()
        putenv("$key=$value");
        // also expose via superglobals (useful in various hosts)
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    if ($env === false) {
        die(".env file syntax is invalid.");
    }
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
// echo "getenv: " . var_export(getenv('USE_MOCK'), true) . "<br>"; //delete for production ...
// Read from environment
define('USE_MOCK', filter_var(getenv('USE_MOCK') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'cms_db');
define('DB_USER', getenv('DB_USER') ?: 'user');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
//define('BASE_PATH', getenv('BASE_PATH') ?: '/MindCloud-SCMS');
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql'); // default to pgsql if not set
define('APP_ENV', getenv('APP_ENV') ?: 'dev');
define('UI_PER_PAGE_DEFAULT', (int)(getenv('UI_PER_PAGE_DEFAULT') ?: 10));

// Normalize BASE_PATH
$envBase = getenv('BASE_PATH') ?: '/MindCloud-SCMS';
if ($envBase && $envBase !== '') {
    $base = '/' . trim($envBase, '/');
} else {
    // Derive from the directory where /public/index.php lives, e.g. /MindCloud-SCMS
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $base = ($scriptDir === '' || $scriptDir === '/') ? '' : $scriptDir;
}
// Always store without trailing slash (except root empty)
define('BASE_PATH', $base);

//$basePath = BASE_PATH ?: '/MindCloud-SCMS';
// echo "USE_MOCK: " . var_export(USE_MOCK, true) . "<br>"; // delete for production

// Role groupings (global arrays)
$GLOBAL_ROLES  = ['VPAA', 'VPAA Secretary'];
$DEAN_ROLES    = ['Dean'];
$CHAIR_ROLES   = ['Chair'];
$FACULTY_ROLES = ['Professor'];