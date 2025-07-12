<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="../../public/assets/css/SidebarComponent.css" />
  <title>Sidebar</title>
</head>
<body>

<?php
  $currentPage = $currentPage ?? ($_GET['page'] ?? 'index');
?>

<div class="toggle-wrapper">
  <button class="btn toggle-btn" id="toggleBtn">
    <i class="bi bi-list"></i>
  </button>
</div>

<div class="wrapper">
  <div class="sidebar" id="sidebar">
    <div class="fade-group">
      <div class="sidebar-img-wrapper">
        <img src="../../public/assets/images/coecsa-building.jpg" alt="Sidebar logo" class="sidebar-img" />
      </div>

      <div class="d-flex flex-column align-items-center text-center profile-section">
        <h4 class="profile-name">Test Name</h4>
        <span class="profile-role">Test Role</span>
      </div>

      <ul class="nav flex-column">
        <?php
          $links = [
            'approve'   => 'Approve',
            'note'      => 'Note',
            'prepare'   => 'Prepare',
            'revise'    => 'Revise',
            'faculty'   => 'Faculty',
            'templates' => 'Templates',
            'syllabus'  => 'Syllabus',
            'college'   => 'College',
            'secretary' => 'Secretary',
            'courses'   => 'Courses'
          ];

          foreach ($links as $key => $label) {
            $activeClass = $currentPage === $key ? 'active' : '';
            echo "<li class='nav-item'>
                    <a class='nav-link linkstyle $activeClass' href='WorkspaceComponent.php?page=$key'>$label</a>
                  </li>";
          }
        ?>
      </ul>
    </div>
  </div>


<script>
  document.getElementById("toggleBtn").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
  });
</script>

</body>
</html>
