<?php
// root/app/views/Dashboard2.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// for handling success/error/warning messages
$flashMessage = null;
if (!empty($_SESSION['flash'])) {
  $flashMessage = $_SESSION['flash'];
  unset($_SESSION['flash']);
}

require_once __DIR__ . '/../../config/config.php'; // Load environment variables
require_once __DIR__ . '/../controllers/DataController.php';
require_once __DIR__ . '/../controllers/ContentController.php'; // Dynamically control the content
require_once __DIR__ . '/../models/PostgresDatabase.php';

if (isset($_GET['status']) && isset($_GET['message'])): ?>
    <script>
        alert("<?= addslashes($_GET['message']) ?>");
    </script>
    <script>
    if (window.location.search.includes('status=') && window.location.search.includes('message=')) {
        // Remove query params from URL after showing alert
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>
<?php endif;

// FAKE LOGIN SESSION FOR TESTING WITHOUT DB
//$_SESSION['user_id'] = 1;
//$_SESSION['username'] = 'TestUser';
//$_SESSION['college_id'] = 'TESTCOL';
//$_SESSION['role'] = 'Developer';

// Load dynamic content using the ContentController
$page = $_POST['page'] ?? 'default';
//echo "Dashboard2.php: page value = $page";
//console log page value for debugging
$data = ['page' => $_POST['page'] ?? 'college'];
$message = "Dashboard2.php: page value = {$data['page']}";
echo "<script>console.log(" . json_encode($message) . ");</script>";

$contentController = new ContentController();
$resources = $contentController->getPageResources($page);

$css_file = $resources['css'];
$js_file = $resources['js'];
$content_file = $resources['content'];
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
  <link rel="stylesheet" href="../../public/assets/css/main.css" />

  <!-- Page-specific CSS -->
  <?php if ($css_file): ?>
    <link rel="stylesheet" href="<?= $css_file ?>">
  <?php endif; ?>
</head>
<body style="background-color: #f8f4f4;">


<?php include_once __DIR__ . '/components/Topbar.php';// '/xHeaderComponent.php'; delete for production ... // Load header component ?>

<div class="wrapper"><!-- wrapper open -->
  
  <?php
  //$currentPage = $page; 
  include_once __DIR__ . '/components/Sidebar.php'; // '/xSidebarComponent2.php'; delete for production ... // Load sidebar component

  // Handle flash message
  if ($flashMessage):
  ?>

  <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?> alert-dismissable fade show" role="alert">
    <?= htmlspecialchars($flashMessage['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>

  <?php endif; ?>

  <!-- Load Dynamic Workspace -->
  <div class="main-content"><!-- main-content open -->
    <div class="container-fluid py-4"><!-- container-fluid py-4 open -->
      <?php
        if ($content_file !== '#' && file_exists(__DIR__ . '/' . $content_file)) {
          include __DIR__ . '/' . $content_file;
        } else {
          echo "<h4 class='fw-bold mb-4'>404 - Page Not Found</h4>";
        }
      ?>
    </div><!-- container-fluid py-4 close -->
  </div><!-- main-content close -->
</div><!-- wrapper close -->



<!-- Page-specific JS -->
<?php if ($js_file): ?>
  <script src="<?= $js_file ?>"></script>
<?php endif; ?>

<!------------------------- AJAX Loader --------------------------->
<script 
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
</script>

<script>
document.body.addEventListener('click', function (e) {
  // Sidebar tabs are <a> elements with data-page attributes
  const button = e.target.closest('[data-page]');
  if (!button) return; // Ignore clicks outside data-page elements

  // Prevent the standard href from redirecting
  e.preventDefault();

  // Page is 
  const page = button.getAttribute('data-page');
  console.log('Clicked page:', page); // log the value to the console

  // formData is the form sent to the backend via POST method
  const formData = new FormData();
  formData.append('page', page);

  // Send a POST request
  fetch('Dashboard2.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(html => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    // Replace main content
    const newContent = doc.querySelector('.main-content');
    if (newContent) {
      document.querySelector('.main-content').innerHTML = newContent.innerHTML;
    }

    // Remove old page-specific scripts
    //document.querySelectorAll('script[data-page-script').forEach(el => el.remove());

    // Load new page-specific scripts


    // Load dynamic CSS (if needed)
    const newCss = doc.querySelectorAll('head link[rel="stylesheet"]:not([href*="bootstrap"], [href*="HeaderComponent"], [href*="SidebarComponent"])');
    newCss.forEach(link => {
      if (!document.querySelector(`link[href="${link.href}"]`)) {
        document.head.appendChild(link.cloneNode());
      }
    });

    // Load external JS files manually (including accscript.js)
    const newJs = doc.querySelectorAll('script[src]');
    newJs.forEach(script => {
      const src = script.getAttribute('src');

      // Always reload external scripts like accscript.js
      const newScript = document.createElement('script');
      newScript.src = src + `?t=${Date.now()}`; // force fresh load
      newScript.defer = true;
      newScript.onload = () => console.log(`✅ Reloaded: ${src}`);
      document.body.appendChild(newScript);
    });
  })
  .catch(err => console.error('Fetch error:', err));
});
</script>



</body>
</html>
