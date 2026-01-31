<?php
/*
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  //$users = $db->getAllUsersWithRoles();
  $users = $db->getAllUsersAccountInfo();
*/
  $db = new DataController();
  
?>

  <!-- Accounts -->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Accounts</h2>
    <!-- Search + Edit Controls -->
    <?php
      include_once __DIR__ . '/Accounts_includes/SearchBar.php';
    ?>

    <!----------------------- Create User -------------------------->
    <?php
      // check if user has permission to create accounts
      $userHasPermission = $db->checkPermission('AccountCreation');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Accounts_includes/CreateUserModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!------------------- Accounts Table ------------------------>
    <?php
      // check if user has permission to view accounts
      $userHasPermission = $db->checkPermission('AccountViewing');
      
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Accounts_includes/AccountsTable2.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!-------------------Edit User Modal---------------------->
    <?php
      // check if user has permission to modify accounts
      $userHasPermission = $db->checkPermission('AccountModification');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Accounts_includes/EditUserModal2.php';
        //echo "<h1>Hello</h1>";
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

    <!-------------------Delete User Modal---------------------->
    <?php
      // check if user has permission to delete accounts
      $userHasPermission = $db->checkPermission('AccountDeletion');
      if ($userHasPermission['success'] === true && $userHasPermission['hasPermission'] === true) {
        include_once __DIR__ . '/Accounts_includes/DeleteUserModal.php';
      } else {
        echo htmlspecialchars($userHasPermission['error']);
      }
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <!-- <script src="../../public/assets/js/accscript.js" defer></script> -->
