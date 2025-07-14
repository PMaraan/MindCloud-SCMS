<?php
// root/app/controllers/ContentController.php
//$page = $_GET['page'] ?? 'index';                       //delete for production

class ContentController {
  // CSS file mappings for each page
  private $css_map = [
    'templates'    => '../../public/assets/css/Templates.css',
    'college'      => '../../public/assets/css/CollegeRoles.css',
    'faculty'      => '../../public/assets/css/FacultyRoles.css',
    'add_college'  => '../../public/assets/css/FacultyRoles.css',
    'view_roles'   => '../../public/assets/css/ViewRoles.css',
    'edit_college' => '../../public/assets/css/FacultyRoles.css',
  ];

  // JS file mappings for each page
  private $js_map = [
    'templates'    => '../../public/assets/js/Templates.js',
    'college'      => '../../public/assets/js/CollegeRoles.js',
    'faculty'      => '../../public/assets/js/FacultyRoles.js',
    'add_college'  => '../../public/assets/js/FacultyRoles.js',
    'view_roles'   => '../../public/assets/js/ViewRoles.js',
    'edit_college' => '../../public/assets/js/FacultyRoles.js',
  ];

  // Content mapping (used to locate the correct PHP file for each page)
  private $page_map = [
    'index'        => 'index.php',
    'approve'      => '#',                      // Placeholder / Not yet implemented
    'note'         => '#',                      // Placeholder / Not yet implemented
    'prepare'      => '#',                      // Placeholder / Not yet implemented
    'revise'       => '#',                      // Placeholder / Not yet implemented
    'faculty'      => 'FacultyRoles.php',
    'templates'    => 'Templates.php',
    'syllabus'     => '#',                      // Placeholder / Not yet implemented
    'college'      => 'CollegeRoles.php',
    'secretary'    => '#',                      // Placeholder / Not yet implemented
    'courses'      => '#',                      // Placeholder / Not yet implemented
    'add_college'  => 'CollegeFacultyRoles.php',
    'view_roles'   => 'ViewRoles.php',
    'edit_college' => 'EditCollegeRoles.php',
  ];

  // Constructor
  public function __construct(){

  }

  // Public method to return all resources needed for a specific page
  public function getPageResources($page) {
    $css = $this->css_map[$page] ?? null;               // Get CSS file or null if not mapped
    $js = $this->js_map[$page] ?? null;                 // Get JS file or null if not mapped
    $content = $this->page_map[$page] ?? '404.php';     // Get content file or default to 404

    return [
        'css'     => $css,
        'js'      => $js,
        'content' => $content
    ];
  }

  private function mapSidebarTabsToAddresses($permissionGroupsArray){
    $mappedTabs = [];
    $mapper = [
      'Accounts'  => '/accounts',
      'Roles'     => '/roles',
      'Colleges'  => '/colleges',
      'Courses'   => '/courses',
      'Templates' => '/templates',
      'Syllabus'  => '/syllabus',
    ];

    foreach ($permissionGroupsArray as $tabName) {
      if (isset($mapper[$tabName])) {
        $mappedTabs[$tabName] = $mapper[$tabName];
      }
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
    return $this->mapSidebarTabsToAddresses($permissionGroups);
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
