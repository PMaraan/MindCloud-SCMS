<?php
/**
 * File: config/config.php
 * Description: Application configuration settings.
 */

// Step 1: Load .env file if it exists
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
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

// Step 2: Define constants from environment variables or defaults
define('APP_ENV', getenv('APP_ENV') ?: 'development');

define('USE_MOCK', filter_var(getenv('USE_MOCK') ?: 'false', FILTER_VALIDATE_BOOLEAN));

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'cms_db');
define('DB_USER', getenv('DB_USER') ?: 'user');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql'); // default to PostgreSQL if not set

define('UI_PER_PAGE_DEFAULT', (int)(getenv('UI_PER_PAGE_DEFAULT') ?: 10));

// Step 3: Normalize BASE_PATH
$envBase = trim(getenv('BASE_PATH') ?: '', '/');
if ($envBase !== '') {
    $basePath = '/' . $envBase;
} else {
    // Auto-detect based on SCRIPT_NAME
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $basePath  = rtrim($scriptDir, '/');
}

define('BASE_PATH', $basePath === '/' ? '' : $basePath);

// Step 4: Define ASSET_BASE
// Because ONLY /public is web-accessible (via .htaccess),
// assets MUST be served from /public/assets
define('ASSET_BASE', BASE_PATH . '/assets');

// Step 5: Define role groupings (global arrays)
$GLOBAL_ROLES  = ['VPAA', 'VPAA Secretary'];
$DEAN_ROLES    = ['Dean'];
$CHAIR_ROLES   = ['Chair'];
$FACULTY_ROLES = ['Professor'];

// End of config/config.php
