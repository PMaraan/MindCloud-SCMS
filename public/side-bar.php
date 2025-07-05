<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="assets/css/side-bar.css" />
  <title>Collapsible Sidebar</title>
</head>
<body>

<div class="wrapper">
  <div class="sidebar" id="sidebar">
  <button class="btn toggle-btn" id="toggleBtn">
    <i class="bi bi-list"></i>
  </button>

  <div class="fade-group">
    <div class="sidebar-img-wrapper">
      <img src="assets/images/coecsa-building.jpg" alt="Sidebar logo" class="sidebar-img" />
    </div>

    <div class="d-flex flex-column align-items-center text-center profile-section">
      <h4 class="profile-name">Test Name</h4>
      <span class="profile-role">Test Role</span>
    </div>

    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link linkstyle" href="template_menu.php">Design a Template</a>
      </li>
      <li class="nav-item">
        <a class="nav-link linkstyle" href="#">For Approval</a>
      </li>
      <li class="nav-item">
        <a class="nav-link linkstyle" href="#">For Noting</a>
      </li>
      <li class="nav-item">
        <a class="nav-link linkstyle" href="#">For Preparation</a>
      </li>
      <li class="nav-item">
        <a class="nav-link linkstyle" href="#">For Revision</a>
      </li>
      <li class="nav-item">
        <a class="nav-link linkstyle" href="#">Courses & Professors</a>
      </li>
      <li class="nav-item">
        <a class="nav-link linkstyle" href="Test.php">Templates</a>
      </li>
      <li class="nav-item">
        <a class="nav-link linkstyle" href="#">Syllabus</a>
      </li>
      
    </ul>
  </div>
</div>

  <script src="assets/js/side-bar.js"></script>

</body>
</html>
