<?php
// app/models/UserModel.php

class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function authenticate($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :username AND password = :password");
        $stmt->execute(['username' => $username, 'password' => $password]);
        return $stmt->fetch();
    }
}
