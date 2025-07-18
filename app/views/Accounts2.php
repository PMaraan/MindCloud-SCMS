<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/models/MockDatabase.php';
require_once __DIR__ . '/../../app/models/PostgresDatabase.php';

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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/assets/css/HeaderComponent.css">
  
  <!-- Custom Styles -->
  <link rel="stylesheet" href="assets/css/accstyle.css">

  <?php // include 'xHeaderComponent.php'; ?>
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
    
      <!-- Button trigger modal -->
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        Create User
      </button>
      <!--modal-->
      <div class="modal" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Create Account</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p>Please fill in the following details</p>
              <!-- Input for ID Number -->
              <div class="mb-2">
                <label class="form-label required">ID Number</label>
                <input type="text" id="editIdNumber" class="form-control" required/>
              </div>
              <!-- Input for First Name -->
              <div class="mb-2">
                <label class="form-label required">First Name</label>
                <input type="text" id="editFirstName" class="form-control" />
              </div>
              <!-- Input for Middle Initial -->
              <div class="mb-2">
                <label class="form-label">Middle Initial</label>
                <input type="text" id="editMiddleInitial" class="form-control" />
              </div>
              <!-- Input for Last Name -->
              <div class="mb-2">
                <label class="form-label required">Last Name</label>
                <input type="text" id="editLastName" class="form-control" />
              </div>              
              <!-- Input for Email -->
              <div class="mb-2">
                <label class="form-label required">Email</label>
                <input type="email" id="editEmail" class="form-control" />
              </div>
              <div class="mb-2">
                <label class="form-label">College</label>
                <select id="editRole" class="form-select">
                  <option>CCS</option>
                  <option>CEA</option>
                  <option>CFAD</option>
                </select>
              </div>
              <!-- Dropdown for selecting Role -->
              <div class="mb-2">
                <label class="form-label">Role</label>
                <select id="editRole" class="form-select">
                  <option>Professor</option>
                  <option>Chair</option>
                  <option>College Secretary</option>
                  <option>Dean</option>
                  <option>Secretary</option>
                  <option>Admin</option>
                  <option>Superadmin</option>
                </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary">Save changes</button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Accounts Table -->
    <table class=" table account-table table-bordered table-hover">
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



  <!-- JS Script -->
  <script src="assets/js/accscript.js"></script>
</body>
</html>
