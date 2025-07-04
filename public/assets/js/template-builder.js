const builder = document.getElementById('builderArea');
let draggedType = null;

document.querySelectorAll('.element').forEach(el => {
  el.addEventListener('dragstart', e => {
    draggedType = e.target.dataset.type;
  });
});

builder.addEventListener('dragover', e => {
  e.preventDefault();
});

builder.addEventListener('drop', e => {
  e.preventDefault();
  const html = getElementHTML(draggedType);
  insertElement(builder, html);
});

function insertElement(container, html) {
  const temp = document.createElement('div');
  temp.innerHTML = html.trim();
  const node = temp.firstChild;
  container.appendChild(node);
}

// Generates HTML for different element types
function getElementHTML(type) {
  switch (type) {
    case 'button':
      return '<button class="btn btn-primary my-2">Click Me</button>';
    case 'input':
      return '<input type="text" class="form-control my-2 w-50" placeholder="Text Input">';
    case 'label':
      return '<label class="my-2 d-block">Label</label>';
    case 'radio':
      return '<div class="form-check my-2"><input class="form-check-input" type="radio" name="rgroup"><label class="form-check-label">Option</label></div>';
    case 'textarea':
      return '<textarea class="form-control my-2 w-50" rows="3" placeholder="Write something..."></textarea>';
    case 'richtext':
      return `
        <div class="rich-editor">
          <div class="rich-toolbar">
            <button onclick="execFormat(this, 'bold')"><b>B</b></button>
            <button onclick="execFormat(this, 'italic')"><i>I</i></button>
            <button onclick="execFormat(this, 'underline')"><u>U</u></button>
            <button onclick="execFormat(this, 'insertUnorderedList')">•</button>
            <button onclick="execFormat(this, 'insertOrderedList')">1.</button>
          </div>
          <div class="rich-content" contenteditable="true"></div>
        </div>`;
        case 'header':
  return `
    <div class="syllabus-header">
      <div class="logo-upload-wrapper" onclick="triggerLogoUpload(this)">
        <img src="https://via.placeholder.com/80" alt="Logo" class="logo-preview">
        <input type="file" accept="image/*" class="logo-input d-none" onchange="previewLogo(this)">
      </div>
      <div class="syllabus-header-content">
        <h2 contenteditable="true">Course Title / Syllabus Template</h2>
        <p contenteditable="true">Semester: --- | Course Code: --- | Instructor: ---</p>
      </div>
    </div>`;
    case 'table':
  return `
    <div class="editable-table my-3">
      <div class="table-controls mb-2">
        <button class="btn btn-sm btn-outline-primary me-1" onclick="addRow(this)">+ Row</button>
        <button class="btn btn-sm btn-outline-primary me-1" onclick="addColumn(this)">+ Col</button>
        <button class="btn btn-sm btn-outline-danger me-1" onclick="deleteRow(this)">− Row</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteColumn(this)">− Col</button>
      </div>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th contenteditable="true">Header 1</th>
            <th contenteditable="true">Header 2</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td contenteditable="true">Row 1 Col 1</td>
            <td contenteditable="true">Row 1 Col 2</td>
          </tr>
        </tbody>
      </table>
    </div>`;



    default:
      return '';
  }
}

// Ensures commands apply only inside the clicked editor
function execFormat(button, command) {
  const editor = button.closest('.rich-editor').querySelector('.rich-content');
  editor.focus();
  document.execCommand(command, false, null);
}
function triggerLogoUpload(wrapper) {
  const input = wrapper.querySelector('.logo-input');
  input.click();
}
function addRow(btn) {
  const table = btn.closest('.editable-table').querySelector('table');
  const cols = table.rows[0].cells.length;
  const newRow = table.insertRow();
  for (let i = 0; i < cols; i++) {
    const cell = newRow.insertCell();
    cell.contentEditable = true;
    cell.innerText = `Row ${table.rows.length - 1} Col ${i + 1}`;
  }
}

function addColumn(btn) {
  const table = btn.closest('.editable-table').querySelector('table');
  const colIndex = table.rows[0].cells.length;
  // Add to thead
  const headerCell = document.createElement('th');
  headerCell.contentEditable = true;
  headerCell.innerText = `Header ${colIndex + 1}`;
  table.tHead.rows[0].appendChild(headerCell);
  // Add to tbody
  for (let i = 0; i < table.tBodies[0].rows.length; i++) {
    const cell = table.tBodies[0].rows[i].insertCell();
    cell.contentEditable = true;
    cell.innerText = `Row ${i + 1} Col ${colIndex + 1}`;
  }
}

function deleteRow(btn) {
  const table = btn.closest('.editable-table').querySelector('table');
  const rowCount = table.tBodies[0].rows.length;
  if (rowCount > 1) {
    table.deleteRow(-1);
  }
}

function deleteColumn(btn) {
  const table = btn.closest('.editable-table').querySelector('table');
  const colCount = table.rows[0].cells.length;
  if (colCount <= 1) return;

  for (let row of table.rows) {
    row.deleteCell(-1);
  }
}

function previewLogo(input) {
  const file = input.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function (e) {
    const preview = input.closest('.logo-upload-wrapper').querySelector('.logo-preview');
    preview.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function saveLayout() {
  const content = builder.innerHTML;
  console.log("Saved Layout HTML:", content);
  alert("Layout saved (check console).");
}
function downloadAsPDF() {
  const element = document.getElementById('builderArea');

  // Optional styling adjustments before export
  const opt = {
    margin:       0.5,
    filename:     'template-layout.pdf',
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2 },
    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
  };

  html2pdf().set(opt).from(element).save();
}
