<div class="faculty-table-container">

  <!-- Top Bar -->
  <!-- Top Bar -->
<div class="faculty-top-bar d-flex flex-wrap align-items-start gap-2 mb-3">

  <!-- Text Fields Group -->
  <div class="d-flex flex-grow-1 gap-2" style="min-width: 0;">
    <input
      type="text"
      id="facultyNameField"
      class="form-control faculty-input"
      placeholder="College Name"
    />
    <input
      type="text"
      id="facultyCodeField"
      class="form-control faculty-input"
      placeholder="College Code"
    />
  </div>

  <!-- Button Group -->
  <div class="d-flex flex-wrap gap-2 faculty-button-group">
    <a href="WorkspaceComponent.php?page=view_roles" class="btn btn-secondary btn-view-roles">
      <i class="bi bi-person-gear"></i> Roles
    </a>
    <a href="WorkspaceComponent.php?page=add_college" class="btn btn-primary btn-add-faculty">
      <i class="bi bi-plus"></i> Add Faculty
    </a>
  </div>

  <!-- Search Bar + Filter -->
  <div class="w-100 d-flex align-items-center gap-2 position-relative mt-2">
    <input
      type="text"
      id="facultyGeneralSearch"
      class="form-control flex-grow-1"
      placeholder="General Search"
    />
    <button
      class="btn btn-outline-secondary d-flex align-items-center justify-content-center dropdown-toggle"
      id="facultyFilterBtn"
      data-bs-toggle="dropdown"
      aria-expanded="false"
      title="Filter"
      type="button"
    >
      <i class="bi bi-funnel"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-end p-3" id="facultyRoleFilterContainer" style="min-width: 200px;">
      <p class="mb-2 fw-semibold">Filter by Role:</p>
      <div id="facultyRoleFilterOptions" class="d-flex flex-column gap-1 mb-2"></div>
    </div>
  </div>

</div>



  <!-- Table -->
  <div class="faculty-table-wrapper">
    <table class="table table-bordered table-hover" id="FacultyRolesTable">
      <thead class="faculty-table-header">
        <tr>
          <th>ID Number</th>
          <th>First Name</th>
          <th>M.I.</th>
          <th>Last Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Manage</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $data = [
            ['123456', 'John', 'D.', 'Doe', 'john.doe@example.com', 'VPAA'],
            ['789012', 'Jane', 'A.', 'Smith', 'jane.smith@example.com', 'Dean'],
            ['345678', 'Emily', 'B.', 'Johnson', 'emily.johnson@example.com', 'Chair'],
          ];

          foreach ($data as $row) {
            echo "<tr>";
            echo "<td>{$row[0]}</td>";
            echo "<td>{$row[1]}</td>";
            echo "<td>{$row[2]}</td>";
            echo "<td>{$row[3]}</td>";
            echo "<td>{$row[4]}</td>";
            echo "<td>{$row[5]}</td>";
            echo "<td class='manage-cell'>
                    <a href='WorkspaceComponent.php?page=edit_college={$row[1]}' title='Edit'>
                      <i class='bi bi-pencil-square'></i>
                    </a>
                  </td>";
            echo "</tr>";
          }
        ?>
      </tbody>
    </table>
    </table>

<!-- Add College Button (Bottom Center, Function-based) -->
<div class="d-flex justify-content-center my-4">
  <button class="btn btn-success" onclick="addCollege()">
    <i class="bi bi-check-lg"></i>Add College
  </button>
</div>

</div> <!-- end of .faculty-table-wrapper -->

  </div>

</div>
