<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rich Document Builder</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      height: 100vh;
      overflow: hidden;
      display: flex;
      flex-direction: row-reverse;
    }

    .sidebar-wrapper {
      width: 250px;
      background: #222;
      color: #fff;
      padding: 10px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .element {
      padding: 10px;
      background: #444;
      border: 1px solid #555;
      margin-bottom: 10px;
      border-radius: 5px;
      cursor: grab;
      user-select: none;
    }

    .builder {
      flex-grow: 1;
      background: #f0f0f0;
      padding: 10px;
      display: flex;
      flex-direction: column;
    }

    .toolbar {
      background: #fff;
      padding: 8px;
      border: 1px solid #ccc;
      display: flex;
      gap: 8px;
      margin-bottom: 10px;
    }

    .toolbar button {
      border: none;
      background: #e0e0e0;
      padding: 5px 10px;
      cursor: pointer;
    }

    .toolbar button:hover {
      background: #ccc;
    }

    .document {
      flex-grow: 1;
      background: #fff;
      border: 1px solid #ccc;
      padding: 20px;
      min-height: 400px;
      overflow-y: auto;
      outline: none;
    }
  </style>
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
    </div>
    <button class="btn btn-success w-100 mt-3" onclick="saveLayout()">Save</button>
  </div>

  <div class="builder">
    <div class="toolbar">
      <button onclick="formatText('bold')"><b>B</b></button>
      <button onclick="formatText('italic')"><i>I</i></button>
      <button onclick="formatText('underline')"><u>U</u></button>
      <button onclick="formatText('insertUnorderedList')">• List</button>
      <button onclick="formatText('insertOrderedList')">1. List</button>
    </div>

    <div class="document" contenteditable="true" id="docArea"></div>
  </div>

  <script>
    const docArea = document.getElementById('docArea');
    let draggedType = null;

    // Store the dragged type
    document.querySelectorAll('.element').forEach(el => {
      el.addEventListener('dragstart', e => {
        draggedType = e.target.dataset.type;
      });
    });

    // Allow dropping
    docArea.addEventListener('dragover', e => {
      e.preventDefault();
    });

    // Insert HTML on drop
    docArea.addEventListener('drop', e => {
      e.preventDefault();
      const html = getElementHTML(draggedType);
      insertHTMLAtCursor(html);
    });

    // Formatting
    function formatText(cmd) {
      document.execCommand(cmd, false, null);
    }

    // Generate element HTML
    function getElementHTML(type) {
      switch (type) {
        case 'button':
          return '<button class="btn btn-primary">Click Me</button>';
        case 'input':
          return '<input type="text" class="form-control d-inline w-auto mx-2" placeholder="Text Input">';
        case 'label':
          return '<label class="mx-2">Label</label>';
        case 'radio':
          return '<label class="form-check mx-2"><input type="radio" name="rgroup"> Option</label>';
        case 'textarea':
          return '<textarea class="form-control my-2" rows="2" placeholder="Write something..."></textarea>';
        default:
          return '';
      }
    }

    // Insert at caret
    function insertHTMLAtCursor(html) {
      const sel = window.getSelection();
      if (sel.rangeCount) {
        const range = sel.getRangeAt(0);
        range.deleteContents();

        const temp = document.createElement("div");
        temp.innerHTML = html;
        const frag = document.createDocumentFragment();
        let node;
        while ((node = temp.firstChild)) {
          frag.appendChild(node);
        }
        range.insertNode(frag);
      }
    }

    function saveLayout() {
      const content = docArea.innerHTML;
      console.log("Saved Document Content:", content);
      alert("Document content saved (check console).");
    }
  </script>
</body>
</html>
