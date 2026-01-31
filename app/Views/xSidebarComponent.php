<?php
  $currentPage = $currentPage ?? ($_GET['page'] ?? 'index');
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
                // Map out the permmissions of the user
                // Display the sidebar tabs that the user has permissions to

                // this array is javascript frontend. create a php implementation and delete this
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

                // change logic to take list permissions from the database and create a list element for each permission
                foreach ($links as $key => $label) {
                    $activeClass = $currentPage === $key ? 'active' : '';
                    echo "<li class='nav-item'>
                            <a class='nav-link linkstyle $activeClass' href='Dashboard.php?page=$key'>$label</a>
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