<?php
// public/index.php

session_start();

require_once __DIR__ . '/../router/router.php';

// Get the request URI and strip query string
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route the request
route($uri);
