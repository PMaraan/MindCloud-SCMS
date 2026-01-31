<?php
  // /app/Views/layouts/components/Sidebar.php
  // Expects $visibleModules
?>
<!-- Sidebar Component -->
<div class="toggle-wrapper">
  <button class="btn toggle-btn" id="toggleBtn" title="Toggle sidebar">
    <i class="bi bi-list"></i>
  </button>
</div>

<div class="sidebar" id="sidebar"><!-- sidebar open -->
  <div class="fade-group"><!-- fade-group open -->

    <!-- Sidebar Banner -->
    <div class="sidebar-img-wrapper"><!-- sidebar-img-wrapper open -->
      <img src="<?= BASE_PATH . "/public/assets/images/coecsa-building.jpg" ?>" alt="Sidebar logo" class="sidebar-img" />
    </div><!-- sidebar-img-wrapper close -->

    <!-- Profile Section -->
    <div class="d-flex flex-column align-items-center text-center profile-section"><!-- d-flex flex-column align-items-center text-center profile-section open -->
      <h4 class="profile-name"><?= htmlspecialchars($username)  // $username is from DashboardCntroller.php?></h4> 
      <span class="profile-role"><?= htmlspecialchars($displayRole)  // $displayRole is from DashboardCntroller.php?></span>
    </div><!-- d-flex flex-column align-items-center text-center profile-section close -->

    <!-- Navigation -->
    <ul class="nav flex-column">
      <?php 
        $current = $currentPage ?? 'dashboard';
        foreach (($visibleModules ?? []) as $key => $module): // Render each tab that the user has permission
      ?> 
          <li class="nav-item">
            <a  href="<?= BASE_PATH ?>/dashboard?page=<?= urlencode($key) ?>" 
                class="nav-link <?= ($current === $key) ? 'active' : '' ?>" 
                data-page="<?= htmlspecialchars($key) ?>">
              <?= htmlspecialchars($module['label'] ?? ucfirst($key)) ?>
            </a>
          </li>
      <?php endforeach; ?>
    </ul>

  </div><!-- fade-group close -->
</div><!-- sidebar close -->

<!-- Sidebar Component Script-->
<script>
// Toggle sidebar show and collapsed
document.getElementById("toggleBtn").addEventListener("click", function () {
  document.getElementById("sidebar").classList.toggle("collapsed");
  document.body.classList.toggle("sidebar-collapsed");
});

/*
// Toggle the active class for styling of selected sidebar tab
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    this.classList.add('active');
  });
});
*/
</script>
