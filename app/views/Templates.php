<!-- Include Bootstrap CSS and Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- Header with Filter Button -->
<div class="d-flex justify-content-between align-items-center">
  <h4 class="fw-bold mb-0">Create a Template</h4>

  <div class="d-flex justify-content-end" style="width: 200px;">
    <div class="dropdown" style="margin-right: 14px;">
      <button class="btn btn-light dropdown-toggle p-2" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-funnel-fill" style="color: #680404;"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
        <li><a class="dropdown-item" href="#">Recent</a></li>
        <li><a class="dropdown-item" href="#">A-Z</a></li>
        <li><a class="dropdown-item" href="#">Issued Only</a></li>
        <li><a class="dropdown-item" href="#">Draft Only</a></li>
      </ul>
    </div>
  </div>
</div>

<!-- TEST BUTTON FOR ADDING TEMPLATE CARD -->
<div class="mb-3">
  <button id="testInsertCardBtn" class="btn btn-outline-primary">
    <i class="bi bi-plus-circle"></i> Test Insert Card
  </button>
</div>

<!-- Template Card Grid -->
<div id="template-list" class="d-flex flex-wrap gap-4 justify-content-start">

  <!-- "+" Add Template Link Card -->
  <a href="TemplateBuilder.php" class="template-card d-flex justify-content-center align-items-center text-decoration-none text-dark">
    <span class="display-2 fw-light">+</span>
  </a>

  <!-- Template Cards -->
  <div class="template-card position-relative">
    <span class="badge status-badge bg-secondary">Draft</span>
    <div class="dropdown position-absolute top-0 end-0 m-2">
      <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" style="font-size: 1.2rem; color: #555;"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#">Delete</a></li>
      </ul>
    </div>
    <div class="template-footer text-white fw-bold text-center">Template 1</div>
  </div>

  <div class="template-card position-relative">
    <span class="badge status-badge bg-success">Issued</span>
    <div class="dropdown position-absolute top-0 end-0 m-2">
      <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" style="font-size: 1.2rem; color: #555;"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#">Delete</a></li>
      </ul>
    </div>
    <div class="template-footer text-white fw-bold text-center">Template 2</div>
  </div>
  
  <div class="template-card position-relative">
    <span class="badge status-badge bg-success">Issued</span>
    <div class="dropdown position-absolute top-0 end-0 m-2">
      <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" style="font-size: 1.2rem; color: #555;"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#">Delete</a></li>
      </ul>
    </div>
    <div class="template-footer text-white fw-bold text-center">Template 2</div>
  </div>
  
  <div class="template-card position-relative">
    <span class="badge status-badge bg-success">Issued</span>
    <div class="dropdown position-absolute top-0 end-0 m-2">
      <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" style="font-size: 1.2rem; color: #555;"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#">Delete</a></li>
      </ul>
    </div>
    <div class="template-footer text-white fw-bold text-center">Template 2</div>
  </div>
  
  <div class="template-card position-relative">
    <span class="badge status-badge bg-success">Issued</span>
    <div class="dropdown position-absolute top-0 end-0 m-2">
      <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" style="font-size: 1.2rem; color: #555;"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#">Delete</a></li>
      </ul>
    </div>
    <div class="template-footer text-white fw-bold text-center">Template 2</div>
  </div>
  
  <div class="template-card position-relative">
    <span class="badge status-badge bg-success">Issued</span>
    <div class="dropdown position-absolute top-0 end-0 m-2">
      <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" style="font-size: 1.2rem; color: #555;"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#">Delete</a></li>
      </ul>
    </div>
    <div class="template-footer text-white fw-bold text-center">Template 2</div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/Templates.js"></script>
