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

$type = $_SERVER['REQUEST_METHOD'];


switch ($type) {
    case 'POST':
        //post switch statement
        $action = $_POST['action'];
        switch ($action) {
            case 'saveAccountChangesUsingID':
                //forward to data controller
                $id_no = $_POST['id_no'];
                $fname = $_POST['fname'];
                $mname = $_POST['mname'];
                $lname = $_POST['lname'];
                $email = $_POST['email'];
                $college_short_name = $_POST['college_short_name'];
                $role_name = $_POST['role_name'];
                try {
                    $result = $controller->setAccountChangesUsingID($id_no,
                    $fname, $mname, $lname, $email, $college_short_name,$role_name);
                    echo json_encode(['success' => true, 'message' => 'User saved successfully.']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                
                if ($result['success']){
                    //echo json_encode($result); //send the result back if any
                }else {
                    //echo "Failed to update college: " . $result['error'];
                }
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action!']);
                exit;
                break;
        }





        break;



    case 'GET':
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
        break;


    default:
        echo "Unknown type";
        break;
}





?>