<?php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../app/controllers/DataController.php';

$controller = new DataController();



$action = $_GET['action'] ?? null;

switch ($action) {
    case 'get_user':
        if (!isset($_GET['id_no'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user ID']);
            exit;
        }

        $id_no = $_GET['id_no'];
        $user = $controller->getUserInfoById($id_no); // You'll define this in DataController

        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
        break;

    // Add more actions as needed
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}


?>