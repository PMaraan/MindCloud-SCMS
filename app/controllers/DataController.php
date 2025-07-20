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

    public function setAccountChangesUsingID($id_no, $fname, $mname, $lname, $email, $college_short_name, $role_name) {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Step 1: Update user details
            $userUpdated = $this->db->setUserDetails($id_no, $fname, $mname, $lname, $email);
            if (!$userUpdated) {
                throw new Exception("User details update failed.");
            }

            // Step 2: Update college
            $collegeUpdated = $this->db->setUserCollegeUsingCollegeShortName($id_no, $college_short_name);
            if (!$collegeUpdated) {
                throw new Exception("College update failed.");
            }

            // Step 3: Update role
            $roleUpdated = $this->db->setUserRoleUsingRoleName($id_no, $role_name);
            if (!$roleUpdated) {
                throw new Exception("Role update failed.");
            }

            // Commit the transaction
            $this->db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            // Rollback on any failure
            $this->db->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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