<?php
// app/models/MockUserModel.php

class MockUserModel {
    // mock table for testing without a database server
    // you can edit this according to your needs
    private $users = [
        ['username' => 'admin', 'password' => 'admin123'],
        ['username' => 'test', 'password' => 'test123']
    ];

    public function authenticate($username, $password) {
        foreach ($this->users as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                return $user;
            }
        }
        return false;
    }
}
