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