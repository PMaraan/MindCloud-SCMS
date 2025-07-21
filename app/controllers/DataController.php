<?php
//root/app/controllers/DataController.php

require_once __DIR__ . '/../../config/config.php';

class DataController {
    private $db;

    public function __construct(){
        // Use real or mock DB depending on your config
        require_once __DIR__ . '/../models/PostgresDatabase.php';
        $this->db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    }

    public function getUserInfoById($id_no) {
        return $this->db->getUserWithRoleAndCollegeUsingID($id_no); // You can rename this
    }

    public function getAllRoleNames(){
        return $this->db->getAllRoleNames();
    }

    public function getAllCollegeShortNames(){
        return $this->db->getAllCollegeShortNames();
    }

    public function createUser($id_no, $fname, $mname, $lname, $email, $college_short_name,$role_name){
        try {
            $defaultPassword = 'password';
            $password = password_hash($defaultPassword, PASSWORD_ARGON2ID);
            return $this->db->createUser($id_no, $fname, $mname, $lname, $email, $password, $college_short_name,$role_name);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setAccountChangesUsingID($id_no, $fname, $mname, $lname, $email, $college_short_name, $role_name) {
        try {            
            return $this->db->setAccountChangesUsingID($id_no, $fname, $mname, $lname, $email, $college_short_name, $role_name);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createRole($role_name, $role_level) {
        try {            
            $role_level = intval($role_level);      
            return $this->db->createRole($role_name, $role_level);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createCollege($college_short_name, $college_name, $dean) {
        try {                 
            return $this->db->createCollege($college_short_name, $college_name, $dean);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setRoleChangesUsingID($role_id, $role_name, $role_level) {
        try {
            $role_id = intval($role_id);
            $role_level = intval($role_level);
            return $this->db->setRoleChangesUsingID($role_id, $role_name, $role_level);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

}

/*
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'create') {
        $result = $db->createUser([
            'id_no' => $_POST['id_no'],
            'email' => $_POST['email'],
            'fname' => $_POST['fname'],
            'mname' => $_POST['mname'],
            'lname' => $_POST['lname'],
            'college' => $_POST['college'],
            'role' => $_POST['role']
        ]);
        echo json_encode(['success' => $result]);
    }

    if ($action === 'update') {
        $result = $db->updateUser([
            'id_no' => $_POST['id_no'],
            'email' => $_POST['email'],
            'fname' => $_POST['fname'],
            'mname' => $_POST['mname'],
            'lname' => $_POST['lname'],
            'college' => $_POST['college'],
            'role' => $_POST['role']
        ]);
        echo json_encode(['success' => $result]);
    }
}
*/