<?php
  //$currentPage = $currentPage ?? ($_GET['page'] ?? 'index');
?>

<div class="toggle-wrapper">
  <button class="btn toggle-btn" id="toggleBtn">
    <i class="bi bi-list"></i>
  </button>
</div>

<div class="sidebar" id="sidebar">
  <div class="fade-group">
    <div class="sidebar-img-wrapper">
      <img src="../../public/assets/images/coecsa-building.jpg" alt="Sidebar logo" class="sidebar-img" />
    </div>

    <div class="d-flex flex-column align-items-center text-center profile-section">
      <h4 class="profile-name"><?= $_SESSION['username'] ?></h4>
      <span class="profile-role"><?= trim($_SESSION['college_id'] . " " . $_SESSION['role']) ?></span>
    </div>

    <ul class="nav flex-column">
      <?php
      require_once __DIR__ . '/../models/PostgresDatabase.php';
      $pdo = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
      $permissionGroups = $pdo->getPermissionGroupsByUser($_SESSION['user_id']);

      // Sidebar labels only
      $labels = ['Accounts', 'Roles', 'Colleges', 'Courses', 'Templates', 'Syllabus'];
      foreach ($labels as $key) {
        if (in_array($key, $permissionGroups)) {
          $pageKey = strtolower(str_replace(' ', '_', $key)); // Normalize key
          echo "<li class='nav-item'>
                  <a class='nav-link linkstyle' href='#' data-page='$pageKey'>$key</a>
                </li>";
        }
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
