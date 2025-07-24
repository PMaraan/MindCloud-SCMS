<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Grid‑Based Syllabus Template Builder</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- Custom Styles -->
  <link href="../../public/assets/css/TemplateBuilder.css" rel="stylesheet">

  <!-- Inline Utility Styles -->
  <style>
    td.multi-selected {
      outline: 2px dashed #007bff;
      background-color: #eaf3ff;
    }
  </style>
</head>

<body>

<!-- 🟦 SIDEBAR / PALETTE -->
<div id="palette-wrapper" class="d-flex flex-column"> <!-- ID: palette-wrapper -->
  <!-- Toggle Sidebar Button -->
  <div class="toggle-btn p-2 border-bottom">
    <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle" aria-label="Toggle Sidebar"> <!-- ID: sidebarToggle -->
      <i class="bi bi-layout-sidebar-inset"></i>
    </button>
  </div>

  <!-- Sidebar Palette Items -->
  <div id="palette" class="flex-grow-1 overflow-auto"> <!-- ID: palette -->
    <div class="p-3">
      <div class="draggable" draggable="true" data-type="label"><i class="bi bi-tag-fill me-1"></i>Label</div>
      <div class="draggable" draggable="true" data-type="paragraph"><i class="bi bi-file-text-fill me-1"></i>Paragraph</div>
      <div class="draggable" draggable="true" data-type="text-field"><i class="bi bi-input-cursor-text me-1"></i>Text Field</div>
      <div class="draggable" draggable="true" data-type="text-3"><i class="bi bi-textarea-resize me-1"></i>Text Area</div>
      <div class="draggable" draggable="true" data-type="table"><i class="bi bi-table me-1"></i>Table</div>
      <div class="draggable" draggable="true" data-type="signature"><i class="bi bi-pen-fill me-1"></i>Signature Field</div>
    </div>
  </div>
</div>

<!-- 🟨 TOP TOOLBAR -->
<div class="builder-header editor-toolbar d-flex align-items-center gap-2 p-2 border-bottom bg-light">
  <!-- Logo -->
  <div class="position-absolute start-0 ms-2 d-flex align-items-center">
    <img src="../../public/assets/images/logo_lpu.png" alt="Logo" style="height: 64px;" />
  </div>

  <!-- Paper Size Selector -->
  <label class="form-label m-0 me-2" for="paperSize">Paper Size:</label>
  <select class="form-select form-select-sm w-auto" id="paperSize"> <!-- ID: paperSize -->
    <option value="A4">A4</option>
    <option value="Letter">Letter</option>
    <option value="Legal">Legal</option>
  </select>

  <!-- Add Page -->
  <button class="btn btn-sm btn-outline-secondary ms-2" id="addPageBtn"> <!-- ID: addPageBtn -->
    <i class="bi bi-file-earmark-plus"></i> Add Page
  </button>

  <!-- Font Selectors -->
  <select id="fontFamily" class="form-select form-select-sm w-auto ms-3"> <!-- ID: fontFamily -->
    <option value="Arial">Arial</option>
    <option value="Georgia">Georgia</option>
    <option value="Courier New">Courier New</option>
    <option value="Times New Roman">Times New Roman</option>
  </select>

  <select id="fontSize" class="form-select form-select-sm w-auto"> <!-- ID: fontSize -->
    <option value="12">12</option>
    <option value="14">14</option>
    <option value="16">16</option>
    <option value="18">18</option>
    <option value="24">24</option>
    <option value="36">36</option>
  </select>

  <!-- Text Format Buttons -->
  <button class="btn btn-sm btn-outline-secondary" data-cmd="bold"><b>B</b></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="italic"><i>I</i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="underline"><u>U</u></button>

  <!-- Text Alignment -->
  <button class="btn btn-sm btn-outline-secondary" data-cmd="justifyLeft"><i class="bi bi-text-left"></i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="justifyCenter"><i class="bi bi-text-center"></i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="justifyRight"><i class="bi bi-text-right"></i></button>

  <!-- Undo / Redo -->
  <button class="btn btn-sm btn-outline-secondary" data-cmd="undo"><i class="bi bi-arrow-counterclockwise"></i></button>
  <button class="btn btn-sm btn-outline-secondary" data-cmd="redo"><i class="bi bi-arrow-clockwise"></i></button>
  <button class="btn btn-sm btn-outline-secondary" id="clearFormat"><i class="bi bi-eraser"></i></button> <!-- ID: clearFormat -->

  <!-- Send & Save -->
  <div class="position-absolute end-0 me-3 d-flex align-items-center gap-2">
    <button id="sendTemplateBtn" class="btn btn-outline-primary"> <!-- ID: sendTemplateBtn -->
      <i class="bi bi-send"></i>
    </button>
    <button id="saveTemplateBtn" class="btn btn-primary"> <!-- ID: saveTemplateBtn -->
      <i class="bi bi-save"></i>
    </button>
  </div>
