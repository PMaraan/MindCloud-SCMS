<?php
  //$currentPage = $currentPage ?? ($_GET['page'] ?? 'index');
?>

<div class="toggle-wrapper">
  <button class="btn toggle-btn" id="toggleBtn">
    <i class="bi bi-list"></i>
  </button>
</div>

<!--<div class="wrapper">-->
    <div class="sidebar" id="sidebar">
        <div class="fade-group">
            <div class="sidebar-img-wrapper">
                <img src="../../public/assets/images/coecsa-building.jpg" alt="Sidebar logo" class="sidebar-img" />
            </div>

            <div class="d-flex flex-column align-items-center text-center profile-section">
                <h4 class="profile-name"><?= $_SESSION['username']?></h4>
                <span class="profile-role"><?= trim($_SESSION['college_id'] . " " . $_SESSION['role'])?></span>
            </div>

            <ul class="nav flex-column">
                <?php
                // the comments below are just pseudo code to guide the devs
                // Create the database pdo               
                // Get the role of the user
                // Map out the permissions of the user
                require_once __DIR__ . '/../models/PostgresDatabase.php'; // Load the database model
                $pdo = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
                $permissionGroups = $pdo->getPermissionGroupsByUser($_SESSION['user_id']);

                $mapper = [
                    'Accounts' => '/Accounts.php',
                    'Roles' => '/Roles.php',
                    'Colleges' => '/Colleges.php',
                    'Courses' => '/Courses.php',
                    'Templates' => '/Templates.php',
                    'Syllabus' => '/Syllabus.php'
                ];
                // Display the sidebar tabs that the user has permissions to
                foreach ($mapper as $key => $href) {
                    if (in_array($key, $permissionGroups)) {
                        echo "<li class='nav-item'>
                                <a class='nav-link linkstyle' href='$href'>$key</a>
                        </li>";
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
        </div>
    </div>
<!--</div>-->


<script>
  document.getElementById("toggleBtn").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
  });
</script>