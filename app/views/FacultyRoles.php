<div class="container-fluid table-container">

  <!-- Top Bar -->
  <div class="top-bar d-flex flex-wrap align-items-start gap-2 mb-3">

    <!-- Text Fields -->
    <div class="d-flex gap-2 flex-wrap" style="flex: 1 1 auto;">
      <input
        type="text"
        id="collegeNameField"
        class="form-control college-textfield"
        placeholder="College Name"
        style="width: 200px;"
      />
      <input
        type="text"
        id="collegeCodeField"
        class="form-control college-textfield"
        placeholder="College Code"
        style="width: 200px;"
      />
    </div>

    <!-- Buttons -->
    <div class="d-flex flex-wrap gap-2 button-group-container">
      <a href="WorkspaceComponent.php?page=filter_colleges" class="btn btn-secondary btn_filtercollege">
        <i class="bi bi-person-gear"></i> Roles
      </a>
      <a href="WorkspaceComponent.php?page=add_college" class="btn btn-primary btn_addcollege">
        <i class="bi bi-plus"></i> Add Faculty
      </a>
    </div>

    <!-- Search + Filter -->
    <div class="w-100 d-flex align-items-center gap-2 position-relative">
      <input
        type="text"
        id="generalSearch"
        class="form-control flex-grow-1"
        placeholder="General Search"
      />
      <button
        class="btn btn-outline-secondary d-flex align-items-center justify-content-center dropdown-toggle"
        id="filterBtn"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        title="Filter"
        type="button"
      >
        <i class="bi bi-funnel"></i>
      </button>

      <!-- Filter Dropdown -->
      <div class="dropdown-menu dropdown-menu-end p-3" id="roleFilterContainer" style="min-width: 200px;">
        <p class="mb-2 fw-semibold">Filter by Role:</p>
        <div id="roleFilterOptions" class="d-flex flex-column gap-1 mb-2"></div>

      </div>
    </div>

  </div>

  <!-- Table -->
  <div class="table-responsive-wrapper">
    <table class="table table-bordered table-hover" id="CollegeRolesTable">
      <thead class="table-header">
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
            echo "<td class='col-name'>{$row[0]}</td>";
            echo "<td class='col-code'>{$row[1]}</td>";
            echo "<td>{$row[2]}</td>";
            echo "<td>{$row[3]}</td>";
            echo "<td>{$row[4]}</td>";
            echo "<td>{$row[5]}</td>";
            echo "<td class='col-manage'>
                    <a href='WorkspaceComponent.php?page=edit_college={$row[1]}' title='Edit'>
                      <i class='bi bi-pencil-square'></i>
                    </a>
                  </td>";
            echo "</tr>";
          }
        ?>
      </tbody>
    </table>
  </div>

</div>
