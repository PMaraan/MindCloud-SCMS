<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Drag & Drop UI Builder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/template-builder.css">
</head>
<body>

  <div class="sidebar-wrapper">
    <div>
      <h5 class="text-white">Elements</h5>
      <div class="element" draggable="true" data-type="button">Button</div>
      <div class="element" draggable="true" data-type="input">Text Input</div>
      <div class="element" draggable="true" data-type="label">Label</div>
      <div class="element" draggable="true" data-type="radio">Radio</div>
      <div class="element" draggable="true" data-type="textarea">Textarea</div>
      <div class="element" draggable="true" data-type="richtext">Rich Text Editor</div>
      <div class="element" draggable="true" data-type="header">Syllabus Header</div>
      <div class="element" draggable="true" data-type="table">Table</div>


    </div>
    <button class="btn btn-success w-100 mt-3" onclick="saveLayout()">Save</button>
    <button class="btn btn-danger w-100 mt-2" onclick="downloadAsPDF()">Download as PDF</button>

  </div>

  <div class="builder" id="builderArea">
    <p class="text-muted">Drag elements here...</p>
  </div>

  <script src="assets/js/template-builder.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

</body>
</html>
