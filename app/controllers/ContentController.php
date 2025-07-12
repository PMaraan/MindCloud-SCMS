<?php
$page = $_GET['page'] ?? 'index';

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
?>