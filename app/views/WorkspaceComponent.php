<?php
$page = $_GET['page'] ?? 'index';

// CSS Mappings
$page_css = [
  'templates' => '../../public/assets/css/Templates.css',
  'college'   => '../../public/assets/css/CollegeRoles.css',
  'faculty'   => '../../public/assets/css/FacultyRoles.css',
  'add_college' => '../../public/assets/css/CollegeFacultyRoles.css',
  'view_roles' => '../../public/assets/css/ViewRoles.css',
];

// JS Mappings
$page_js = [
  'templates' => 'assets/js/Templates.js',
  'college'   => '../../public/assets/js/CollegeRoles.js',
  'faculty'   => '../../public/assets/js/FacultyRoles.js',
  'add_college' => '../../public/assets/js/CollegeFacultyRoles.js',
  'view_roles' => '../../public/assets/js/ViewRoles.js',
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
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LPU-SCMS</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />

  <!-- Page-specific CSS -->
  <?php if (isset($page_css[$page])): ?>
    <link rel="stylesheet" href="<?= $page_css[$page] ?>">
  <?php endif; ?>
</head>
<body>

  <?php include 'HeaderComponent.php'; ?>

  <div class="wrapper">
    <?php
      $currentPage = $page; 
      include 'SidebarComponent.php';
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
