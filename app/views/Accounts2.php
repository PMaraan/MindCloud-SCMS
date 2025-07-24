<?php
/*
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  //$users = $db->getAllUsersWithRoles();
  $users = $db->getAllUsersAccountInfo();
*/
  $db = new DataController();
  $query = $db->getAllUsers(); // connect to data controller in the future
  if ($query && $query['success']) {
    $users = $query['db'];
  } else {
    $error = $query['error'] ?? 'Unknown error';
    echo "<script>alert('Error: " . addslashes($error) . "');</script>";    
  }
?>

  <!-- Accounts -->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Accounts</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Accounts_includes/SearchBar.php';
    ?>

    <!-- Create User -->
    <?php
      include_once __DIR__ . '/Accounts_includes/CreateUserModal.php';
    ?>

    <!-------------------Edit User Modal---------------------->
    <?php
      include_once __DIR__ . '/Accounts_includes/EditUserModal2.php';
    ?>

    <!-- Accounts Table -->
    <?php
      include_once __DIR__ . '/Accounts_includes/AccountsTable2.php'
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <!-- <script src="../../public/assets/js/accscript.js" defer></script> -->
