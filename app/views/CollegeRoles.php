<div class="container-fluid table-container">

  <!-- Top Bar -->
  <div class="top-bar d-flex align-items-center gap-2 mb-3">
    <input
      type="text"
      id="searchInput"
      class="form-control search-input"
      placeholder="Search Colleges"
    />
    <a href="WorkspaceComponent.php?page=add_college" class="btn btn-primary btn_addcollege">
      <i class="bi bi-plus"></i> Add College
    </a>
  </div>

  <!-- Table -->
  <div class="table-responsive-wrapper">
    <table class="table table-bordered table-hover" id="CollegeRolesTable">
      <thead class="table-header">
        <tr>
          <th style="width: 46.3%;">College</th>
          <th style="width: 46.3%;">College Code</th>
          <th style="width: 7.14%;">Manage</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $data = [
            ['College of Nursing', 'CON'],
            ['College of Engineering', 'COE'],
            ['College of Arts and Sciences', 'CAS'],
          ];

          foreach ($data as $row) {
            echo "<tr>";
            echo "<td class='col-name'>{$row[0]}</td>";
            echo "<td class='col-code'>{$row[1]}</td>";
            echo "<td class='col-manage'>
                    <a href='WorkspaceComponent.php?page=edit_college&code={$row[1]}' title='Edit'>
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
