<div class="container-fluid table-container">

  <!-- Top Bar -->
  <div class="top-bar d-flex flex-wrap align-items-start gap-2 mb-3">
    
    <!-- Text Fields (Inline) -->
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
    <div class="d-flex gap-2 ms-auto">
      <a href="WorkspaceComponent.php?page=filter_colleges" class="btn btn-secondary btn_filtercollege">
        <i class="bi bi-person-gear"></i> Roles
      </a>
      <a href="WorkspaceComponent.php?page=add_college" class="btn btn-primary btn_addcollege">
        <i class="bi bi-plus"></i> Add College
      </a>
    </div>

    <!-- General Search (Full width below) -->
    <div class="w-100">
      <input
        type="text"
        id="generalSearch"
        class="form-control mt-2"
        placeholder="General Search"
      />
    </div>

  </div>

  <!-- Table -->
  <div class="table-responsive-wrapper">
    <table class="table table-bordered table-hover" id="CollegeRolesTable">
      <thead class="table-header">
        <tr>
          <th>College</th>
          <th>Code</th>
          <th>Dean</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Manage</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $data = [
            ['College of Nursing', 'CON', 'Dr. Santos', 'nursing@example.com', '09123456789', 'Active'],
            ['College of Engineering', 'COE', 'Engr. Reyes', 'engineering@example.com', '09987654321', 'Inactive'],
            ['College of Arts and Sciences', 'CAS', 'Prof. Cruz', 'cas@example.com', '09112223344', 'Active'],
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
