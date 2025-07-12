<?php
// myapp/config/config.php

//require_once __DIR__ . '/../vendor/autoload.php'; // If using Composer (optional)

// Load .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    if ($env === false) {
        die(".env file syntax is invalid.");
    }
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Read from environment
define('USE_MOCK', getenv('USE_MOCK') === 'true');
define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('BASE_PATH', getenv('BASE_PATH'));