<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/MockDatabase.php';
require_once __DIR__ . '/../app/models/PostgresDatabase.php';

//require_once __DIR__ . '/../middleware/auth.php'; // Access control
/*require_once USE_MOCK
    ? __DIR__ . '/../app/models/MockDatabase.php'
    : __DIR__ . '/../app/models/PostgresDatabase.php';
*/
$db = USE_MOCK
    ? new MockDatabase()
    : new PostgresDatabase(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);

$users = $db->getAllUsersWithRoles();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LPU-SCMS Accounts</title>

  <!-- Bootstrap and Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  
  <!-- Custom Styles -->
  <link rel="stylesheet" href="assets/css/accstyle.css">

  <?php include 'nav-bar.php'; ?>
</head>

<body>
  <div class="container-fluid">

    <!-- Search + Edit Controls -->
    <div class="container-search-bar">
      <div class="row mb-3 align-items-center">

        <!-- Search Input -->
        <div class="col-12 col-md-8">
          <div class="input-group">
            <input type="text" id="search" class="form-control" placeholder="Search" />
            <button class="btn filter-btn" type="button">
              <i class="bi bi-funnel-fill"></i>
            </button>
          </div>
        </div>

        <!-- Edit Buttons -->
        <div class="col-12 col-md-4 mt-2 mt-md-0">
          <div id="edit-controls" class="d-flex justify-content-md-end justify-content-start gap-2 flex-wrap">
            <button id="edit-btn" class="btn btn-outline-primary d-none d-md-inline-flex">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button id="edit-btn-mobile" class="btn btn-outline-primary d-flex d-md-none w-100">
              <i class="bi bi-pencil-square me-1"></i> Edit Mode
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- Accounts Table -->
    <table class="account-table">
      <thead>
        <tr>
          <th>ID Number</th>
          <th>Email</th>
          <th>First Name</th>
          <th>M.I.</th>
          <th>Last Name</th>
          <th>Roles</th>
        </tr>
      </thead>
      <tbody id="table-body">
         <?php foreach ($users as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['id_no']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['fname']) ?></td>
          <td><?= htmlspecialchars($user['mname']) ?></td>
          <td><?= htmlspecialchars($user['lname']) ?></td>
          <td>
            <?php foreach (explode(',', $user['roles']) as $role): ?>
              <span class="role-badge"><?= htmlspecialchars(trim($role)) ?></span>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach;
        /* to be deleted
        <tr>
          <td>20231001</td>
          <td>george@lpunetwork.edu.ph</td>
          <td>George</td>
          <td>T</td>
          <td>Santos</td>
          <td class="role-cell" data-editing="false">
            <span class="role-badge Dean" onclick="editRole(this)">Dean</span>
            <button class="btn btn-sm btn-outline-success add-role-btn d-none" onclick="addRole(this)">+</button>
          </td>
        </tr>
        */
      ?>
      </tbody>
    </table>

  </div>

  <!-- JS Script -->
  <script src="assets/js/accscript.js"></script>
</body>
</html>
