<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LPU-SCMS | Design A Template</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="template_menu.css">
</head>
<body>

  <div class = "container-fluid">
  <div class = "row full-height">

    <div class="col-2 sidebar d-flex flex-column align-items-center py-4">
  <!-- Icon (Sample) -->
  <img src="sample-icon.png" alt="Icon" class="mb-4 sidebar-icon">

  <!-- Menu Containers -->
  <a href="#" class="w-100 text-decoration-none">
  <div class="menu-item active d-flex align-items-center px-3">
    <img src="icons/design-icon.png" alt="Icon" class="menu-icon me-3">
    <span class="flex-grow-1 text-start">Design A Template</span>
  </div>
</a>
<a href="for-approval.php" class="w-100 text-decoration-none">
  <div class="menu-item d-flex align-items-center px-3">
    <img src="icons/approval-icon.png" alt="Icon" class="menu-icon me-3">
    <span class="flex-grow-1 text-start">For Approval</span>
  </div>
</a>
<a href="templates.php" class="w-100 text-decoration-none">
  <div class="menu-item d-flex align-items-center px-3">
    <img src="icons/templates-icon.png" alt="Icon" class="menu-icon me-3">
    <span class="flex-grow-1 text-start">Templates</span>
  </div>
</a>
<a href="syllabus.php" class="w-100 text-decoration-none">
  <div class="menu-item d-flex align-items-center px-3">
    <img src="icons/syllabus-icon.png" alt="Icon" class="menu-icon me-3">
    <span class="flex-grow-1 text-start">Syllabus</span>
  </div>
</a>
</div>


    <div class = "col-10 workspace">
        <div class="container py-5">
  <h4 class="mb-4 fw-bold">Choose a Template</h4>
  <div class="d-flex justify-content-start gap-4 flex-wrap">

    <!-- Template Card 1 -->
    <div class="template-card d-flex flex-column justify-content-center align-items-center">
      <img src="icons/template1.png" class="template-thumbnail mb-2">
      <span class="template-title">ABET</span>
    </div>

    <!-- Template Card 2 -->
    <div class="template-card d-flex flex-column justify-content-center align-items-center">
      <img src="icons/template2.png" class="template-thumbnail mb-2">
      <span class="template-title">PTC</span>
    </div>

    <!-- Custom Template (Add New) -->
    <div class="template-card add-template d-flex justify-content-center align-items-center">
      <span class="display-4 text-muted">+</span>
    </div>

  </div>
</div>



  
    </div>

  </div>



  </div>


</body>
</html>