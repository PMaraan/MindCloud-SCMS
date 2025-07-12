<?php
// root/public/index.php

session_start();
// Load environment variables
require_once __DIR__ . '/../config/config.php';
// Get the router
require_once __DIR__ . '/../router/router.php';

// Get URI and normalize it by removing base path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "uri: $uri <br>";
// Remove /MindCloud-SCMS/public from the URI
$basePath = BASE_PATH ?: '/MindCloud-SCMS';
$path = str_starts_with($uri, $basePath) ? substr($uri, strlen($basePath)) : $uri;
echo "basepath: $basePath <br>";
echo "path: $path <br>";
// Ensure path starts with /
$path = '/'. ltrim($path, '/');
echo "path: $path <br>";
// Route the request
route($path);
