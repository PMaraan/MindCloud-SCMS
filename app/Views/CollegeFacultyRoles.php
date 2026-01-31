<!-- Main container for full-width layout -->
<div class="container-fluid table-container">

  <!-- Top Bar for inputs, filters, and actions -->
  <div class="top-bar d-flex flex-wrap align-items-start gap-2 mb-3">

    <!-- Text Fields for filtering by college name or code -->
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

    <!-- Button Group for navigating to Roles or adding Faculty -->
    <div class="d-flex flex-wrap gap-2 button-group-container">
      <a href="WorkspaceComponent.php?page=view_roles" class="btn btn-secondary btn_viewroles">
        <i class="bi bi-person-gear"></i> Roles
      </a>
      <button class="btn btn-primary btn_addcollege" id="openAddFacultyBtn">
        <i class="bi bi-plus"></i> Add Faculty
      </button>
    </div>

    <!-- General search bar and filter dropdown -->
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

      <!-- Dropdown menu for role-based filtering -->
      <div class="dropdown-menu dropdown-menu-end p-3" id="roleFilterContainer" style="min-width: 200px;">
        <p class="mb-2 fw-semibold">Filter by Role:</p>
        <div id="roleFilterOptions" class="d-flex flex-column gap-1 mb-2"></div>
      </div>
    </div>
  </div>

  <!-- Table wrapper for responsive scrolling -->
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
      <tbody id="facultyTableBody">
        <!-- Example structure (hidden or via JS template) -->
        <!--
        <tr>
          <td>{ID}</td>
          <td>{First Name}</td>
          <td>{M.I.}</td>
          <td>{Last Name}</td>
          <td>{Email}</td>
          <td>{Role}</td>
          <td class="col-manage">
            <button class="btn btn-sm btn-outline-primary edit-btn" data-id="{ID}">
              <i class="bi bi-pencil-square"></i>
            </button>
          </td>
        </tr>
        -->
      </tbody>
    </table>

    <!-- Button to navigate to Add College page -->
    <div class="d-flex justify-content-end mt-3">
      <a href="WorkspaceComponent.php?page=college" class="btn btn-outline-success">
        <i class="bi bi-plus-lg"></i> Add College
      </a>
    </div>
  </div>

  <!-- Modal for Add/Edit Faculty functionality -->
  <div id="editFacultyModal" class="modal-overlay d-none">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Manage Faculty</h5>
        <button class="btn-close" id="modalCloseBtn" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Input for First Name -->
        <div class="mb-2">
          <label class="form-label">First Name</label>
          <input type="text" id="editFirstName" class="form-control" />
        </div>
        <!-- Input for Middle Initial -->
        <div class="mb-2">
          <label class="form-label">Middle Initial</label>
          <input type="text" id="editMiddleInitial" class="form-control" />
        </div>
        <!-- Input for Last Name -->
        <div class="mb-2">
          <label class="form-label">Last Name</label>
          <input type="text" id="editLastName" class="form-control" />
        </div>
        <!-- Input for ID Number -->
        <div class="mb-2">
          <label class="form-label">ID Number</label>
          <input type="text" id="editIdNumber" class="form-control" />
        </div>
        <!-- Input for Email -->
        <div class="mb-2">
          <label class="form-label">Email</label>
          <input type="email" id="editEmail" class="form-control" />
        </div>
        <!-- Dropdown for selecting Role -->
        <div class="mb-2">
          <label class="form-label">Role</label>
          <select id="editRole" class="form-select">
            <option>Dean</option>
            <option>Chair</option>
            <option>Professor</option>
          </select>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <!-- Delete, Save, and Add buttons -->
        <button class="btn btn-danger d-none" id="deleteFacultyBtn">Delete</button>
        <button class="btn btn-success" id="saveFacultyBtn">Save</button>
        <button class="btn btn-primary d-none" id="addFacultyBtn">Add</button>
      </div>
    </div>
  </div>

</div>
