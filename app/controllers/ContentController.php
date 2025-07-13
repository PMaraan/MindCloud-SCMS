<?php
// root/app/controllers/ContentController.php
//$page = $_GET['page'] ?? 'index';                       //delete for production

class ContentController {

  // Constructor
  public function __construct(){

  }

  private function mapSidebarTabsToAddresses($permissionGroupsArray){
    $mappedTabs;
    $mapper['Accounts'] = '/accounts';
    $mapper['Roles'] = '/roles';
    $mapper['Colleges'] = '/colleges';
    $mapper['Courses'] = '/courses';
    $mapper['Templates'] = '/templates';
    $mapper['Syllabus'] = '/syllabus';
    foreach ($permissionGroupsArray as $tabName){

      //$mappedTabs[$tabName] = 
    }
    // Return a key value pair Tab_name and Address
    return $mappedTabs;
  }

  public function getSidebarTabs($role_id){
    // Get permission groups from db and map the page addresses
    // Get permission groups from db
    require_once __DIR__ . '/../models/PostgresDatabase.php'; // Load the database model
    $pdo = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    $permissionGroups = $pdo->getPermissionGroupsByUser($_SESSION['user_id']);

    // Return array

  }

}

/*
// CSS Mappings
$page_css = [
  'templates' => '../../public/assets/css/Templates.css',
  'college'   => '../../public/assets/css/CollegeRoles.css',
  'faculty'   => '../../public/assets/css/FacultyRoles.css',
  'add_college' => '../../public/assets/css/FacultyRoles.css',
  'view_roles' => '../../public/assets/css/ViewRoles.css',
  'edit_college' => '../../public/assets/css/FacultyRoles.css',
];

// JS Mappings
$page_js = [
  'templates' => '../../public/assets/js/Templates.js',
  'college'   => '../../public/assets/js/CollegeRoles.js',
  'faculty'   => '../../public/assets/js/FacultyRoles.js',
  'add_college' => '../../public/assets/js/FacultyRoles.js',
  'view_roles' => '../../public/assets/js/ViewRoles.js',
  'edit_college' => '../../public/assets/js/FacultyRoles.js',
];

// Content mapping
$allowed_pages = [
  'index'     => 'index.php',
  'approve'   => '#',
  'note'      => '#',
  'prepare'   => '#',
  'revise'    => '#',
  'faculty'   => 'FacultyRoles.php',
  'templates' => 'Templates.php',
  'syllabus'  => '#',
  'college'   => 'CollegeRoles.php',
  'secretary' => '#',
  'courses'   => '#',
  'add_college' => 'CollegeFacultyRoles.php',
  'view_roles' => 'ViewRoles.php',
  'edit_college' => 'EditCollegeRoles.php',
];
*/
?>