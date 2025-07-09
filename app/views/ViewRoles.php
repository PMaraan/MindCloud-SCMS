<div class="container-fluid table-container">

  <!-- Top Bar -->
  <div class="top-bar d-flex align-items-center gap-2 mb-3">
    <input
      type="text"
      id="searchRolesInput"
      class="form-control search-input"
      placeholder="Search Roles"
    />
    <a href="WorkspaceComponent.php?page=add_role" class="btn btn-primary btn_addrole">
      <i class="bi bi-plus"></i> Add Roles
    </a>
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
            echo "<td class='role-manage'>
                    <a href='WorkspaceComponent.php?page=edit_role&name={$row[0]}' title='Edit'>
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
