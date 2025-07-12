<?php
// root/public/index.php

session_start();

// Load environment variables and BASE_PATH
require_once __DIR__ . '/../config/config.php';

// Load the router
require_once __DIR__ . '/../router/router.php';

// Get URI and normalize it 
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//$basePath = BASE_PATH ?: '/MindCloud-SCMS'; // BASE_PATH is from root/config/config.php   (uncomment if routing fails; else delete)
// echo "index.php: uri: $uri <br>";                    // delete for production

// Calculate relative path by removing the base path
$path = str_starts_with($uri, $basePath) ? substr($uri, strlen($basePath)) : $uri;

//echo "index.php: basepath: $basePath <br>";           // delete for production
//echo "index.php: path: $path <br>";                   // delete for production

// Ensure path starts with /
$path = '/'. ltrim($path, '/');
//echo "index.php: path: $path <br>";                   // delete for production

// Route the request
route($path);