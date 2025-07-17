<?php
// app/models/PostgresDatabase.php

require_once __DIR__ . '/StorageInterface.php';
 // echo "PostgresDatabase.php: basePath1: $basePath <br>";                             //delete for production
class PostgresDatabase implements StorageInterface {
    private $pdo;
    private $basePath = BASE_PATH;

    public function __construct($host, $port, $dbname, $user, $pass) {
        try {
            $this->pdo =  new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
        //echo "PostgresDatabase.php: basePath2: $this->basePath <br>";                 //delete for production
    }

    public function authenticate($email, $password) {
        // Retrieve the user details from the database
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        // echo "PostgresDatabase.php: basePath3: $this->basePath <br>";                //delete for production
        // Check if email and password match
        if ($user && password_verify($password, $user['password'])) {
            // Retrieve user role
            $stmtRole = $this->pdo->prepare("SELECT r.role_id, r.name AS role_name
                                        FROM user_roles ur
                                        JOIN roles r ON ur.role_id = r.role_id                                        
                                        WHERE ur.id_no = ?");
            $stmtRole->execute([$user['id_no']]);
            $userRole = $stmtRole->fetch();

            // Retrieve user college (if present)
            $stmtCollege = $this->pdo->prepare("SELECT c.college_id, c.name AS college_name
                                        FROM user_roles ur
                                        JOIN colleges c ON ur.college_id = c.college_id                                        
                                        WHERE ur.id_no = ?");
            $stmtCollege->execute([$user['id_no']]);
            $userCollege = $stmtCollege->fetch();

            // Set session variables
            session_regenerate_id(true);
            $_SESSION['username'] = $user['fname'] . " " . $user['lname'];
            $_SESSION['user_id'] = $user['id_no'];
            $_SESSION['role_id'] = $_SESSION['role'] = $userRole['role_id'];
            $_SESSION['role'] = $userRole['role_name'];
            $_SESSION['college_id'] = $userCollege['college_id'];
            $_SESSION['college'] = $userCollege['college_name'];
            $role = $_SESSION['role'];
            //echo "role: $role <br>";                                                  //delete for production
            //echo "PostgresDatabase.php: basePath4: $this->basePath <br>";             //delete for production
            header("Location: $this->basePath/app/views/Dashboard2.php");
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
            header("Location: $this->basePath/app/views/login.php?error=1");
            exit;
        }    
    }

    public function getAllRoles() {
        $stmt = $this->pdo->prepare("
            SELECT * FROM roles
            ORDER BY role_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function getUserPermissions($userId) {
        $stmt = $this->pdo->prepare("
            SELECT p.permission_key
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.permission_id
            JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.id_no = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getPermissionGroupsByUser($id_no) {
        $stmt = $this->pdo->prepare("
            SELECT p.permission_id, p.name
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.permission_id
            WHERE ur.id_no = ?
        ");
        $stmt->execute([$id_no]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $groups = [];
        foreach ($permissions as $perm) {
            $setPrefix = substr($perm['permission_id'], 0, 1); // e.g., '1' for account, '3' for college
            switch ($setPrefix) {
                case '1': $groups['Accounts'] = true; break;
                case '2': $groups['Roles'] = true; break;
                case '3': $groups['Colleges'] = true; break;
                case '4': $groups['Courses'] = true; break;
                case '5': $groups['Templates'] = true; break;
                case '6': $groups['Syllabus'] = true; break;
                default: break;
            }
        }

        return array_keys($groups); // e.g., ['accounts', 'college', 'templates']
    }

    public function connect() {
        
    }

}