</div>

<!-- 🟩 TABLE PROPERTIES TOOLBAR -->
<div id="tableToolbar" class="table-toolbar d-none"> <!-- ID: tableToolbar -->
  <!-- Row Controls -->
  <div class="btn-group" role="group" aria-label="Rows">
    <button class="btn btn-sm btn-outline-secondary" data-table-cmd="AddRow"><i class="bi bi-arrow-bar-down"></i> Add Row</button>
    <button class="btn btn-sm btn-outline-danger" data-table-cmd="deleteRow"><i class="bi bi-trash"></i> Delete Row</button>
  </div>

  <!-- Column Controls -->
  <div class="btn-group" role="group" aria-label="Columns">
    <button class="btn btn-sm btn-outline-secondary" data-table-cmd="addColLeft"><i class="bi bi-arrow-bar-left"></i> Col Left</button>
    <button class="btn btn-sm btn-outline-secondary" data-table-cmd="addColRight"><i class="bi bi-arrow-bar-right"></i> Col Right</button>
    <button class="btn btn-sm btn-outline-danger" data-table-cmd="deleteCol"><i class="bi bi-trash"></i> Delete Col</button>
  </div>

  <!-- Merge Controls -->
  <div class="btn-group" role="group" aria-label="Merge">
    <button class="btn btn-sm btn-outline-primary" data-table-cmd="merge"><i class="bi bi-merge"></i> Merge</button>
    <button class="btn btn-sm btn-outline-warning" data-table-cmd="unmergeCells"><i class="bi bi-scissors"></i> Unmerge</button>
  </div>
</div>

<!-- ⬜ WORKSPACE -->
<div id="workspace"></div> <!-- ID: workspace -->

<!-- 🔻 SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../../public/assets/js/TemplateBuilder.js"></script>

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

</body>
</html>

<!--------------------------------------------------------
ISSUE TEMPLATE MODAL CODE >:( TOO TIRED TO PUT IN NEW FILE 
---------------------------------------------------------->
<div class="modal fade" id="issueTemplateModal" tabindex="-1" aria-labelledby="issueTemplateModalLabel" aria-hidden="true"> <!-- ID: issueTemplateModal -->
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 rounded-3 shadow">

      <!-- Modal Header -->
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title w-100 text-center" id="issueTemplateModalLabel">Issue Template</h5> <!-- ID: issueTemplateModalLabel -->
        <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body pt-0">
        <form id="issueTemplateForm" class="row g-3"> <!-- ID: issueTemplateForm -->

          <!-- College Field -->
          <!-- Editable field with datalist suggestions -->
          <div class="col-12">
            <label for="collegeInput" class="form-label">College</label>
            <input type="text" id="collegeInput" class="form-control" list="collegeSuggestions" placeholder="Enter college name" required> <!-- ID: collegeInput -->
            <datalist id="collegeSuggestions"> <!-- ID: collegeSuggestions -->
              <option value="College of Computer Science">
              <option value="College of Business">
              <option value="College of Education">
              <option value="College of Arts and Sciences">
            </datalist>
          </div>

          <!-- Professors Input -->
          <!-- Accepts comma-separated professor names -->
          <div class="col-12">
            <label for="professorsInput" class="form-label">Professors</label>
            <input type="text" id="professorsInput" class="form-control" placeholder="Enter professor names, separated by commas" required> <!-- ID: professorsInput -->
            <small class="form-text text-muted">
              Example: Prof. Alice, Prof. Bob, Prof. Carol
            </small>
          </div>

        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer justify-content-center border-top-0">
        <button type="button" class="btn btn-primary px-4" id="confirmIssueBtn">Issue</button> <!-- ID: confirmIssueBtn -->
      </div>

    </div>
  </div>
</div>

<!-- Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const issueModal = new bootstrap.Modal(document.getElementById('issueTemplateModal')); // ID: issueTemplateModal
    const sendBtn = document.getElementById('sendTemplateBtn'); // ID: sendTemplateBtn (assumed to be in main template)

    sendBtn?.addEventListener('click', () => {
      issueModal.show();
    });

    document.getElementById('confirmIssueBtn').addEventListener('click', () => { // ID: confirmIssueBtn
      const college = document.getElementById('collegeInput').value.trim(); // ID: collegeInput

      // Parse comma-separated professor names from input
      const rawInput = document.getElementById('professorsInput').value || ""; // ID: professorsInput
      const professors = rawInput.split(',')
                                 .map(name => name.trim())
                                 .filter(name => name.length > 0);

      const payload = {
        college,
        professors
      };

      console.log("Issuing Template:", payload);

      // Optional: Send to server
      // fetch('/issue-template', {
      //   method: 'POST',
      //   headers: { 'Content-Type': 'application/json' },
      //   body: JSON.stringify(payload)
      // });

      issueModal.hide(); // Close modal after submission
    });
  });
</script>
</body>
</html>