<?php
  // root/app/views/layouts/components/Sidebar.php
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
      <img src="<?= $basePath . "/public/assets/images/coecsa-building.jpg" ?>" alt="Sidebar logo" class="sidebar-img" />
    </div><!-- sidebar-img-wrapper close -->

    <div class="d-flex flex-column align-items-center text-center profile-section"><!-- d-flex flex-column align-items-center text-center profile-section open -->
      <h4 class="profile-name"><?= $username?></h4> 
      <span class="profile-role"><?= $displayRole  //  change this to query database ...?></span>
    </div><!-- d-flex flex-column align-items-center text-center profile-section close -->

    <ul class="nav flex-column">
      <?php
      // Render each tab that the user has permission
      foreach ($mapper as $label => $pageName): ?>
        <?php if (in_array($label, $permissionGroups)): ?>
          
          <li class="nav-item">
            <a href="#" class="nav-link" data-page="<?= htmlspecialchars($pageName) ?>">
              <?= htmlspecialchars($label) ?>
            </a>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </div><!-- fade-group close -->
</div><!-- sidebar close -->

<!-- Sidebar Component Script-->
<script>
// Toggle sidebar show and collapsed
document.getElementById("toggleBtn").addEventListener("click", function () {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("collapsed");
  document.body.classList.toggle("sidebar-collapsed");
});

// Toggle the active class for styling of selected sidebar tab
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    this.classList.add('active');
  });
});
</script>
