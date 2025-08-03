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
f// 1. Tab Switching Logic
function switchTab(tabId) {
  const tabs = document.querySelectorAll('.tab-content');
  const buttons = document.querySelectorAll('.tab-button');

  tabs.forEach(tab => tab.classList.remove('active'));
  buttons.forEach(btn => btn.classList.remove('active'));

  document.getElementById(tabId).classList.add('active');
  event.target.classList.add('active');
}

// 2. Drag & Drop Builder
const elements = document.querySelectorAll('.element');
const builderArea = document.getElementById('builderArea');

elements.forEach(el => {
  el.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', e.target.getAttribute('data-type'));
  });
});

builderArea.addEventListener('dragover', (e) => {
  e.preventDefault();
  builderArea.classList.add('dragging');
});

builderArea.addEventListener('dragleave', () => {
  builderArea.classList.remove('dragging');
});

builderArea.addEventListener('drop', (e) => {
  e.preventDefault();
  const type = e.dataTransfer.getData('text/plain');
  builderArea.classList.remove('dragging');

  const newElement = createElement(type);
  builderArea.appendChild(newElement);
});

function createElement(type) {
  let el;
  switch (type) {
    case 'button':
      el = document.createElement('button');
      el.className = 'btn btn-primary m-2';
      el.textContent = 'Click Me';
      break;
    case 'input':
      el = document.createElement('input');
      el.className = 'form-control m-2';
      el.placeholder = 'Enter text...';
      break;
    case 'label':
      el = document.createElement('label');
      el.className = 'm-2';
      el.textContent = 'Label';
      break;
    case 'radio':
      el = document.createElement('input');
      el.type = 'radio';
      el.className = 'form-check-input m-2';
      break;
    case 'textarea':
      el = document.createElement('textarea');
      el.className = 'form-control m-2';
      el.rows = 3;
      el.placeholder = 'Type here...';
      break;
    case 'richtext':
      el = document.createElement('div');
      el.className = 'border p-2 m-2';
      el.contentEditable = true;
      el.innerHTML = '<p>Editable Rich Text</p>';
      break;
    case 'header':
      el = document.createElement('h3');
      el.className = 'fw-bold m-2';
      el.textContent = 'Syllabus Header';
      break;
    case 'table':
      el = document.createElement('table');
      el.className = 'table table-bordered m-2';
      el.innerHTML = `
        <thead><tr><th>Header 1</th><th>Header 2</th></tr></thead>
        <tbody><tr><td>Data 1</td><td>Data 2</td></tr></tbody>`;
      break;
    default:
      el = document.createElement('div');
      el.className = 'm-2';
      el.textContent = 'Unknown Element';
  }

  return el;
}

// 3. Save Layout (localStorage for now)
function saveLayout() {
  const content = builderArea.innerHTML;
  localStorage.setItem('syllabusBuilderLayout', content);
  alert('Layout saved to browser (localStorage)');
}

// 4. Export to PDF
function downloadAsPDF() {
  const element = document.body; // You can narrow this if needed
  const opt = {
    margin: 0.5,
    filename: 'syllabus.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
  };

  html2pdf().set(opt).from(element).save();
}

