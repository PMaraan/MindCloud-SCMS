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
            $stmtCollege = $this->pdo->prepare("SELECT c.short_name, c.name AS college_name
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
            $_SESSION['college_id'] = $userCollege['short_name'];
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

    public function getAllUsersAccountInfo() {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                c.short_name AS college_short_name,
                r.name AS role_name
            FROM users u
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN colleges c ON ur.college_id = c.college_id
            ORDER BY u.id_no ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserWithRoleAndCollegeUsingID($id_no) {
        $query = "
            SELECT 
                u.id_no,
                u.fname,
                u.mname,
                u.lname,
                u.email,
                r.name AS role_name,
                c.short_name AS college_short_name
            FROM users u
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r ON ur.role_id = r.role_id
            LEFT JOIN colleges c ON ur.college_id = c.college_id
            WHERE u.id_no = ?;
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$id_no]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function getAllRoleNames(){
        $stmt = $this->pdo->prepare("
            SELECT name as role_name FROM roles
            ORDER BY role_id DESC;
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCollegeShortNames(){
        $stmt = $this->pdo->prepare("
            SELECT short_name FROM colleges;
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function createUser($id_no, $fname, $mname, $lname, $email, $password, $college_short_name, $role_name) {
        try {
            // Begin transaction
            $this->pdo->beginTransaction();

            // Step 1: Insert into users table
            $stmt1 = $this->pdo->prepare("
                INSERT INTO users (id_no, fname, mname, lname, email, password)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt1->execute([$id_no, $fname, $mname, $lname, $email, $password]);

            // Step 2: Resolve college_id (if college is provided)
            $college_id = null;
            if (!empty($college_short_name)) {
                $stmt2 = $this->pdo->prepare("
                    SELECT college_id FROM colleges WHERE short_name = ?
                ");
                $stmt2->execute([$college_short_name]);
                $college = $stmt2->fetch(PDO::FETCH_ASSOC);

                if (!$college) {
                    throw new Exception("College not found: $college_short_name");
                }
                $college_id = $college['college_id'];
            }

            // Step 3: Get role_id from role_name
            $stmt3 = $this->pdo->prepare("
                SELECT role_id FROM roles WHERE name = ?
            ");
            $stmt3->execute([$role_name]);
            $role = $stmt3->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                throw new Exception("Role not found: $role_name");
            }
            $role_id = $role['role_id'];

            // Step 4: Insert into user_roles table (college_id may be null)
            $stmt4 = $this->pdo->prepare("
                INSERT INTO user_roles (id_no, role_id, college_id)
                VALUES (?, ?, ?)
            ");
            $stmt4->execute([$id_no, $role_id, $college_id]);

            // Commit transaction
            $this->pdo->commit();

            return ['success' => true];

        } catch (Exception $e) {
            // Rollback on any failure
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setAccountChangesUsingID($id_no, $fname, $mname, $lname, $email, $college_short_name, $role_name) {
        try {
            $this->pdo->beginTransaction();

            // Step 1: Update user details
            $stmt1 = $this->pdo->prepare("
                UPDATE users
                SET fname = ?, mname = ?, lname = ?, email = ?
                WHERE id_no = ?
            ");
            $stmt1->execute([$fname, $mname, $lname, $email, $id_no]);

            if ($stmt1->rowCount() === 0) {
                throw new \Exception("User update failed: No matching user found.");
            }

            // Step 2: Update college
            $stmt2 = $this->pdo->prepare("
                UPDATE user_roles
                SET college_id = (
                    SELECT college_id
                    FROM colleges
                    WHERE short_name = ?
                )
                WHERE id_no = ?
            ");
            $stmt2->execute([$college_short_name, $id_no]);

            if ($stmt2->rowCount() === 0) {
                throw new \Exception("College update failed: Invalid college short name or user role not found.");
            }

            // Step 3: Update role
            $stmt3 = $this->pdo->prepare("
                UPDATE user_roles
                SET role_id = (
                    SELECT role_id
                    FROM roles
                    WHERE name = ?
                )
                WHERE id_no = ?
            ");
            $stmt3->execute([$role_name, $id_no]);

            if ($stmt3->rowCount() === 0) {
                throw new \Exception("Role update failed: Invalid role name or user role not found.");
            }

            $this->pdo->commit();

            return ['success' => true];

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function setUserDetails($id_no, $fname, $mname, $lname, $email) {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET fname = ?, mname = ?, lname = ?, email = ?
            WHERE id_no = ?
        ");
        return $stmt->execute([$fname, $mname, $lname, $email, $id_no]);

    }

    public function setUserCollegeUsingCollegeShortName($id_no,$college_short_name){
        $stmt = $this->pdo->prepare("
            UPDATE user_roles
            SET college_id = (
                SELECT college_id
                FROM colleges
                WHERE short_name = ?
            )
            WHERE id_no = ?
        ");
        return $stmt->execute([$college_short_name, $id_no]);
    }

    public function setUserRoleUsingRoleName($id_no, $role_name) {
        $stmt = $this->pdo->prepare("
            UPDATE user_roles
            SET role_id = (
                SELECT role_id
                FROM roles
                WHERE role_name = ?
            )
            WHERE id_no = ?
        ");
        return $stmt->execute([$role_name, $id_no]);
    }

    public function createRole($role_name, $role_level) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO roles (name, level)
                VALUES (?, ?)
            ");
            $stmt->execute([$role_name, $role_level]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
        
    }

    public function setRoleChangesUsingID($role_id, $role_name, $role_level) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE roles
                SET name = ?, level = ?
                WHERE role_id = ?
            ");
            $stmt->execute([$role_name, $role_level, $role_id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function connect() {
        
    }

}
