<?php
// /app/Views/layouts/DashboardLayout.php

/**
 * Variables expected:
 * $pageContent   -> Absolute path to content file
 * $pageCss       -> URL path to page CSS (or null)
 * $pageJs        -> URL path to page JS (or null)
 * $flashMessage  -> Flash message array from FlashHelper
 * $permissionGroups -> User's allowed sidebar sections
 * $mapper        -> Sidebar page map
 */

// require_once __DIR__ . '/../../../config/config.php'; // Load configs
// require_once __DIR__ . '/../../helpers/FlashHelper.php'; // Element for displaying success/error/warning messages

// Handling success/error/warning messages
// $flashMessage = FlashHelper::get();

// FAKE LOGIN SESSION FOR TESTING WITHOUT DB
//$_SESSION['user_id'] = 1;
//$_SESSION['username'] = 'TestUser';
//$_SESSION['college_id'] = 'TESTCOL';
//$_SESSION['role'] = 'Developer';

$basePath = rtrim(BASE_PATH, '/'); // BASE_PATH is from config.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>LPU-SCMS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Global CSS -->
 <link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/global.css">

  <!-- Bootstrap & Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  
  <!-- Component CSS -->
  <link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/HeaderComponent.css">
  <link rel="stylesheet" href="<?= $basePath ?>/public/assets/css/SidebarComponent.css">

  <!-- Legacy Bootstrap & Icons   
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  
  <link rel="stylesheet" href="../../public/assets/css/main.css" />
  -->
  
   <!-- Page-Specific CSS -->
  <?php 
  /*
  if (!empty($pageCss)): 
      <link rel="stylesheet" href="<?= $basePath . htmlspecialchars($pageCss) ?>">
   endif; 
  */ ?>
</head>
<body>

<!-- Topbar -->
<?php include __DIR__ . '/components/Topbar.php'; ?>

<!-- Wrapper -->
<div class="wrapper">

  <!-- Sidebar -->
  <?php include __DIR__ . '/components/Sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content container-fluid py-4">
    <?php if (!empty($flashMessage)): ?>
      <div class="alert alert-<?= htmlspecialchars($flashMessage['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($flashMessage['message'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <!-- The module-rendered HTML goes here -->
    <?= $contentHtml ?? '' ?>

    <?php if (defined('APP_ENV') && APP_ENV === 'dev'): ?>
      <hr>
      <h6 class="text-muted">Debug (dev only)</h6>
      <pre>Modules: <?= htmlspecialchars(print_r($modules ?? [], true)) ?></pre>
      <pre>ControllerClass: <?= htmlspecialchars((string)($controllerClass ?? 'null')) ?></pre>
      <pre>Test1: <?= htmlspecialchars((string)($test1 ?? '')) ?></pre>
      <pre>Test2: <?= htmlspecialchars((string)($test2 ?? '')) ?></pre>
      <pre>Controller object: <?= htmlspecialchars($controller ? get_class($controller) : 'null') ?></pre>
    <?php endif; ?>
    
      <?php //echo 'Modules: ' . print_r($modules);
      //echo var_dump($controllerClass);
      //echo htmlspecialchars($test1);
      //echo htmlspecialchars($test2);
      //echo var_dump($controller);
      //echo "<pre>"; 
        //print_r($contentHtml); 
      //echo "</pre>";
      //echo print_r($controllerClass->index());//echo $contentHtml ?? '';//include $pageContent; ?>
  </div>
</div>

<!-- Global JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="<?= $basePath ?>/public/assets/js/dashboard.js"></script>
<script src="<?= $basePath ?>/public/assets/js/notifications.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  // Reset forms inside ANY Bootstrap modal when it closes,
  // unless the modal has data-no-reset attribute.
  document.body.addEventListener('hidden.bs.modal', (ev) => {
    const modal = ev.target;
    if (!(modal instanceof HTMLElement)) return;
    if (modal.hasAttribute('data-no-reset')) return; // opt-out

    // Reset all forms inside this modal
    modal.querySelectorAll('form').forEach(form => {
      if (!(form instanceof HTMLFormElement)) return;
      form.reset();

      // Also clear validation states if you use them
      form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
        el.classList.remove('is-valid', 'is-invalid');
      });
    });
  }, true);
});
</script>
<!-- Page-Specific JS -->
<?php /*
if (!empty($pageJs)): 
  <script src="<?= $basePath . htmlspecialchars($pageJs) ?>" defer></script>
 endif; 
*/ ?>

</body>
</html>