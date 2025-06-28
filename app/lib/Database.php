<?php
// app/lib/Database.php

class Database {
    private $host, $port, $dbname, $user, $pass;

    public function __construct($host, $port, $dbname, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function connect() {
        try {
            return new PDO("pgsql:host=$this->host;port=$this->port;dbname=$this->dbname", $this->user, $this->pass);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}
