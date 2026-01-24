<?php
session_start();
header('Content-Type: application/json');

$page = $_POST['page'] ?? 'dashboard';

// Define available pages and their optional assets
$pages = [
    'dashboard' => [
        'css' => '/public/assets/css/pages/dashboard.css',
        'js'  => '/public/assets/js/pages/dashboard.js',
        'content' => __DIR__ . '/../views/pages/dashboard.php'
    ],
    'programs' => [
        'css' => '/public/assets/css/pages/programs.css',
        'js'  => '/public/assets/js/pages/programs.js',
        'content' => __DIR__ . '/../views/pages/programs.php'
    ],
    'simple_page' => [
        'css' => null,
        'js'  => null,
        'content' => __DIR__ . '/../views/pages/simple_page.php'
    ]
];

if (!isset($pages[$page])) {
    echo json_encode([
        'html' => '<h4>404 - Page Not Found</h4>',
        'css' => null,
        'js' => null
    ]);
    exit;
}

// Load page HTML
ob_start();
include $pages[$page]['content'];
$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'css' => $pages[$page]['css'] ?? null,
    'js'  => $pages[$page]['js'] ?? null
]);
