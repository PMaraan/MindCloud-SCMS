<?php
// app/models/MockDatabase.php

require_once __DIR__ . '/StorageInterface.php';

class MockDatabase implements StorageInterface{
    // mock table for testing without a database server
    // you can edit this according to your needs
    private $db = [
        'users' => [
            ['email' => 'admin@lpunetwork.edu.ph', 'password' => 'password', 'fname' => 'Admin', 'lname' => 'User'],
            ['email' => 'test@lpunetwork.edu.ph', 'password' => 'test123', 'fname' => 'Test', 'lname' => 'User']
        ]
    ];

    public function authenticate($email, $password) {
        foreach ($this->db['users'] as $user) {
            if ($user['email'] === $email && $user['password'] === $password) {
                session_regenerate_id(true);
                $_SESSION['username'] = $user['fname'] . " " . $user['lname'];
                header("Location: ../views/dashboard.php");
                exit;
                //return $user;
            }
        }
        //return false;
    }
}
