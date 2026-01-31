<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Syllabus Builder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../public/assets/css/template-builder.css">
</head>
<body>

<!-- 🟨 TOP TOOLBAR -->
<div class="builder-header editor-toolbar d-flex align-items-center gap-2 p-2 border-bottom">
  <!-- Logo -->
  <div class="position-absolute start-0 ms-2 d-flex align-items-center">
    <img src="../../public/assets/images/logo_lpu.png" alt="Logo" style="height: 64px;" />
  </div>

<!-- Tabs -->
<div class="tabs">
  <button class="tab-button active" onclick="switchTab('syllabus')">Syllabus</button>
  <button class="tab-button" onclick="switchTab('cilo')">CILO Attachment</button>
</div>

<!-- Syllabus Tab -->
<div id="syllabus" class="tab-content active">
  <div class="container mb-5">
    <!-- Header -->
    <div class="text-center my-4">
      <img src="../../public/assets/images/lpu-logo.png" height="100" class="me-3">
      <img src="../../public/assets/images/lpu-logo.png" height="100">
      <h5 class="mt-3 fw-bold">Lyceum of the Philippines University</h5>
      <h6 class="mb-4 fst-italic">College of Engineering, Computer Studies and Architecture</h6>
    </div>

    <!-- Course Goals -->
    <div class="mb-4">
      <h4 class="fw-bold mb-2">Course Goals</h4>
      <textarea class="form-control border border-warning" rows="4" placeholder="Enter course goals here..."></textarea>
    </div>

    <!-- Topics Table -->
    <div>
      <h4 class="fw-bold mb-3">Topics to be Covered</h4>
      <table class="table table-bordered">
        <thead class="table-light text-center">
          <tr>
            <th>Week</th>
            <th>Topics</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($i = 1; $i <= 10; $i++): ?>
            <tr>
              <td class="text-center"><?= $i ?></td>
              <td contenteditable="true" class="editable-cell"></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- CILO Tab -->
<div id="cilo" class="tab-content">
  <div class="container-fluid py-4">
    <!-- CILO Table -->
    <div class="mb-4">
      <h4 class="fw-bold">Course Intended Learning Outcomes (CILO)</h4>
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center">
          <thead class="table-light">
            <tr>
              <th>Course Intended Learning Outcomes</th>
              <th>Assessment Task</th>
              <th>Total Points Attainment</th>
              <th>Percentage</th>
              <th>Achievement of CILO</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < 5; $i++): ?>
              <tr>
                <td contenteditable="true" class="editable-cell"></td>
                <td contenteditable="true" class="editable-cell"></td>
                <td contenteditable="true" class="editable-cell"></td>
                <td contenteditable="true" class="editable-cell"></td>
                <td contenteditable="true" class="editable-cell"></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Insert Attachment -->
    <div class="mb-4">
      <h5 class="fw-bold">Insert Attachment</h5>
      <input type="file" class="form-control">
    </div>

    <!-- Sidebars -->
    <div class="row">
      <!-- CILO Calculator -->
      <div class="col-md-4 mb-3">
        <div class="border rounded p-3 bg-light">
          <h5 class="fw-bold mb-3">CILO Calculator</h5>
          <div class="mb-2">
            <label>Number of Students</label>
            <input type="number" class="form-control">
          </div>
          <div class="mb-2">
            <label>Passing Percentage</label>
            <input type="number" class="form-control">
          </div>
          <div class="mb-2">
            <label>Number of Passing Students</label>
            <input type="number" class="form-control">
          </div>
          <div class="mb-2">
            <label>Achievement of CILO</label>
            <select class="form-select">
              <option value="Y">Yes</option>
              <option value="N">No</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Comments -->
      <div class="col-md-8 mb-3">
        <div class="border rounded p-3 bg-light">
          <h5 class="fw-bold mb-3">Comments</h5>
          <div class="mb-2 border-bottom pb-2">
            <strong>Chair</strong> <small class="text-muted float-end">Aug 2, 2025</small>
            <p class="mb-0">Please revise CILO 3.1 to match assessment format.</p>
          </div>
          <div class="mb-2 border-bottom pb-2">
            <strong>Professor</strong> <small class="text-muted float-end">Aug 1, 2025</small>
            <p class="mb-0">Consider adjusting the assessment task weight.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="../../public/assets/js/syllabus-builder.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

</body>
</html>
