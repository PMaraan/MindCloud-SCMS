<div class="container-fluid table-container">
  
  <!-- Top Bar -->
  <div class="top-bar d-flex align-items-center gap-2 mb-3">
    <input
      type="text"
      id="searchRolesInput"
      class="form-control search-input"
      placeholder="Search Roles"
    />
    <button class="btn btn-primary btn_addrole" id="openAddRoleModal">
      <i class="bi bi-plus"></i> Add Roles
    </button>
  </div>

  <!-- Table -->
  <div class="table-responsive-wrapper">
    <table class="table table-bordered table-hover" id="RolesTable">
      <thead class="table-header">
        <tr>
          <th style="width: 46.3%;">Role</th>
          <th style="width: 46.3%;">Number of Members</th>
          <th style="width: 7.14%;">Manage</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $roles = [
            ['Dean', 1],
            ['Chair', 2],
            ['Faculty', 6],
          ];

          foreach ($roles as $row) {
            echo "<tr>";
            echo "<td class='role-name'>{$row[0]}</td>";
            echo "<td class='role-count'>{$row[1]}</td>";
            echo "<td class='role-manage text-center'>
                    <button 
                      class='btn btn-sm btn-outline-secondary edit-role-btn' 
                      data-role-name='{$row[0]}'
                      data-role-count='{$row[1]}'
                      title='Edit'>
                      <i class='bi bi-pencil-square'></i>
                    </button>
                  </td>";
            echo "</tr>";
          }
        ?>
      </tbody>
    </table>

    <!-- Overlay Modal -->
    <div id="addRoleModal" class="add-role-modal">
      <div class="add-role-container d-flex flex-column position-relative">
        
        <!-- Close Button -->
        <button
          type="button"
          class="btn-close close-add-role"
          aria-label="Close"
          style="position: absolute; top: 1rem; right: 1rem;"
        ></button>

        <!-- Content Block -->
        <div class="flex-grow-1">
          <!-- Dynamic Title -->
          <h5 id="roleModalTitle" class="mb-3">Add New Role</h5>

          <!-- Role Name Input -->
          <div class="mb-3">
            <label for="roleName" class="form-label">Role Name</label>
            <input
              type="text"
              class="form-control"
              id="roleName"
              placeholder="Enter role name"
            />
          </div>

          <!-- Permissions List -->
          <div class="permissions-list mb-3">
            <div class="permission-item d-flex justify-content-between align-items-center mb-2">
              <span>Permission 1</span>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="permManageUsers" />
              </div>
            </div>
            <div class="permission-item d-flex justify-content-between align-items-center mb-2">
              <span>Permission 2</span>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="permAccessReports" />
              </div>
            </div>
            <!-- Add more permissions here if needed -->
          </div>
        </div>

        <!-- Modal Action Buttons -->
        <button class="btn btn-primary w-100 mt-3" id="confirmAddRole">Add Role</button>
        <button class="btn btn-success w-100 mt-3 d-none" id="saveEditRole">Save Changes</button>
        <button class="btn btn-danger w-100 mt-2 d-none" id="deleteRole">Delete Role</button>


      </div>
    </div>

  </div>
</div>
