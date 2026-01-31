<?php
/*
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  $query = $db->getAllCourses(); // connect to data controller in the future
  if ($query && $query['success']) {
    $courses = $query['db'];
  } else {
    $error = $query['error'] ?? 'Unknown error';
    echo "<script>alert('Error: " . addslashes($error) . "');</script>";
  }
  */
  $db = new Datacontroller();
?>

  <!------------------------- Courses ---------------------------->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Courses</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Courses_includes/SearchBar.php';
    ?>

    <!-------------------- Create Courses ---------------------------->
    <?php
    // check if user has permission to create courses
      $userHasPermission = $db->checkPermission('CourseCreation');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        //include_once __DIR__ . '/Courses_includes/CreateCourseModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!-------------------Edit Course Modal---------------------->
    <?php
      // check if user has permission to edit courses
      $userHasPermission = $db->checkPermission('CourseModification');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        //include_once __DIR__ . '/Courses_includes/EditCourseModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!--------------------- Courses Table -------------------------->
    <?php
      // check if user has permission to view courses
      $userHasPermission = $db->checkPermission('CourseViewing');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Courses_includes/CoursesTable.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
      
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <!--<script src="../../public/assets/js/Courses.js" defer></script>-->
