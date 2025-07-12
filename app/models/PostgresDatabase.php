<?php
// app/models/PostgresDatabase.php

require_once __DIR__ . '/StorageInterface.php';

class PostgresDatabase implements StorageInterface {
    private $pdo;

    public function __construct($host, $port, $dbname, $user, $pass) {
        try {
            $this->pdo =  new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function authenticate($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user['fname'] . " " . $user['lname'];
            $_SESSION['user_id'] = $user['id_no'];
            header('Location: /dashboard');
            exit;
        }else {
            $logFile = __DIR__ . '/../logs/login_errors.log';
            if (!file_exists($logFile)) {
                file_put_contents($logFile, "=== Login Log Initialized on " . date('Y-m-d H:i:s') . " ===\n");
            }
            file_put_contents(
                $logFile,
                "[" . date('Y-m-d H:i:s') . "] Login failed for email: $email\n",
                FILE_APPEND
            );
            header('Location: /login?error=1');
        }    
    }

    public function getAllUsersWithRoles() {
    $stmt = $this->pdo->prepare("
        SELECT u.id_no, u.email, u.fname, u.mname, u.lname,
               STRING_AGG(r.name, ', ') AS roles
        FROM users u
        LEFT JOIN user_roles ur ON u.id_no = ur.id_no
        LEFT JOIN roles r ON ur.role_id = r.role_id
        GROUP BY u.id_no, u.email, u.fname, u.mname, u.lname
        ORDER BY u.id_no
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    public function connect() {
        
    }

}
