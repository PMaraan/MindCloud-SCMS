<div class="container-fluid syllabus-container">

  <!-- 🔍 Search bar row -->
  <div class="top-bar d-flex align-items-center mb-3" style="gap: 0.5rem;">
    <div class="flex-grow-1">
      <input
        type="text"
        id="searchInput"
        class="form-control search-input w-100"
        placeholder="Search syllabus..."
      />
    </div>
  </div>

  <!-- 🔽 Filter and Sort Row -->
  <div class="sort-filter-bar d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

    <!-- Left-aligned: Add Folder + Date sort buttons -->
    <div class="d-flex flex-wrap align-items-center gap-3">
      <button class="btn btn-primary" id="addFolderBtn">
        <i class="bi bi-folder-plus me-1"></i> Add Folder
      </button>

      <button class="btn btn-light sort-btn" data-sort="modified">
        Date Modified <i class="bi bi-caret-down-fill"></i>
      </button>
      <button class="btn btn-light sort-btn" data-sort="created">
        Date Created <i class="bi bi-caret-down-fill"></i>
      </button>
    </div>

    <!-- Right-aligned: File Name + Status -->
    <div class="d-flex flex-wrap align-items-center gap-3">
      <button class="btn btn-light sort-btn" data-sort="name">
        File Name <i class="bi bi-caret-down-fill"></i>
      </button>

      <button class="btn btn-light d-flex align-items-center gap-1" id="statusFilterBtn">
        Status <i class="bi bi-funnel"></i>
      </button>
    </div>

  </div>

  <!-- File list container -->
  <div class="file-list" id="fileList">
    <!-- Example card (dynamically generated) -->
    <div class="file-card d-flex justify-content-between align-items-center p-3 mb-2">
      <div class="file-info">
        <div class="file-name fw-semibold d-flex align-items-center">
          <i class="bi bi-file-earmark-text-fill me-2" style="color:#680404"></i>
          Sample Syllabus Title
        </div>
        <div class="file-status text-muted small d-flex align-items-center">
          Status: Pending
        </div>
      </div>
      <div class="file-dates text-end">
        <div class="date-created small text-muted">Created: Jul 21, 2025</div>
        <div class="date-edited small text-muted">Edited: Jul 22, 2025</div>
      </div>
    </div>
  </div>

</div>

<!-- 🧠 JS + Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/assets/js/Syllabus.js"></script>
