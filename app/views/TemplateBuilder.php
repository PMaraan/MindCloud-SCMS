<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Grid‑Based Syllabus Template Builder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../public/assets/css/TemplateBuilder.css" rel="stylesheet">
</head>
<body>

<!-- SIDEBAR / PALETTE -->
<div id="palette-wrapper" class="d-flex flex-column">
  <div class="toggle-btn p-2 border-bottom">
    <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle" aria-label="Toggle Sidebar">
      <i class="bi bi-layout-sidebar-inset"></i>
    </button>
  </div>

  <div id="palette" class="flex-grow-1 overflow-auto">
    <div class="p-3">
      <div class="draggable" draggable="true" data-type="label">
        <i class="bi bi-tag-fill me-1"></i>Label
      </div>
      <div class="draggable" draggable="true" data-type="paragraph">
        <i class="bi bi-file-text-fill me-1"></i>Paragraph
      </div>
      <div class="draggable" draggable="true" data-type="text-field">
        <i class="bi bi-input-cursor-text me-1"></i>Text Field
      </div>
      <div class="draggable" draggable="true" data-type="text-3">
        <i class="bi bi-textarea-resize me-1"></i>Text Area
      </div>
      <div class="draggable" draggable="true" data-type="table">
        <i class="bi bi-table me-1"></i>Table
      </div>
      <div class="draggable" draggable="true" data-type="signature">
        <i class="bi bi-pen-fill me-1"></i>Signature Field
      </div>
    </div>
  </div>
</div>

<!-- TOP TOOLBAR -->
<div class="builder-header editor-toolbar d-flex align-items-center gap-2 p-2 border-bottom bg-light">
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
  <button class="btn btn-sm btn-outline-secondary" id="clearFormat"><i class="bi bi-eraser"></i></button>

  <div class="position-absolute end-0 me-3 d-flex align-items-center gap-2">
    <button id="sendTemplateBtn" class="btn btn-outline-primary">
      <i class="bi bi-send"></i>
    </button>
    <button id="saveTemplateBtn" class="btn btn-primary">
      <i class="bi bi-save"></i>
    </button>
  </div>
</div>

<!-- TABLE PROPERTIES SUB-HEADER -->
<div id="tableToolbar" class="table-toolbar d-none border-bottom bg-white shadow-sm py-1 px-3">
  <div class="d-flex align-items-center gap-2">

    <span class="fw-bold me-3">Table Properties</span>

    <!-- Row Controls -->
    <div class="btn-group" role="group" aria-label="Rows">
      <button class="btn btn-sm btn-outline-secondary" data-table-cmd="AddRow">
        <i class="bi bi-arrow-bar-down"></i> Add Row
      </button>
      <button class="btn btn-sm btn-outline-danger" data-table-cmd="deleteRow">
        <i class="bi bi-trash"></i> Delete Row
      </button>
    </div>

    <!-- Column Controls -->
    <div class="btn-group ms-3" role="group" aria-label="Columns">
      <button class="btn btn-sm btn-outline-secondary" data-table-cmd="addColLeft">
        <i class="bi bi-arrow-bar-left"></i> Col Left
      </button>
      <button class="btn btn-sm btn-outline-secondary" data-table-cmd="addColRight">
        <i class="bi bi-arrow-bar-right"></i> Col Right
      </button>
      <button class="btn btn-sm btn-outline-danger" data-table-cmd="deleteCol">
        <i class="bi bi-trash"></i> Delete Col
      </button>
    </div>

    <!-- Merge/Unmerge -->
    <div class="btn-group ms-3" role="group" aria-label="Merge">
      <button class="btn btn-sm btn-outline-primary" data-table-cmd="mergeCells">
        <i class="bi bi-merge"></i> Merge
      </button>
      <button class="btn btn-sm btn-outline-warning" data-table-cmd="unmergeCells">
        <i class="bi bi-scissors"></i> Unmerge
      </button>
    </div>

    <div class="ms-auto d-flex gap-2">
      <button class="btn btn-sm btn-outline-secondary" data-table-cmd="alignLeft">
        <i class="bi bi-text-left"></i>
      </button>
      <button class="btn btn-sm btn-outline-secondary" data-table-cmd="alignCenter">
        <i class="bi bi-text-center"></i>
      </button>
      <button class="btn btn-sm btn-outline-secondary" data-table-cmd="alignRight">
        <i class="bi bi-text-right"></i>
      </button>
    </div>

  </div>
</div>

<!-- WORKSPACE -->
<div id="workspace"></div>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/assets/js/TemplateBuilder.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('palette-wrapper');
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
    });
  });
</script>

</body>
</html>
