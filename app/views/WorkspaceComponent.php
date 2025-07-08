<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LPU-SCMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>

  <?php include 'HeaderComponent.php'; ?>

  <div class="wrapper">
    <?php include 'SidebarComponent.php'; ?>

    <div class="workspace">
      <div class="container py-5">
        <?php
          $page = $_GET['page'] ?? 'index';

          $allowed_pages = [
            'index' => 'index.php',
            'approve' => '#',
            'note' => '#',
            'prepare' => '#',
            'revise' => '#',
            'faculty' => '#',
            'templates' => 'Templates.php',
            'syllabus' => '#',
            'college' => 'College.php',
            'secretary' => '#',
            'courses' => '#'
          ];

          if (array_key_exists($page, $allowed_pages)) {
            include $allowed_pages[$page];
          } else {
            echo "<h4 class='fw-bold mb-4'>404 - Page Not Found</h4>";
          }
        ?>
      </div>
    </div>
  </div>

</body>
</html>
