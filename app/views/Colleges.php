<?php
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  $colleges = $db->getAllColleges(); // connect to data controller in the future
  
?>

  <!-- Colleges -->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Colleges</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Colleges_includes/SearchBar.php';
    ?>

    <!-- Create College -->
    <?php
      include_once __DIR__ . '/Colleges_includes/CreateCollegeModal.php';
    ?>

    <!-------------------Edit College Modal---------------------->
    <?php
      include_once __DIR__ . '/Colleges_includes/EditCollegeModal.php';
    ?>

    <!-- College Table -->
    <?php
      include_once __DIR__ . '/Colleges_includes/CollegesTable.php'
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <script src="../../public/assets/js/Colleges.js" defer></script>
