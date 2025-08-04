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
            case 'setAccountChangesUsingID':
                // check for null values
                if (!isset($_POST['id_no']) || $_POST['id_no'] === '') {
                    throw new Exception("Missing or empty value for user ID");
                }
                if (!isset($_POST['fname']) || $_POST['fname'] === '') {
                    throw new Exception("Missing or empty value for user First Name");
                }
                if (!isset($_POST['lname']) || $_POST['lname'] === '') {
                    throw new Exception("Missing or empty value for user Last Name");
                }
                if (!isset($_POST['email']) || $_POST['email'] === '') {
                    throw new Exception("Missing or empty value for user Email");
                }
                if (!isset($_POST['role_id']) || $_POST['role_id'] === '') {
                    throw new Exception("Missing or empty value for user Role ID");
                }
                $id_no = $_POST['id_no'];
                $fname = $_POST['fname'];
                $mname = $_POST['mname'];
                $lname = $_POST['lname'];
                $email = $_POST['email'];
                $college_id = intval($_POST['college_id']) ?? ''; // convert to integer
                $role_id = intval($_POST['role_id']); // convert to integer
                //$program_id = intval($_POST['program_id']); // convert to integer
                try {
                    //forward to data controller
                    $result = $controller->setAccountChangesUsingID($id_no,
                        $fname, $mname, $lname, $email, $college_id,$role_id);
                    //echo json_encode(['success' => true, 'message' => 'User saved successfully.']);
                    echo json_encode($result);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                
                if ($result['success']){
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode("User updated successfully."));
                    exit;
                }else {
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
            case 'createUser':
                // check for null values
                if (!isset($_POST['id_no']) || $_POST['id_no'] === '') {
                    throw new Exception("Missing or empty value for user ID");
                }
                if (!isset($_POST['fname']) || $_POST['fname'] === '') {
                    throw new Exception("Missing or empty value for user First Name");
                }
                if (!isset($_POST['lname']) || $_POST['lname'] === '') {
                    throw new Exception("Missing or empty value for user Last Name");
                }
                if (!isset($_POST['email']) || $_POST['email'] === '') {
                    throw new Exception("Missing or empty value for user Email");
                }
                if (!isset($_POST['role_id']) || $_POST['role_id'] === '') {
                    throw new Exception("Missing or empty value for user Role ID");
                }
                $id_no = $_POST['id_no'];
                $fname = $_POST['fname'];
                $mname = $_POST['mname'];
                $lname = $_POST['lname'];
                $email = $_POST['email'];
                $college_id = $_POST['college_id'] ?? ''; // handle null college
                $role_id = $_POST['role_id'];
                $result = $controller->createUser($id_no, $fname, $mname, $lname, 
                    $email, $college_id, $role_id);
                //response
                if($result['success']){
                    $message = $result['message'] ?? 'Success!';
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode($message));
                    exit;
                }else{
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
            case 'deleteAccount':
                // validate inputs
                if (!isset($_POST['id_no']) || $_POST['id_no'] === '') {
                    throw new Exception("Missing or empty value for user ID");
                }if (!isset($_POST['role_id']) || $_POST['role_id'] === '') {
                    throw new Exception("Missing or empty value for role ID");
                }
                $id_no = $_POST['id_no'];
                $role_id = $_POST['role_id'];
                try {
                    //forward to data controller
                    $result = $controller->deleteUserUsingID($id_no, $role_id);
                    //echo json_encode($result);
                } catch (Exception $e) {
                    $result = ['success' => false, 'error' => $e->getMessage()];
                    //echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                
                if ($result['success']){
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode("User deleted successfully."));
                    exit;
                }else {
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
            case 'createRole':                
                $role_name = $_POST['role_name'];
                $role_level = $_POST['role_level'];                
                //$result = $controller->createRole($role_name, $role_level);
                //response
                try {
                    $result = $controller->createRole($role_name, $role_level);
                    echo json_encode(['success' => true, 'message' => 'Role created successfully.']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                if($result['success']){
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode("Role created successfully."));
                    exit;
                }else{
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
            case 'setCollegeInfo':
                
                try {
                    if (!isset($_POST['college_id']) || $_POST['college_id'] === '') {
                        throw new Exception("Missing or empty value for college ID");
                    }
                    if (!isset($_POST['college_short_name']) || $_POST['college_short_name'] === '') {
                        throw new Exception("Missing or empty value for college short name");
                    }
                    if (!isset($_POST['college_name']) || $_POST['college_name'] === '') {
                        throw new Exception("Missing or empty value for college name");
                    }
                    if (!isset($_POST['dean_id']) || $_POST['dean_id'] === '') {
                        throw new Exception("Missing or empty value for dean ID");
                    }

                    $college_id = intval($_POST['college_id']);
                    $college_short_name = $_POST['college_short_name'];
                    $college_name = $_POST['college_name'];
                    $dean_id = $_POST['dean_id'];
                    $result = $controller->updateCollegeInfo($college_id, $college_short_name, $college_name, $dean_id);
                    // forward to datacontroller
                    //return['success' => true, 'message' => $controller->updateCollegeInfo($college_id, $college_short_name, $college_name, $dean_id)];
                } catch (PDOException $e) {
                    // database error
                    $result = ['success' => false, 'error' => "Database error: " . $e->getMessage()];
                } catch (Exception $e) {
                    // other errors
                    $result =  ['success' => false, 'error' => $e->getMessage()];
                }
                if($result['success']){
                    $message = $result['message'] ?? 'Success!';
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode($message));
                    exit;
                }else{
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
            case 'setRoleChangesUsingID':
                //forward to data controller
                $role_id = $_POST['role_id'];
                $role_name = $_POST['role_name'];
                $role_level = $_POST['role_level'];
                try {
                    $result = $controller->setRoleChangesUsingID($role_id, $role_name, $role_level);
                    echo json_encode(['success' => true, 'message' => 'User saved successfully.']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                
                if ($result['success']){
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode("Role updated successfully."));
                    exit;
                }else {
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
            case 'createCollege':                
                $college_short_name = $_POST['college_short_name'];
                $college_name = $_POST['college_name']; 
                $dean = $_POST['college_dean'];
                //$result = $controller->createRole($role_name, $role_level);
                //response
                try {
                    $result = $controller->createCollege($college_short_name, $college_name, $dean);
                    echo json_encode(['success' => true, 'message' => 'Role created successfully.']);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                if($result['success']){
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode("College created successfully."));
                    exit;
                }else{
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
/*
            case 'setCollegeInfo':
                //forward to data controller
                $college_id = $_POST['college_id'];
                $college_short_name = $_POST['college_short_name'];
                $college_name = $_POST['college_name'];
                //$dean_name = $_POST['dean_name']; //doesn't need
                $college_dean = $_POST['college_dean'];
                try {
                    $result = $controller->setCollegeInfo($college_id, $college_short_name, $college_name, $college_dean);
                    //echo json_encode(['success' => true, 'message' => 'User saved successfully.']);
                } catch (Exception $e) {
                    //echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
                
                if ($result['success']){
                    header("Location: ../app/views/Dashboard2.php?status=success&message=" . urlencode("College updated successfully."));
                    exit;
                }else {
                    $error = $result['error'] ?? 'Unknown error';
                    header("Location: ../app/views/Dashboard2.php?status=error&message=" . urlencode($error));
                    exit;
                }
                break;
                */
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
            // return all programs under the selected college
            case 'getProgramsByCollege':
                $college_id = $_GET['college_id'] ?? null;

                if (!$college_id) {
                    echo json_encode(['success' => false, 'error' => 'Missing college ID']);
                    exit;
                }

                $programs = $controller->getProgramsByCollege($college_id);
                echo json_encode(['success' => true, 'programs' => $programs]);
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