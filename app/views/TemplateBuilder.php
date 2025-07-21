<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Grid‑Based Syllabus Template Builder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../../public/assets/css/TemplateBuilder.css" rel="stylesheet">
</head>
<body>

<div id="palette-wrapper" class="d-flex flex-column">

  <div class="toggle-btn p-2 border-bottom">
    <button class="btn btn-sm btn-outline-secondary" type="button" id="sidebarToggle" aria-label="Toggle Sidebar">
      <i class="bi bi-layout-sidebar-inset"></i>
    </button>
  </div>

  <div id="palette" class="flex-grow-1 overflow-auto">
    <div class="p-3">
      <div class="draggable" draggable="true" data-type="label"><i class="bi bi-tag-fill me-1"></i>Label</div>
      <div class="draggable" draggable="true" data-type="paragraph"><i class="bi bi-file-text-fill me-1"></i>Paragraph</div>
      <div class="draggable" draggable="true" data-type="text-3"><i class="bi bi-textarea-resize me-1"></i>Text Area</div>
      
      <div class="draggable" draggable="true" data-type="table"><i class="bi bi-table me-1"></i>Table</div>
      <div class="draggable" draggable="true" data-type="signature"><i class="bi bi-pen-fill"></i>Signature Field</div>

    </div>
  </div>
</div>

<div class="builder-header editor-toolbar d-flex align-items-center gap-2 p-2 border-bottom bg-light">
   <div class="position-absolute start-0 ms-2 d-flex align-items-center">
    <img src="../../public/assets/images/logo_lpu.png" alt="Logo" style="height: 64px;">
  </div>
  <label class="form-label m-0 me-2" for="paperSize">Paper Size:</label>
  <select class="form-select form-select-sm w-auto" id="paperSize">
    <option value="A4">A4</option>
    <option value="Letter">Letter</option>
    <option value="Legal">Legal</option>
  </select>

  <button class="btn btn-sm btn-outline-secondary ms-2" id="addPageBtn">
    <i class="bi bi-file-earmark-plus"></i>&nbsp;Add Page
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

  <input type="color" id="textColor" title="Text Color">
  <input type="color" id="bgColor" title="Background Color">

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


</div>
<div id="workspace"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/assets/js/TemplateBuilder.js"></script>

</body>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('palette-wrapper');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
    });
  });
</script>
</html>
