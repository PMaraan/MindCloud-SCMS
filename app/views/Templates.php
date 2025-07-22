<div class="container-fluid"><!-- 📦 Main Container -->

  <!-- 📌 Header with Filter Dropdown -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Create a Template</h4>

    <!-- 🔽 Filter Dropdown -->
    <div class="d-flex" style="width: 200px;">
      <div class="dropdown me-3">
        <button class="btn btn-light dropdown-toggle p-2" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-funnel-fill" style="color:#680404"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
          <li><a class="dropdown-item" href="#" data-filter="recent">Recent</a></li>
          <li><a class="dropdown-item" href="#" data-filter="az">A‑Z</a></li>
          <li><a class="dropdown-item" href="#" data-filter="issued">Issued Only</a></li>
          <li><a class="dropdown-item" href="#" data-filter="draft">Draft Only</a></li>
        </ul>
      </div>
    </div>
  </div>

  <!-- ➕ Add New Template Button -->
  <button id="testInsertCardBtn" class="btn btn-outline-primary mb-4">
    <i class="bi bi-plus-circle"></i> Test Insert Card
  </button>

  <!-- 🧩 Template Card Grid -->
  <div id="template-list" class="d-flex flex-wrap gap-4">
    
    <!-- 🟰 "+" Card (Static Link to Builder) -->
    <a href="TemplateBuilder.php"
       class="template-card d-flex justify-content-center align-items-center text-decoration-none text-dark"
       id="add-template-card">
      <span class="display-2 fw-light">+</span>
    </a>

    <!-- 🛑 Template cards will be dynamically injected here via JS -->
    <!-- Example of dynamic template card structure:
    <div class="template-card" data-template-id="123" data-status="draft">
      <span class="badge status-badge bg-secondary">Draft</span>
      <div class="dropdown position-absolute top-0 end-0 m-2">
        <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown">
          <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="#">Edit</a></li>
          <li><a class="dropdown-item" href="#" data-action="delete">Delete</a></li>
        </ul>
      </div>
      <div class="template-footer">Template Name</div>
    </div>
    -->

  </div><!-- /#template-list -->

</div><!-- /.container-fluid -->

<!-- ✅ Required for Bootstrap dropdowns to function -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
