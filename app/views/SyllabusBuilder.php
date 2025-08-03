<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Syllabus Builder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../public/assets/css/SyllabusBuilder.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</head>
<body class="bg-light">

<!-- 🟨 TOP TOOLBAR -->
<div class="builder-header editor-toolbar d-flex align-items-center gap-2 p-2 border-bottom">
  <div class="position-absolute start-0 ms-2 d-flex align-items-center">
    <img src="../../public/assets/images/logo_lpu.png" alt="Logo" style="height: 64px;" />
  </div>
  <label class="form-label m-0 me-2" for="paperSize">Paper Size:</label>
  <select class="form-select form-select-sm w-auto" id="paperSize">
    <option value="A4">A4</option>
    <option value="Letter">Letter</option>
    <option value="Legal">Legal</option>
  </select>
  <button class="btn btn-sm btn-outline-secondary ms-2" id="addPageBtn">
    <i class="bi bi-file-earmark-plus"></i> Add Page
  </button>
  <select id="fontFamily" class="form-select form-select-sm w-auto ms-3">
    <option value="Arial">Arial</option>
    <option value="Georgia">Georgia</option>
    <option value="Courier New">Courier New</option>
    <option value="Times New Roman">Times New Roman</option>
  </select>
  <select id="fontSize" class="form-select form-select-sm w-auto">
    <option value="12">12</option>
    <option value="14">14</option>
    <option value="16">16</option>
    <option value="18">18</option>
    <option value="24">24</option>
    <option value="36">36</option>
  </select>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="bold"><b>B</b></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="italic"><i>I</i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="underline"><u>U</u></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="justifyLeft"><i class="bi bi-text-left"></i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="justifyCenter"><i class="bi bi-text-center"></i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="justifyRight"><i class="bi bi-text-right"></i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="undo"><i class="bi bi-arrow-counterclockwise"></i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="redo"><i class="bi bi-arrow-clockwise"></i></button>
  <div class="position-absolute end-0 me-3 d-flex align-items-center gap-2">
    <button id="sendTemplateBtn" class="btn btn-outline-primary">
      <i class="bi bi-send"></i>
    </button>
    <button id="saveTemplateBtn" class="btn btn-primary">
      <i class="bi bi-save"></i>
    </button>
  </div>
</div>

<!-- 🟥 SIDEBAR (Dynamic content: Comments or CILO Calculator) -->
<div id="palette-wrapper" class="d-flex flex-column p-3 bg-light border-end" style="width: 300px;">
  <div class="toggle-btn p-2 border-bottom">
    <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle" aria-label="Toggle Sidebar">
      <i class="bi bi-layout-sidebar-inset"></i>
    </button>
  </div>
  <div id="sidebarContent">
    <!-- This will be replaced dynamically -->
  </div>
</div>

<!-- 🟩 MAIN WORKSPACE -->
<div class="container mt-5 mb-3">

  <!-- Wrapper with margin-top to create space from top toolbar -->
  <div class="position-relative mt-4">

    <!-- Tabs appear visually attached to the white box -->
    <ul class="nav nav-tabs nav-fill w-100 position-absolute top-0 start-0 rounded-top border" id="syllabusTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active text-dark border" id="syllabus-tab" data-bs-toggle="tab" data-bs-target="#syllabus" type="button" role="tab">
      Syllabus
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link text-dark border" id="cilo-tab" data-bs-toggle="tab" data-bs-target="#cilo" type="button" role="tab">
      CILO Attachment
    </button>
  </li>
</ul>

    <!-- White box with padding top to fit the tabs inside -->
    <div class="white-box p-4 pt-5 shadow rounded-bottom bg-white" style="margin-top: 2.5rem;">
      <div class="tab-content" id="syllabusTabContent">
        <div class="tab-pane fade show active" id="syllabus" role="tabpanel">
          <div class="d-flex align-items-center justify-content-center mb-4" style="gap: 20px;">
            <img src="../../public/assets/images/lpu-logo.png" height="150" class="flex-shrink-0">
            <div class="text-center">
              <h5 class="fw-bold mb-1">Lyceum of the Philippines University</h5>
              <h6 class="fst-italic mb-0">College of Computer Studies</h6>
            </div>
            <img src="../../public/assets/images/lpu-logo.png" height="150" class="flex-shrink-0">
          </div>


        <div class="mb-4">
          <h4 class="fw-bold">Course Goals</h4>
          <textarea class="form-control border border-warning" rows="4" placeholder="Enter course goals here..."></textarea>
        </div>

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
                <td contenteditable="true"></td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-pane fade" id="cilo" role="tabpanel">
        <div class= mb-6 style="height: 3rem;"></div>
        <div class="table-responsive mb-4">
          <table class="table table-bordered text-center">
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
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
                <td contenteditable="true"></td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>

        <div class="mb-4 text-center">
          <button 
            class="btn d-flex flex-column align-items-center justify-content-center border border-secondary w-100"
            style="height: 200px; background-color: #f8f9fa;" 
            onclick="document.getElementById('attachmentInput').click();"
          >
            <i class="bi bi-paperclip" style="font-size: 3rem; color: #6c757d;"></i>
            <span class="mt-2 fw-bold text-secondary">Insert Attachment</span>
          </button>
          <input type="file" id="attachmentInput" class="d-none">
        </div>


      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Sidebar Toggle Logic -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebarToggle');     // ID: sidebarToggle
    const sidebar = document.getElementById('palette-wrapper');     // ID: palette-wrapper
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
    });
  });
</script>

<script>
  const sidebarContent = document.getElementById('sidebarContent');

  const commentsHTML = `
    <h6 class="fw-bold mb-3">Comments</h6>
    <div class="mb-2 border-bottom pb-2">
      <strong>Chair</strong> <small class="text-muted float-end">Aug 2, 2025</small>
      <p class="mb-0">Please revise CILO 3.1 to match assessment format.</p>
    </div>
    <div class="mb-2 border-bottom pb-2">
      <strong>Professor</strong> <small class="text-muted float-end">Aug 1, 2025</small>
      <p class="mb-0">Consider adjusting the assessment task weight.</p>
    </div>
  `;

  const calculatorHTML = `
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
        <option value="Yes">Y</option>
        <option value="No">N</option>
      </select>
    </div>
  `;

  const syllabusTab = document.getElementById('syllabus-tab');
  const ciloTab = document.getElementById('cilo-tab');

  function setSidebarContent(content) {
    sidebarContent.innerHTML = content;
  }

  syllabusTab.addEventListener('click', () => setSidebarContent(commentsHTML));
  ciloTab.addEventListener('click', () => setSidebarContent(calculatorHTML));

  // Initialize with Comments on page load
  document.addEventListener('DOMContentLoaded', () => {
    setSidebarContent(commentsHTML);
  });
</script>

</body>
</html>
