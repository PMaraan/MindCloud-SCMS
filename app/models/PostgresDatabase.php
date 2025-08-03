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

    public function beginTransaction() {
        if(!$this->pdo->inTransaction()){
            return $this->pdo->beginTransaction();
        }
        return false; // return false if already in transaction
    }

    public function commit() {
        if ($this->pdo->inTransaction()) {
            return $this->pdo->commit();
        }
        return false; // when there is nothing to commit
    }
    
    public function rollBack() {
        if ($this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }
        return false; // when there is nothing to roll back
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
            $stmtRole = $this->pdo->prepare("SELECT r.role_id, r.role_name
                                        FROM user_roles ur
                                        JOIN roles r ON ur.role_id = r.role_id                                        
                                        WHERE ur.id_no = ?");
            $stmtRole->execute([$user['id_no']]);
            $userRole = $stmtRole->fetch();

            // Retrieve user college (if present)
            $stmtCollege = $this->pdo->prepare("SELECT c.short_name, c.college_name
                                        FROM user_roles ur
                                        JOIN colleges c ON ur.college_id = c.college_id                                        
                                        WHERE ur.id_no = ?");
            $stmtCollege->execute([$user['id_no']]);
            $userCollege = $stmtCollege->fetch();

            // Set session variables
            session_regenerate_id(true);
            $_SESSION['username'] = $user['fname'] . " " . $user['lname'];
            $_SESSION['user_id'] = $user['id_no'];
            $_SESSION['role_id']  = intval($userRole['role_id']);
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

    // check if user has permission; returns true or false
    public function checkPermission($user_id,$permission_name) {
        $stmt = $this->pdo->prepare("
            SELECT EXISTS (
                SELECT 1
                FROM user_roles ur
                JOIN role_permissions rp ON ur.role_id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.permission_id
                WHERE ur.id_no = ?
                AND p.permission_name = ?
            )
        ");
        $stmt->execute([$user_id, $permission_name]);
        return $stmt->fetchColumn();
    }

    public function getAllUsers() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id_no,
                    u.fname,
                    u.mname,
                    u.lname,
                    u.email,
                    c.short_name AS college_short_name,
                    r.role_name
                FROM users u
                JOIN user_roles ur ON u.id_no = ur.id_no
                JOIN roles r ON ur.role_id = r.role_id
                LEFT JOIN colleges c ON ur.college_id = c.college_id
                ORDER BY u.id_no ASC
            ");
            $stmt->execute();
            return ['success' => true, 'db' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getAllRoles($sortOrder = null) {
        $sortOrder = strtoupper($sortOrder ?? 'ASC');
        $allowedSorts = ['ASC', 'DESC'];
        if (!in_array($sortOrder, $allowedSorts)) {
            $sortOrder = 'ASC';
        }
        $stmt = $this->pdo->prepare("
            SELECT * FROM roles
            ORDER BY role_id $sortOrder
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsersAccountInfo() { //deprecated. delete for production
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id_no,
                    u.fname,
                    u.mname,
                    u.lname,
                    u.email,
                    c.college_id,
                    c.short_name AS college_short_name,
                    r.role_id,
                    r.role_name
                FROM users u
                JOIN user_roles ur ON u.id_no = ur.id_no
                JOIN roles r ON ur.role_id = r.role_id
                LEFT JOIN colleges c ON ur.college_id = c.college_id
                ORDER BY u.id_no ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);        
    }

    public function getProgramsByCollege($college_id) {
        $stmt = $this->pdo->prepare("
            SELECT program_id, program_name
            FROM programs
            WHERE college_id = ?
            ORDER BY program_name
        ");
        $stmt->execute([$college_id]);
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
                r.role_name,
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
                STRING_AGG(r.role_name, ', ') AS roles
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
            SELECT role_name FROM roles
            ORDER BY role_id DESC;
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // return all roles with level higher than user level
    public function getAllRolesWithRestrictions($level) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM roles
            WHERE role_level > ?
            ORDER BY role_id DESC
        ");
        $stmt->execute([$level]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCollegeShortNames(){
        $stmt = $this->pdo->prepare("
            SELECT college_id, short_name FROM colleges;
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPermissionGroupsByUser($id_no) {
        $stmt = $this->pdo->prepare("
            SELECT p.permission_id, p.permission_name
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
                case '4': $groups['Programs'] = true; break;
                case '5': $groups['Courses'] = true; break;
                case '6': $groups['Faculty'] = true; break;
                case '7': $groups['Templates'] = true; break;
                case '8': $groups['Syllabus'] = true; break;
                default: break;
            }
        }

        return array_keys($groups); // e.g., ['accounts', 'college', 'templates']
    }

    public function getAllColleges() {
        $stmt = $this->pdo->prepare("
            SELECT 
                c.college_id,
                c.short_name,
                c.college_name,
                u.id_no AS dean_id,
                CONCAT(u.fname, ' ', COALESCE(u.mname || ' ', ''), u.lname) AS dean_name
            FROM colleges c
            LEFT JOIN college_deans cd ON c.college_id = cd.college_id
            LEFT JOIN users u ON cd.dean_id = u.id_no
            ORDER BY c.college_id;
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllDeans() {
        $stmt = $this->pdo->prepare("
            SELECT u.id_no, u.fname, u.mname, u.lname, u.email
            FROM users u
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r ON ur.role_id = r.role_id
            WHERE r.role_name = ?
        ");
        $stmt->execute(['Dean']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllChairs() {
        $stmt = $this->pdo->prepare("
            SELECT u.id_no, u.fname, u.mname, u.lname, u.email
            FROM users u
            JOIN user_roles ur ON u.id_no = ur.id_no
            JOIN roles r ON ur.role_id = r.role_id
            WHERE r.role_name = ?
        ");
        $stmt->execute(['Chair']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCourses() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM courses;
            ");
            $stmt->execute();
            return['success' => true, 'db' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => "Database error: " . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllProgramDetails() {
        try {
            // fetch all programs from the database
            $stmt = $this->pdo->prepare("
                SELECT  p.program_id, 
                        p.program_name, 
                        c.college_id, 
                        c.short_name AS college_short_name, 
                        u.id_no AS chair_id, 
                        CONCAT(
                            u.fname,
                            CASE 
                                WHEN u.mname IS NOT NULL AND u.mname <> '' THEN 
                                    ' ' || UPPER(LEFT(u.mname, 1)) || '.'
                                ELSE 
                                    ''
                            END,
                            ' ',
                            u.lname
                        ) AS chair_name
                FROM programs p
                LEFT JOIN program_chairs pc ON p.program_id = pc.program_id
                LEFT JOIN users u ON pc.chair_id = u.id_no
                LEFT JOIN colleges c ON p.college_id = c.college_id
                ORDER BY p.program_id ASC;
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    // assign new dean or replace old dean
    public function assignDean($id_no, $college_id) {
        $stmt = $this->pdo->prepare("
            INSERT INTO college_deans (college_id, dean_id)
            VALUES (?,?)
            ON CONFLICT ON CONSTRAINT uq_collegedeans_collegeid
            DO UPDATE SET dean_id = EXCLUDED.dean_id
        ");
        $stmt->execute([$college_id, $id_no]);
        return true;
    }

    // assign a role and an optional college to the user
    public function assignUserRoleAndCollege($id_no, $role_id, $college_id = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_roles (id_no, role_id, college_id)
            VALUES (?,?,?)
            ON CONFLICT (id_no, role_id)
            DO UPDATE SET college_id = EXCLUDED.college_id
        ");
        $stmt->execute([$id_no, $role_id, $college_id]);
        return true;
    }

    // cleanup college references in user roles (especially when assigning new deans)
    public function cleanupCollegeReferences($id_no, $role_id, $college_id) {
        $stmt = $this->pdo->prepare("
            UPDATE user_roles
            SET college_id = null
            WHERE role_id = ? AND college_id = ? AND id_no != ?
        ");
        $stmt->execute([$role_id, $college_id, $id_no]);
        return true;
    }

    public function createUser($id_no, $fname, $mname, $lname, $email, $password) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (id_no, fname, mname, lname, email, password)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$id_no, $fname, $mname, $lname, $email, $password]);
        return true;
    }

    // deprecated
    public function createUser0($id_no, $fname, $mname, $lname, $email, $password, $college_short_name, $role_name) { // deprecated. delete for production ...
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
                SELECT role_id FROM roles WHERE role_name = ?
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

    public function createDeanUser() {

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
                    WHERE role_name = ?
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
                INSERT INTO roles (role_name, role_level)
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
                SET role_name = ?, role_level = ?
                WHERE role_id = ?
            ");
            $stmt->execute([$role_name, $role_level, $role_id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createCollege($college_short_name, $college_name, $dean) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO colleges (short_name, college_name, dean)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$college_short_name, $college_name, $dean]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setCollegeInfo($college_id, $college_short_name, $college_name, $college_dean) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE colleges
                SET short_name = ?, college_name = ?, dean = ?
                WHERE college_id = ?
            ");
            $stmt->execute([$college_short_name, $college_name, $college_dean, $college_id]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getRoleIfExists($role_id) {
        $stmt = $this->pdo->prepare("
            SELECT role_name
            FROM roles
            WHERE role_id = ?
            LIMIT 1
        ");
        $stmt->execute([$role_id]);
        return $stmt->fetchColumn();
    }

    public function getRoleLevelUsingRoleId($role_id) {
        $stmt = $this->pdo->prepare("
            SELECT role_level FROM roles
            WHERE role_id = ?
            LIMIT 1
        ");
        $stmt->execute([$role_id]);
        return $stmt->fetchColumn();
    }

    public function getRoleLevelUsingUserId($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.role_level
            FROM roles r
            JOIN user_roles ur ON r.role_id = ur.role_id
            WHERE ur.id_no = ?
            ORDER BY r.role_level ASC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function getRoleNameUsingUserId($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT role_name from roles r
            LEFT JOIN user_roles ur ON r.role_id = ur.role_id
            WHERE ur.id_no = ?
            ORDER BY role_level ASC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function getRoleNameUsingRoleId($role_id){
        $stmt = $this->pdo->prepare("
            SELECT role_name from roles
            WHERE role_id = ?
        ");
        $stmt->execute([$role_id]);
        return $stmt->fetchColumn();
    }

    public function updateDeanUser($id_no, $fname, $mname, $lname, $email, $college_id, $role_id){
        try {
            $this->pdo->beginTransaction();

            // remove the chair record if the user is a chair previously
            $stmt0 = $this->pdo->prepare("
                DELETE FROM program_chairs WHERE chair_id = ?
            ");
            $stmt0->execute([$id_no]);
            
            // update user details
            $stmt1 = $this->pdo->prepare("
                UPDATE users
                SET fname = ?,
                    mname = ?,
                    lname = ?,
                    email = ?
                WHERE id_no = ?
            ");
            $stmt1->execute([$fname, $mname, $lname, $email, $id_no]);

            // set the college to null for the previous dean of the college
            $stmt1b = $this->pdo->prepare("
                UPDATE user_roles
                SET college_id = null
                WHERE role_id = ? AND college_id = ? AND id_no != ?
            ");
            $stmt1b->execute([$role_id, $college_id, $id_no]);

            //update user role and college
            $stmt2 = $this->pdo->prepare("
                UPDATE user_roles
                SET role_id = ?,
                    college_id = ?
                WHERE id_no = ?
            ");
            $stmt2->execute([$role_id, $college_id, $id_no]);

            // update college and dean relationships
            // clean up old assignments if any
            $stmt3a = $this->pdo->prepare("
                DELETE FROM college_deans WHERE dean_id = ?
            ");
            $stmt3a->execute([$id_no]);
            // officially assign the new dean
            $stmt3b = $this->pdo->prepare("
                INSERT INTO college_deans (college_id, dean_id)
                VALUES (?, ?)
                ON CONFLICT ON CONSTRAINT uq_collegedeans_collegeid
                DO UPDATE SET dean_id = EXCLUDED.dean_id
            ");
            $stmt3b->execute([$college_id, $id_no]);

            // commit if there were no errors
            $this->pdo->commit();
            return "Account data changed successfully!";
        } catch(PDOException $e) {
            // rollback if there is an error
            $this->pdo->rollback();
            return "Database error: " . $e->getMessage();
        } catch(Exception $e) {
            // rollback if there is an error
            $this->pdo->rollback();
            return "Error: " . $e->getMessage();
        }
    }

    // updateChairUser is deprecated. delete for production ...
    public function updateChairUser($id_no, $fname, $mname, $lname, $email, $college_id, $role_id){
        try {
            $this->pdo->beginTransaction();

            // remove the dean record if the user is a dean previously
            $stmt0 = $this->pdo->prepare("
                DELETE FROM college_deans WHERE dean_id = ?
            ");
            $stmt0->execute([$id_no]);

            // Get old college_id before updating
            $stmtOld = $this->pdo->prepare("
                SELECT college_id FROM user_roles WHERE id_no = ?
            ");
            $stmtOld->execute([$id_no]);
            $oldCollegeId = $stmtOld->fetchColumn();

            // update user details
            $stmt1 = $this->pdo->prepare("
                UPDATE users
                SET fname = ?,
                    mname = ?,
                    lname = ?,
                    email = ?
                WHERE id_no = ?
            ");
            $stmt1->execute([$fname, $mname, $lname, $email, $id_no]);

            // Only update user_roles and delete program_chairs if college changed
            if ($oldCollegeId != $college_id) {
                // Clean up old program chair records
                $stmt2a = $this->pdo->prepare("
                    DELETE FROM program_chairs WHERE chair_id = ?
                ");
                $stmt2a->execute([$id_no]);

                // Update user_roles with new college
                $stmt2b = $this->pdo->prepare("
                    UPDATE user_roles
                    SET role_id = ?, college_id = ?
                    WHERE id_no = ?
                ");
                $stmt2b->execute([$role_id, $college_id, $id_no]);
            } else {
                // Only role update if college didn't change
                $stmt2 = $this->pdo->prepare("
                    UPDATE user_roles
                    SET role_id = ?
                    WHERE id_no = ?
                ");
                $stmt2->execute([$role_id, $id_no]);
            }
            // officially assign the new chair to the program
            /*
            $stmt3b = $this->pdo->prepare("
                INSERT INTO program_chairs (program_id, chair_id)
                VALUES (?, ?)
                ON CONFLICT ON CONSTRAINT uq_programchairs_programid
                DO UPDATE SET chair_id = EXCLUDED.chair_id
            ");
            $stmt3b->execute([$college_id, $id_no]);
            */
            // commit if there were no errors
            $this->pdo->commit();
            return "Account data changed successfully!";
        } catch(PDOException $e) {
            // rollback if there is an error
            $this->pdo->rollback();
            return "Database error: " . $e->getMessage();
        } catch(Exception $e) {
            // rollback if there is an error
            $this->pdo->rollback();
            return "Error: " . $e->getMessage();
        }
    }

    public function updateGenericUser($id_no,  $fname, $mname, $lname, $email, $college_id, $role_id){
        try {
            $this->pdo->beginTransaction();

            // remove the dean or chair record to cleanup the records
            $stmt0a = $this->pdo->prepare("
                DELETE FROM college_deans WHERE dean_id = ?
            ");
            $stmt0a->execute([$id_no]);

            $stmt0b = $this->pdo->prepare("
                DELETE FROM program_chairs WHERE chair_id = ?
            ");
            $stmt0b->execute([$id_no]);

            // update user details
            $stmt1 = $this->pdo->prepare("
                UPDATE users
                SET fname = ?,
                    mname = ?,
                    lname = ?,
                    email = ?
                WHERE id_no = ?
            ");
            $stmt1->execute([$fname, $mname, $lname, $email, $id_no]);

            //update user role
            $stmt2 = $this->pdo->prepare("
                UPDATE user_roles
                SET role_id = ?,
                    college_id = ?
                WHERE id_no = ?
            ");
            $stmt2->execute([$role_id, $college_id, $id_no]);

            // commit if there were no errors
            $this->pdo->commit();
            return "Account data changed successfully!";
        } catch(PDOException $e) {
            // rollback if there is an error
            $this->pdo->rollback();
            return "Database error: " . $e->getMessage();
        } catch(Exception $e) {
            // rollback if there is an error
            $this->pdo->rollback();
            return "Error: " . $e->getMessage();
        }
    }

    public function connect() {
        
    }

}
