<?php
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  $users = $db->getAllUsersWithRoles();
?>


  <div class="container-fluid"> <!--container-fluid open-->

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
      include_once __DIR__ . '/Accounts_includes/EditUserModal.php';
    ?>

    <!-- Accounts Table -->
    <?php
      include_once __DIR__ . '/Accounts_includes/AccountsTable.php'
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <script src="../../public/assets/js/accscript.js"></script>
