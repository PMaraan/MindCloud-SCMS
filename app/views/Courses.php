<?php
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  $query = $db->getAllCourses(); // connect to data controller in the future
  if ($query && $query['success']) {
    $courses = $query['db'];
  } else {
    $error = $query['error'] ?? 'Unknown error';
    echo "<script>alert('Error: " . addslashes($error) . "');</script>";
  }
?>

  <!-- Colleges -->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Courses</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Courses_includes/SearchBar.php';
    ?>

    <!-- Create College -->
    <?php
      //include_once __DIR__ . '/Courses_includes/CreateCollegeModal.php';
    ?>

    <!-------------------Edit College Modal---------------------->
    <?php
      //include_once __DIR__ . '/Courses_includes/EditCollegeModal.php';
    ?>

    <!-- Courses Table -->
    <?php
      include_once __DIR__ . '/Courses_includes/CoursesTable.php'
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <!--<script src="../../public/assets/js/Courses.js" defer></script>-->
