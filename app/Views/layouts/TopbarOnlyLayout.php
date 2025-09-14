<?php
// /app/Views/layouts/TopbarOnlyLayout.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Expects: $contentHtml (string) containing the page content HTML.
 * This layout renders the Topbar and a main container, but NO sidebar.
 *
 * NOTE about $basePath:
 * Your Topbar component currently references `$basePath . "/public/..."`.
 * BASE_PATH already points to "/public". To keep the Topbar component unchanged,
 * we provide $basePath here as the PROJECT root (one level above /public).
 * Example: if BASE_PATH = /MindCloud-SCMS/public, $basePath becomes /MindCloud-SCMS
 */
$projectRoot = rtrim(dirname(BASE_PATH), '/'); // -> "/{project}"
$basePath = $projectRoot; // satisfy Topbar.php expectations

// Safety: ensure we have something to render.
$contentHtml = isset($contentHtml) ? (string)$contentHtml : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>LPU-SCMS — Profile</title>


<!-- Global CSS -->
 <link rel="stylesheet" href="<?= BASE_PATH ?>/public/assets/css/global.css">

  <!-- Bootstrap & Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  
  <!-- Component CSS -->
  <link rel="stylesheet" href="<?= BASE_PATH ?>/public/assets/css/HeaderComponent.css">

</head>
<body>
  <?php
  // Include Topbar component (expects $basePath defined above)
  require __DIR__ . '/components/Topbar.php';
  ?>

  <main class="container-fluid py-3">
    <?= $contentHtml ?>
  </main>

  <!-- Bootstrap bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- If your topbar/notifications rely on a site-wide script, include it here -->
  <!-- <script src="<?= BASE_PATH ?>/assets/js/app.js"></script> -->
</body>
</html>
