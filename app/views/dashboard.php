<?php
session_start();

require_once __DIR__ . '/../../config/config.php'; // Load environment variables
require_once __DIR__ . '/../controllers/ContentController.php'; // Dynamically control the content
//require_once __DIR__ . '/xHeaderComponent.php'; // Load header component
//require_once __DIR__ . '/xSidebarComponent.php'; // Load sidebar component

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