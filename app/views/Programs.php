<?php
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  $query = $db->getAllPrograms(); // connect to data controller in the future
  if ($query && $query['success']) {
    $programs = $query['db'];
  } else {
    $error = $query['error'] ?? 'Unknown error';
    echo "<script>alert('Error: " . addslashes($error) . "');</script>";
  }
?>

  <!-- Programs -->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Programs</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Programs_includes/SearchBar.php';
    ?>

    <!-- Create Program -->
    <?php
      //include_once __DIR__ . '/Programs_includes/CreateProgramModal.php';
    ?>

    <!-------------------Edit Program Modal---------------------->
    <?php
      //include_once __DIR__ . '/Programs_includes/EditProgramModal.php';
    ?>

    <!-- Programs Table -->
    <?php
      include_once __DIR__ . '/Programs_includes/ProgramsTable.php'
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <!--<script src="../../public/assets/js/Programs.js" defer></script>-->
