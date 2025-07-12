<div class="container-fluid table-container">

  <!-- Page title -->
  <div class="title mb-3">Syllabus for Preparation</div>

  <!-- Search bar input -->
  <div class="top-bar d-flex align-items-center gap-2 mb-3">
    <input
      type="text"
      id="searchInput"
      class="form-control search-input"
      placeholder="Search syllabus..."
    />
  </div>

  <!-- Column headers for sorting -->
  <div class="file-header d-flex justify-content-between mb-2 fw-bold">
    <span data-sort="title">Syllabus Title <span id="titleSort">↕</span></span>
    <span data-sort="date">Date Submitted <span id="dateSort">↕</span></span>
  </div>

  <!-- File list container (populated by JS) -->
  <div class="file-list" id="fileList"></div>

</div>
