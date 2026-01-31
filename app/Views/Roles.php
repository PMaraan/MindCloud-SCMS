<?php
  $db = new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
  $roles = $db->getAllRoles(); // connect to data controller in the future

?>

  <!-- Roles -->
  <div class="container-fluid"> <!--container-fluid open-->
    <h2>Roles</h2>
    <!-- Search + Edit Controls -->
     <?php
      include_once __DIR__ . '/Roles_includes/SearchBar.php';
    ?>

    <!-- Create Role -->
    <?php
      include_once __DIR__ . '/Roles_includes/CreateRoleModal.php';
    ?>

    <!-------------------Edit Role Modal---------------------->
    <?php
      include_once __DIR__ . '/Roles_includes/EditRoleModal.php';
    ?>

    <!-- Role Table -->
    <?php
      include_once __DIR__ . '/Roles_includes/RolesTable.php'
    ?>

  </div><!--container-fluid close-->
  <!-- JS Script -->
  <!-- <script src="../../public/assets/js/Roles.js" defer></script> -->
