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

    private function isMorePrivileged($currentUserId, $role_id){
        // the current user is the currently logged in user
        // the other user is the user who we do db operations to
        $currentUserLevelRaw = $this->db->getRoleLevelUsingUserId($currentUserId);
        $targetUserLevelRaw = $this->db->getRoleLevelUsingRoleId($role_id);
        
        // check if fetch failes
        if ($currentUserLevelRaw === false || $targetUserLevelRaw === false) {
            throw new Exception("Role level not found for one or both users.");
        }

        // cast values to int
        $currentUserLevel = intval($currentUserLevelRaw);
        $targetUserLevel = intval($targetUserLevelRaw);

        // Admins (level 1) can act on anyone, including equal level
        if ($currentUserLevel === 1) {
            return true;
        }

        // the lower the level, the greater the privelege
        // return true if current user is more priveleged
        return $currentUserLevel < $targetUserLevel;
    }

    public function getAllUsersAccountInfo() {
        try {
            //validate role here ...
            return ['success' => true, 'db' => $this->db->getAllUsersAccountInfo()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error: '. $e->getMessage()];
        }
    }

    public function getUserInfoById($id_no) {
        return $this->db->getUserWithRoleAndCollegeUsingID($id_no); // You can rename this
    }

    public function getAllRoleNames(){
        return $this->db->getAllRoleNames();
    }

    public function getAllRoles() {
        try {
            return ['success' => true, 'db' => $this->db->getAllRoles()];
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            // handle other errors
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // only use this for create user modal. for edit user modal, use getAllRoles()
    public function getAllRolesWithRestrictions() {
        try {
            // check permissions
             if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $user_id = $_SESSION['user_id'];            
            $hasPermission = $this->db->checkPermission($user_id, 'RoleViewing');
            
            if (!$hasPermission) {
                throw new Exception("You don't have permission to perform this action!");
            }
            
            // check the role level. if 1, then get all roles;
            // if above 1, get roles filtering out roles with lower levels
            // note: the lower the level the higher the access
            $level = intval($this->db->getRoleLevelUsingUserId($user_id));
            
            if ($level && $level > 0) {
                switch($level) {
                    case 1:
                        // get all roles without restrictions
                        $result = $this->db->getAllRoles('DESC');                        
                        break;
                    default:
                        // get all roles with restrictions
                        $result = $this->db->getAllRolesWithRestrictions($level);
                        //return ['success' => true, 'db' => $result];
                        break;
                }
            } else {
                throw new Exception("Invalid role.");
            }
            return ['success' => true, 'db' => $result];
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            // handle other errors
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllCollegeShortNames(){
        //validate if the college exists here...
        return $this->db->getAllCollegeShortNames();
    }

    public function getAllColleges(){
        //validate if the college exists here...
        try {
            return ['success' => true, 'db' => $this->db->getAllColleges()];
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            // handle other errors
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getProgramsByCollege($college_id) {
        return $this->db->getProgramsByCollege($college_id);
    }

    public function createUser($id_no, $fname, $mname, $lname, $email, $college_id, $role_id){
        try {
            // check if current user has permission
              if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $user_id = $_SESSION['user_id'];   
            $hasPermission = $this->db->checkPermission($user_id, 'AccountCreation');
            $isMorePrivileged = $this->isMorePrivileged($user_id, $role_id);
            if(!$hasPermission || !$isMorePrivileged) {
                throw new Exception("You don't have permission to perform this action!");
            }

            // hash the default password
            $defaultPassword = 'password';
            $password = password_hash($defaultPassword, PASSWORD_ARGON2ID);

            // get the role name of the target user
            $userRoleName = $this->db->getRoleNameUsingRoleId($role_id);

            // save to database depending on role
            $role_name = strtolower($userRoleName);
            //return ['success' => true, 'message' => $role_name];
            switch($role_name) {
                case 'dean':
                    // begin transaction
                    $this->db->beginTransaction();
                    // save basic info
                    $this->db->createUser($id_no, $fname, $mname, $lname, $email, $password);
                    // clean up old dean in user roles and college deans
                    $this->db->cleanupCollegeReferences($id_no, $role_id, $college_id);
                    // save user role and college
                    $this->db->assignUserRoleAndCollege($id_no, $role_id, $college_id);
                    // officially assing the new dean
                    $this->db->assignDean($id_no, $college_id);
                    // commit if there are no errors
                    $this->db->commit();
                    //$result = $this->db->createDeanUser($id_no, $fname, $mname, $lname, $email, $college_id, $role_id);
                    break;
                default:
                    // begin transaction
                    $this->db->beginTransaction();
                    // save basic info
                    $this->db->createUser($id_no, $fname, $mname, $lname, $email, $password);
                    // save user role and optional college
                    $this->db->assignUserRoleAndCollege($id_no, $role_id, $college_id);
                    // commit if there are no errors
                    $this->db->commit();
                    break;
            }
            //return $this->db->createUser($id_no, $fname, $mname, $lname, $email, $password, $college_id,$role_id);
            return ['success' => true, 'message' => 'User created successfully!'];
        } catch (PDOException $e) {
            // Database or logic-level error
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function checkPermission($permission) {
        try {
             // check if current user has permission to do action
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userid = $_SESSION['user_id'];
            $hasPermission = null;
            $hasPermission = $this->db->checkPermission($userid, $permission);
            if (!$hasPermission) {
                throw new Exception("You don't have permission to perform this action!");
            }
            return ['success' => true, 'hasPermission' => $hasPermission];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database error'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
    }

    public function deleteUserUsingID($id_no) {
        try {
            // check if current user has permission
              if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $user_id = $_SESSION['user_id'];   
            $hasPermission = $this->db->checkPermission($user_id, 'AccountDeletion');
            
            $role_id = $this->db->getRoleIdUsingUserId($id_no);
            $isMorePrivileged = $this->isMorePrivileged($user_id, $role_id);
            if(!$hasPermission || !$isMorePrivileged) {
                throw new Exception("You don't have permission to perform this action!");
            }

            // call the database
            $this->db->deleteUserUsingID($id_no);

            // return this if success
            return ['success' => true, 'message' => 'User deleted successfully!'];
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            // handle other Exceptions
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setAccountChangesUsingID($id_no, $fname, $mname, $lname, $email, $college_id, $role_id) {
        try {
            // check if current user has permission to do action
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $userid = $_SESSION['user_id'];
            $hasPermission = null;
            $hasPermission = $this->db->checkPermission($userid,'AccountModification');
            if (!$hasPermission) {
                throw new Exception("You don't have permission to perform this action!");
            }
            // check if the user has a higher rank than the account being changed
            $userRoleId = $_SESSION['role_id'];
            $userLevel = $this->db->getRoleLevelusingRoleId($userRoleId);
            $targetLevel = $this->db->getRoleLevelUsingRoleId($role_id);
            if ($userLevel >= $targetLevel) {
                throw new Exception("You don't have permission to perform this action!");
            }

            // check if role is valid
            $role_name = $this->db->getRoleIfExists($role_id);
            if (!$role_name) {
                // handle error because role should not be null...
                throw new Exception("Role not found!");
            }
            switch (strtolower($role_name)) {
                case 'dean':
                    // the role to be set is dean
                    $result = $this->db->updateDeanUser($id_no, $fname, $mname, $lname, $email, $college_id, $role_id);
                    break;
                
                case 'chair':
                    // chair logic goes here...
                    $result = $this->db->updateChairUser($id_no, $fname, $mname, $lname, $email, $college_id, $role_id);
                    break;
                
                case '':
                    //handle error here...
                    throw new Exception("Role not found");
                    break;
                default:
                    // for values other than dean, chair, or null...
                    $result = $this->db->updateGenericUser($id_no,  $fname, $mname, $lname, $email, $college_id, $role_id);
                    break;
            }
            // if success
            //$result = $this->db->setAccountChangesUsingID($id_no, $fname, $mname, $lname, $email, $college_id, $role_id);
            return ['success' => true, 'db' => $result];
            /* example short-circuit checking code:
            SELECT EXISTS (
                SELECT 1 FROM colleges WHERE college_id = :id
            )
            */
            
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
    }

    public function createRole($role_name, $role_level) {
        try {            
            $role_level = intval($role_level);      
            return $this->db->createRole($role_name, $role_level);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createCollege($college_short_name, $college_name, $dean) {
        try {                 
            return $this->db->createCollege($college_short_name, $college_name, $dean);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setRoleChangesUsingID($role_id, $role_name, $role_level) {
        try {
            $role_id = intval($role_id);
            $role_level = intval($role_level);
            return $this->db->setRoleChangesUsingID($role_id, $role_name, $role_level);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function setCollegeInfo($college_id, $college_short_name, $college_name, $college_dean) {
        try {
            $college_id = intval($college_id);
            return $this->db->setCollegeInfo($college_id, $college_short_name, $college_name, $college_dean);
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllDeans() {
        try {
            return $this->db->getAllDeans();
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllProgramDetails() {
        try {
            // insert validation here...
            return ['success' => true, 'db' => $this->db->getAllProgramDetails()];
        } catch (PDOException $e) {
            // Database or logic-level error
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error: ' . $e->getMessage()];
        }
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