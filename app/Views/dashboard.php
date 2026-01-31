<?php
// root/app/views/Dashboard.php

session_start();

require_once __DIR__ . '/../../config/config.php'; // Load environment variables
require_once __DIR__ . '/../controllers/ContentController.php'; // Dynamically control the content
//require_once __DIR__ . '/xHeaderComponent.php'; // Load header component          // delete for production
//require_once __DIR__ . '/xSidebarComponent.php'; // Load sidebar component        // delete for production

/*
// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
*/

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You are not logged in!'); window.location='login.php';</script>";
    exit;
}else {
    //echo "<h1>Welcome to the Dashboard!</h1>";
}

$page = $_GET['page'] ?? 'index';

// CSS Mappings
$page_css = [
  'templates' => '../../public/assets/css/Templates.css',
  'college'   => '../../public/assets/css/CollegeRoles.css',
  'faculty'   => '../../public/assets/css/FacultyRoles.css',
  'add_college' => '../../public/assets/css/FacultyRoles.css',
  'view_roles' => '../../public/assets/css/ViewRoles.css',
  'edit_college' => '../../public/assets/css/FacultyRoles.css',
  'approve' => '../../public/assets/css/ForStatus.css',
  'note' => '../../public/assets/css/ForStatus.css',
  'prepare' => '../../public/assets/css/ForStatus.css',
  'revise' => '../../public/assets/css/ForStatus.css',
  'notification' => '../../public/assets/css/Notification.css',
  'settings' => '../../public/assets/css/Settings.css',
];

// JS Mappings
$page_js = [
  'templates' => '../../public/assets/js/Templates.js',
  'college'   => '../../public/assets/js/CollegeRoles.js',
  'faculty'   => '../../public/assets/js/FacultyRoles.js',
  'add_college' => '../../public/assets/js/FacultyRoles.js',
  'view_roles' => '../../public/assets/js/ViewRoles.js',
  'edit_college' => '../../public/assets/js/FacultyRoles.js',
  'approve' => '../../public/assets/js/ForStatus.js',
  'note' => '../../public/assets/js/ForStatus.js',
  'prepare' => '../../public/assets/js/ForStatus.js',
  'revise' => '../../public/assets/js/ForStatus.js',
  'notification' => '../../public/assets/js/Notification.js',
  'settings' => '../../public/assets/js/Settings.js',
];

// Content mapping
$allowed_pages = [
  'index'     => 'index.php',
  'approve'   => 'ForApproval.php',
  'note'      => 'ForNoting.php',
  'prepare'   => 'ForPreparation.php',
  'revise'    => 'SyllabusForRevision.php',
  'faculty'   => 'FacultyRoles.php',
  'templates' => 'Templates.php',
  'syllabus'  => '#',
  'college'   => 'CollegeRoles.php',
  'secretary' => '#',
  'courses'   => '#',
  'add_college' => 'CollegeFacultyRoles.php',
  'view_roles' => 'ViewRoles.php',
  'edit_college' => 'EditCollegeRoles.php',
  'notification'=> 'Notification.php',
  'settings' => 'Settings.php',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>


  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LPU-SCMS</title>

  <!-- Bootstrap & Icons -->    
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../../public/assets/css/HeaderComponent.css">
  <link rel="stylesheet" href="../../public/assets/css/SidebarComponent.css" />

  <!-- Page-specific CSS -->
  <?php if (isset($page_css[$page])): ?>
    <link rel="stylesheet" href="<?= $page_css[$page] ?>">
  <?php endif; ?>

</head>
<body>

  <?php require_once __DIR__ . '/xHeaderComponent.php'; // Load header component ?>

  <div class="wrapper">
    <?php
      $currentPage = $page; 
      require_once __DIR__ . '/xSidebarComponent.php'; // Load sidebar component
    ?>

    <div class="main-content">
      <div class="container-fluid py-4">
        <?php
          if (array_key_exists($page, $allowed_pages)) {
            include $allowed_pages[$page];
          } else {
            echo "<h4 class='fw-bold mb-4'>404 - Page Not Found</h4>";
          }
        ?>
      </div>
    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Page-specific JS -->
  <?php if (isset($page_js[$page])): ?>
    <script src="<?= $page_js[$page] ?>"></script>
  <?php endif; ?>

</body>
</html>


?>