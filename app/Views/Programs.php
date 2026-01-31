<?php
/*
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  $query = $db->getAllPrograms(); // connect to data controller in the future
  if ($query && $query['success']) {
    $programs = $query['db'];
  } else {
    $error = $query['error'] ?? 'Unknown error';
    echo "<script>alert('Error: " . addslashes($error) . "');</script>";
  }
    */
  $db = new DataController();
  
?>

  <!-------------------------- Programs -------------------------->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Programs</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Programs_includes/SearchBar.php';
    ?>

    <!-------------------- Create Program ----------------------->
    <?php
      // check if user has permission to create colleges
      $userHasPermission = $db->checkPermission('ProgramCreation');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Programs_includes/CreateProgramModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
      
    ?>

    <!-------------------Edit Program Modal---------------------->
    <?php
    // check if user has permission to edit colleges
      $userHasPermission = $db->checkPermission('ProgramModification');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        //include_once __DIR__ . '/Programs_includes/EditProgramModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
      
    ?>

    <!------------------- Programs Table ------------------------>
    <?php
      // check if user has permission to view colleges
      $userHasPermission = $db->checkPermission('ProgramViewing');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Programs_includes/ProgramsTable.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!-------------------Delete Program Modal---------------------->
    <?php
      // check if user has permission to delete colleges
      $userHasPermission = $db->checkPermission('ProgramDeletion');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        //include_once __DIR__ . '/Programs_includes/DeleteProgramModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <!--<script src="../../public/assets/js/Programs.js" defer></script>-->
