<?php
  // root/app/views/xSidebarCoponent2.php
  //$currentPage = $currentPage ?? ($_GET['page'] ?? 'index');
?>
<!-- Sidebar Component -->
<div class="toggle-wrapper">
  <button class="btn toggle-btn" id="toggleBtn" title="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>
</div>

<div class="sidebar" id="sidebar"><!-- sidebar open -->
  <div class="fade-group"><!-- fade-group open -->
    <div class="sidebar-img-wrapper"><!-- sidebar-img-wrapper open -->
      <img src="../../public/assets/images/coecsa-building.jpg" alt="Sidebar logo" class="sidebar-img" />
    </div><!-- sidebar-img-wrapper close -->

    <div class="d-flex flex-column align-items-center text-center profile-section"><!-- d-flex flex-column align-items-center text-center profile-section open -->
      <h4 class="profile-name"><?= $_SESSION['username'] ?></h4>
      <span class="profile-role"><?= trim($_SESSION['college_id'] . " " . $_SESSION['role']) ?></span>
    </div><!-- d-flex flex-column align-items-center text-center profile-section close -->

    <ul class="nav flex-column">
      <?php
      // Get user permissions
      // Mock permissions (bypass database)
      //$permissionGroups = ['Accounts', 'Roles', 'Colleges', 'Faculty', 'Programs', 'Courses', 'Templates', 'Syllabus'];
      require_once __DIR__ . '/../models/PostgresDatabase.php'; // Load the database model
      $pdo = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
      $permissionGroups = $pdo->getPermissionGroupsByUser($_SESSION['user_id']);

      $mapper = [
        // key is the actual sidebar text and value is the value of the $_POST['page'] for mapping in ContentController
        'Accounts' => 'Accounts',
        'Roles' => 'Roles',
        'Colleges' => 'Colleges',
        'Faculty' => 'Faculty',
        'Programs' => 'Programs',
        'Courses' => 'Courses',
        'Templates' => 'Templates',
        'Syllabus' => 'Syllabus'
      ];
      // Display the sidebar tabs that the user has permissions to
      foreach ($mapper as $key => $href) {
          if (in_array($key, $permissionGroups)) {
            echo "<li class='nav-item'>
              <a href=\"#\" class=\"nav-link\" data-page=\"$href\">$key</a>
            </li>";
            //<a class='nav-link linkstyle' href='$href'>$key</a>
            //echo "<a href=\"$href\">$key</a><br>";
          }
      }
      /*
      foreach ($permissionGroups as $perm) {
          if (array_key_exists($permKey, $availablePages)) {
              $label = $availablePages[$permKey];
              $activeClass = $currentPage === $permKey ? 'active' : '';
              echo "<li class='nav-item'>
                      <a class='nav-link linkstyle $activeClass' href='Dashboard.php?page=$permKey'>$label</a>
                  </li>";
          }
      }
      */
      ?>
    </ul>

  </div><!-- fade-group close -->
</div><!-- sidebar close -->



<!-- Sidebar Component Script-->
<script>
 document.getElementById("toggleBtn").addEventListener("click", function () {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("collapsed");
  document.body.classList.toggle("sidebar-collapsed");

  });
  /*
  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const page = this.dataset.page;

      fetch('dashboard.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'page=' + encodeURIComponent(page)
      })
      .then(res => res.text())
      .then(html => {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const newContent = temp.querySelector('#main-content');
        if (newContent) {
          document.getElementById('main-content').innerHTML = newContent.innerHTML;
        }
      });
    });
  });
  */
</script>
