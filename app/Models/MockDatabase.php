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

    public function getAllUsersWithRoles() {
    return [
        [
            'id_no' => '2025-01-00001',
            'email' => 'superadmin@lpunetwork.edu.ph',
            'fname' => 'Silvia',
            'mname' => 'A',
            'lname' => 'Adminson',
            'roles' => ['Superadmin']
        ],
        [
            'id_no' => '2025-01-00002',
            'email' => 'admin@lpunetwork.edu.ph',
            'fname' => 'Alan',
            'mname' => 'B',
            'lname' => 'Adminson',
            'roles' => ['Admin']
        ],
        [
            'id_no' => '2025-01-00003',
            'email' => 'manager@lpunetwork.edu.ph',
            'fname' => 'Miku',
            'mname' => 'C',
            'lname' => 'Managerdotr',
            'roles' => ['Manager']
        ],
        [
            'id_no' => '2025-01-00004',
            'email' => 'dean@lpunetwork.edu.ph',
            'fname' => 'Dan',
            'mname' => 'Da',
            'lname' => 'Deanson',
            'roles' => ['Dean']
        ],
        [
            'id_no' => '2025-01-00005',
            'email' => 'chair@lpunetwork.edu.ph',
            'fname' => 'Charles',
            'mname' => 'E',
            'lname' => 'Chairson',
            'roles' => ['Chair']
        ],[
            'id_no' => '2025-01-00006',
            'email' => 'professor@lpunetwork.edu.ph',
            'fname' => 'Pia',
            'mname' => 'F',
            'lname' => 'Professordotr',
            'roles' => ['Professor']
        ],
        // Add more mock users if needed
    ];
}

}
