<?php
/*
  $db = new DataController();
  $query = $db->getAllColleges();
  if ($query && $query['success']) {
    $colleges = $query['db'];
  } else {
    $error = $query['error'] ?? 'Unknown error';
    echo "<script>alert('Error: " . addslashes($error) . "');</script>";    
  }
    */
  $db = new Datacontroller();
?>

  <!-- Colleges -->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Colleges</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Colleges_includes/SearchBar.php';
    ?>

    <!-------------------- Create College ------------------------>
    <?php
      // check if user has permission to create colleges
      $userHasPermission = $db->checkPermission('CollegeCreation');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Colleges_includes/CreateCollegeModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!-------------------Edit College Modal---------------------->
    <?php
      // check if user has permission to edit colleges
      $userHasPermission = $db->checkPermission('CollegeModification');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Colleges_includes/EditCollegeModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!------------------- College Table ----------------------------->
    <?php
      // check if user has permission to view colleges
      $userHasPermission = $db->checkPermission('CollegeViewing');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Colleges_includes/CollegesTable.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!-------------------Delete College Modal---------------------->
    <?php
      // check if user has permission to delete colleges
      $userHasPermission = $db->checkPermission('CollegeDeletion');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Colleges_includes/DeleteCollegeModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <script src="../../public/assets/js/Colleges.js" defer></script>
