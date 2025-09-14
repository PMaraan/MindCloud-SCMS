TemplateBuilder-New.php
<?php
// Fallback for asset base when this file is opened outside the normal app flow
if (!defined('BASE_PATH')) {
    // Example request: /MindCloud-SCMS/app/Views/TemplateBuilder-New.php
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    // Everything before "/app/..." becomes the project base (e.g., "/MindCloud-SCMS")
    $projectBase = '';
    if ($reqUri !== '') {
        $parts = explode('/app/', $reqUri, 2);
        $projectBase = rtrim($parts[0] ?? '', '/');
    }
    // When not routed through /public, serve assets from "/{project}/public"
    $ASSET_BASE = ($projectBase !== '' ? $projectBase : '') . '/public';
} else {
    // When routed properly, BASE_PATH already points to /public
    $ASSET_BASE = BASE_PATH;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MindCloud — TipTap Page Editor</title>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="<?= $ASSET_BASE ?>/assets/css/TemplateBuilder-New.css">
</head>
<body>

  <!-- Top Maroon Bar (now TipTap toolbar inside) -->
  <header id="mc-topbar" class="bg-maroon text-white">
    <div class="container-fluid d-flex align-items-center gap-2">

      <img src="<?= $ASSET_BASE ?>/assets/images/logo_lpu.png" alt="Logo" class="mc-logo">

      <!-- Left: doc controls -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <label class="top-label me-1">Paper Size:</label>
        <select id="ctl-paper" class="form-select form-select-sm top-select">
          <option value="A4" selected>A4</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
        </select>
        <button id="ctl-addpage" class="btn btn-sm btn-outline-light">
          <i class="bi bi-file-earmark-plus"></i> Add Page
        </button>
      </div>

      <!-- Middle: FULL TIPTAP TOOLBAR -->
      <div id="tt-toolbar">
        <!-- Font tools -->
        <div class="toolbar-fonts d-flex align-items-center gap-1 me-2">
          <!-- Font family -->
          <select id="ctl-font" class="form-select form-select-sm top-select" title="Font family">
            <option value="">Font</option>
            <option value="Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif">Inter</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="Times New Roman, Times, serif">Times New Roman</option>
            <option value="Garamond, serif">Garamond</option>
            <option value="Arial, Helvetica, sans-serif">Arial</option>
            <option value="Tahoma, Geneva, sans-serif">Tahoma</option>
            <option value="Courier New, Courier, monospace">Courier New</option>
          </select>

          <!-- Font size -->
          <select id="ctl-size" class="form-select form-select-sm top-select" title="Font size">
            <option value="">Size</option>
            <option value="12px">12</option>
            <option value="14px">14</option>
            <option value="16px">16</option>
            <option value="18px">18</option>
            <option value="20px">20</option>
            <option value="24px">24</option>
            <option value="28px">28</option>
            <option value="32px">32</option>
          </select>

          <!-- Line Spacing -->
          <div class="dropdown">
            <button class="btn btn-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Line spacing">
              <i class="bi bi-text-paragraph"></i> Spacing
            </button>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="1">Single</button></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="1.15">1.15</button></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="1.5">1.5</button></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="2">Double</button></li>
              <li><hr class="dropdown-divider"></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="custom">Custom…</button></li>
              <li><button class="dropdown-item" data-action="unsetLineHeight">Clear</button></li>
            </ul>
          </div>

          <div class="toolbar-sep"></div>

        <button class="btn btn-icon" data-action="insertTable" title="Insert table">
          <i class="bi bi-table"></i>
        </button>

        <div class="toolbar-sep"></div>

        <!-- Undo / Redo -->
        <button class="btn btn-icon" data-action="undo" title="Undo"><i class="bi bi-arrow-counterclockwise"></i></button>
        <button class="btn btn-icon" data-action="redo" title="Redo"><i class="bi bi-arrow-clockwise"></i></button>
        <div class="toolbar-sep"></div>

        <!-- (rest of toolbar buttons are here, unchanged from your original) -->

        <!-- Lists -->
        <button class="btn btn-icon" data-action="toggleBulletList" title="Bulleted list">
          <i class="bi bi-list-ul"></i>
        </button>

        <div class="toolbar-sep"></div>

        <!-- Inline marks -->
        <button class="btn btn-icon" data-action="toggleBold" title="Bold">
          <i class="bi bi-type-bold"></i>
        </button>
        <button class="btn btn-icon" data-action="toggleItalic" title="Italicize">
          <i class="bi bi-type-italic"></i>
        </button>
        <button class="btn btn-icon" data-action="toggleUnderline" title="Underline">
          <i class="bi bi-type-underline"></i>
        </button>

        <div class="toolbar-sep"></div>

        <!-- Hyperlink -->
        <button class="btn btn-icon" data-action="setLink" title="Add link">
          <i class="bi bi-link-45deg"></i>
        </button>
        <button class="btn btn-icon" data-action="unsetLink" title="Remove link">
          <i class="bi bi-link-45deg"></i><i class="bi bi-x-lg ms-n2 small"></i>
        </button>

        <div class="toolbar-sep"></div>

        <!-- Alignment -->
        <button class="btn btn-icon" data-action="alignLeft" title="Align left">
          <i class="bi bi-text-left"></i>
        </button>
        <button class="btn btn-icon" data-action="alignCenter" title="Align middle">
          <i class="bi bi-text-center"></i>
        </button>
        <button class="btn btn-icon" data-action="alignRight" title="Align right">
          <i class="bi bi-text-right"></i>
        </button>
        <button class="btn btn-icon" data-action="alignJustify" title="Justify">
          <i class="bi bi-justify"></i>
        </button>


      </div>

      <!-- Right icons (optional) -->
      <i class="bi bi-send"></i>
      <i class="bi bi-gear"></i>
    </div>
  </header>

  <!-- Editor Shell -->
  <div id="mc-shell">
    <aside class="gutter"></aside>
    <main id="mc-work">
      <section class="page size-A4" id="page-1" data-page="1" tabindex="0">
        <div class="page-header">
          <label class="logo-upload" title="Upload logo">
            <input id="logoInput" type="file" accept="image/*" hidden>
            <img id="logoPreview" alt="Logo" />
            <span class="logo-fallback"></span>
          </label>
          <div class="header-center">
            <h1 class="title" contenteditable="true">Enter Syllabus Title</h1>
            <p class="subtitle" contenteditable="true">Enter Subtitle</p>
          </div>
        </div>
        <div id="editor" class="tiptap" data-editor aria-label="Document editor"></div>
        <footer class="page-footer" aria-label="Page footer">
          <span class="footer-left" contenteditable="true" data-placeholder="Footer Text">Footer Text</span>
          <span class="footer-right">Page <span class="page-num">1</span></span>
        </footer>
      </section>
    </main>

    <!-- Right palette -->
    <aside id="mc-sidebar">
      <div class="d-grid gap-2">
        <button id="sb-toggle" class="sb-item">
          <i class="bi bi-grid-3x3-gap"></i>
        </button>
        <button class="sb-item" draggable="true" data-type="textField"><i class="bi bi-ui-checks-grid"></i> Text Field</button>
        <button class="sb-item" draggable="true" data-type="label"><i class="bi bi-tag"></i> Label</button>
        <button class="sb-item" draggable="true" data-type="paragraph"><i class="bi bi-card-text"></i> Paragraph</button>
        <button class="sb-item" draggable="true" data-type="text"><i class="bi bi-textarea-t"></i> Text Field</button>
        <button class="sb-item" draggable="true" data-type="textarea"><i class="bi bi-textarea-resize"></i> Text Area</button>
        <button class="sb-item" draggable="true" data-type="table"><i class="bi bi-table"></i> Table</button>
        <button class="sb-item" draggable="true" data-type="signature"><i class="bi bi-pen"></i> Signature Field</button>
      </div>
    </aside>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- TipTap (ESM) + Extensions -->
 <script type="module">
/* TipTap core + extensions (ESM) */
import { Editor, Extension } from "https://esm.sh/@tiptap/core@2.6.6";
import StarterKit from "https://esm.sh/@tiptap/starter-kit@2.6.6";
import Underline from "https://esm.sh/@tiptap/extension-underline@2.6.6";
import Link from "https://esm.sh/@tiptap/extension-link@2.6.6";
import TextAlign from "https://esm.sh/@tiptap/extension-text-align@2.6.6";
import Placeholder from "https://esm.sh/@tiptap/extension-placeholder@2.6.6";
import TextStyle from "https://esm.sh/@tiptap/extension-text-style@2.6.6";
import Color from "https://esm.sh/@tiptap/extension-color@2.6.6";
import FontFamily from "https://esm.sh/@tiptap/extension-font-family@2.6.6";
import Superscript from "https://esm.sh/@tiptap/extension-superscript@2.6.6";
import Subscript from "https://esm.sh/@tiptap/extension-subscript@2.6.6";
import TaskList from "https://esm.sh/@tiptap/extension-task-list@2.6.6";
import TaskItem from "https://esm.sh/@tiptap/extension-task-item@2.6.6";
// TipTap tables
import Table       from "https://esm.sh/@tiptap/extension-table@2.6.6";
import TableRow    from "https://esm.sh/@tiptap/extension-table-row@2.6.6";
import TableHeader from "https://esm.sh/@tiptap/extension-table-header@2.6.6";
import TableCell   from "https://esm.sh/@tiptap/extension-table-cell@2.6.6";


/* Load your sidebar/toolbar/blocks wiring (waits for window.__mc.editor) */
import "<?= $ASSET_BASE ?>/assets/js/TemplateBuilder-New.js";

/* ==== Multi-page editor manager ==== */
const MCEditors = {
  map: new Map(), // pageId -> Editor
  get(pageId) { return this.map.get(pageId) || null; },
  set(pageId, ed) { this.map.set(pageId, ed); return ed; },
  first() { return this.map.values().next().value || null; },
  all() { return Array.from(this.map.values()); }
};

  window.__mc = window.__mc || {};
  window.__mc.MCEditors = MCEditors;

/* ---- FontSize (via textStyle) ---- */
const FontSize = Extension.create({
  name: 'fontSize',
  addGlobalAttributes() {
    return [{
      types: ['textStyle'],
      attributes: {
        fontSize: {
          default: null,
          parseHTML: el => el.style.fontSize || null,
          renderHTML: attrs => (attrs.fontSize ? { style: `font-size:${attrs.fontSize}` } : {})
        }
      }
    }];
  },
});

/* ---- LineHeight extension ---- */
const LineHeight = Extension.create({
  name: 'lineHeight',
  addGlobalAttributes() {
    return [{
      types: ['paragraph', 'heading', 'listItem', 'blockquote'],
      attributes: {
        lineHeight: {
          default: null,
          parseHTML: el => el.style.lineHeight || null,
          renderHTML: attrs => {
            if (!attrs.lineHeight) return {};
            return { style: `line-height:${attrs.lineHeight}` };
          }
        }
      }
    }];
  },
  addCommands() {
    const setLH = (value) => ({ tr, state, dispatch, editor }) => {
      const types = new Set(
        ['paragraph', 'heading', 'listItem', 'blockquote']
          .map(n => editor.schema.nodes[n])
          .filter(Boolean)
      );
      const { from, to } = state.selection;
      state.doc.nodesBetween(from, to, (node, pos) => {
        if (types.has(node.type)) {
          tr.setNodeMarkup(pos, node.type, { ...node.attrs, lineHeight: value || null });
        }
      });
      if (dispatch) dispatch(tr);
      return true;
    };
    return {
      setLineHeight: (value) => setLH(value),
      unsetLineHeight: () => setLH(null),
    };
  },
});

/* Create a TipTap editor inside a page’s [data-editor] container */
async function makeEditorFor(pageEl) {
  const holder = pageEl.querySelector('[data-editor]');
  if (!holder) return null;
  const pageId = pageEl.id || pageEl.dataset.page || `page-${Date.now()}`;

  const ed = new Editor({
    element: holder,
    extensions: [
      StarterKit.configure({ heading: { levels: [1,2,3,4,5,6] } }),
      Underline,
      Link.configure({
        openOnClick: true,
        autolink: true,
        HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' },
      }),
      TextAlign.configure({ types: ['heading','paragraph'] }),
      Placeholder.configure({ placeholder: 'Start typing…' }),
      TextStyle,
      Color,
      FontFamily,
      FontSize,
      LineHeight,
      Superscript,
      Subscript,
      TaskList.configure({ HTMLAttributes: { class: 'tt-tasklist' } }),
      TaskItem.configure({ nested: true, HTMLAttributes: { class: 'tt-taskitem' } }),
      // ADD ↓↓↓
      Table.configure({ resizable: true }),
      TableRow,
      TableHeader,
      TableCell,
    ],
    content: '<p></p>',
    autofocus: false,
  });

  MCEditors.set(pageId, ed);
  // Bind the Flow Engine (no inner scrolling; move blocks across pages)
  bindFlowHandlers(ed);

  return ed;
}


/* Boot editors for all existing pages */
async function bootEditorsForExistingPages() {
  const pages = document.querySelectorAll('.page');
  for (const p of pages) await makeEditorFor(p);
}
await bootEditorsForExistingPages();

/* ===================== FLOW ENGINE (no inner scrolling) ===================== */

// remove: const FLOW_GUARD = 2;

const MIN_GUARD = 24; // px, safe floor
function getFlowGuardPx(ed){
  const pageEl = getPageOfEditor(ed);
  const box    = pageEl?.querySelector('[data-editor]');
  const prose  = box?.querySelector('.ProseMirror') || box;
  if (!prose) return MIN_GUARD;

  const cs = getComputedStyle(prose);
  let lh = parseFloat(cs.lineHeight);
  if (!isFinite(lh)) {
    const fs = parseFloat(cs.fontSize) || 16;
    lh = fs * 1.25; // guess "normal"
  }
  return Math.max(MIN_GUARD, Math.ceil(lh + 4)); // a smidge extra
}


const getPageOfEditor = (ed) => ed?.options?.element?.closest?.('.page') || null;
const getEditorOfPage  = (pageEl) => {
  const all = MCEditors.all();
  for (const ed of all) {
    if (pageEl && pageEl.contains(ed.options.element)) return ed;
  }
  return null;
};
const getNextPageEl = (pageEl) => pageEl?.nextElementSibling?.classList?.contains('page') ? pageEl.nextElementSibling : null;
const getPrevPageEl = (pageEl) => pageEl?.previousElementSibling?.classList?.contains('page') ? pageEl.previousElementSibling : null;

// replace your measureEditor() with this
function measureEditor(ed){
  const pageEl = getPageOfEditor(ed);
  const box    = pageEl?.querySelector('[data-editor]');
  const prose  = box?.querySelector('.ProseMirror') || box;
  const footer = pageEl?.querySelector('.page-footer');
  if (!box || !prose) return { limit: 0, used: 0 };

  const boxTop     = box.getBoundingClientRect().top;
  const bottomEdge = footer
    ? footer.getBoundingClientRect().top
    : box.getBoundingClientRect().bottom;

  const limit = Math.max(0, bottomEdge - boxTop); // px available above the footer
  const used  = prose.scrollHeight;               // content height
  return { limit, used };
}


function cloneJSON(obj){ return JSON.parse(JSON.stringify(obj || {})); }

function isEmptyBlock(node){
  if (!node) return true;
  if (node.type !== 'paragraph') return false;
  const c = node.content || [];
  if (!c.length) return true;
  // paragraph with only whitespace
  if (c.length === 1 && c[0].type === 'text' && (!c[0].text || !c[0].text.trim())) return true;
  return false;
}
function ensureDocNotEmpty(json){
  if (!json.content || json.content.length === 0){
    json.content = [{ type: 'paragraph' }]; // keep a placeholder node
  }
  return json;
}

function popLastBlock(ed){
  const json = cloneJSON(ed.getJSON());
  const arr  = json.content || [];
  if (!arr.length) return null;
  const last = arr.pop();
  ensureDocNotEmpty(json);
  ed.commands.setContent(json, false); // false => don't emit update (we'll rebalance manually)
  return last;
}
function shiftFirstBlock(ed){
  const json = cloneJSON(ed.getJSON());
  const arr  = json.content || [];
  if (!arr.length) return null;
  const first = arr.shift();
  ensureDocNotEmpty(json);
  ed.commands.setContent(json, false);
  return first;
}
function prependBlock(ed, node){
  const json = cloneJSON(ed.getJSON());
  json.content = [node, ...(json.content || [])];
  ed.commands.setContent(json, false);
}
function appendBlock(ed, node){
  const json = cloneJSON(ed.getJSON());
  json.content = [...(json.content || []), node];
  ed.commands.setContent(json, false);
}
function hasAnyRealContent(ed){
  const json = ed.getJSON();
  const arr = json.content || [];
  if (!arr.length) return false;
  if (arr.length === 1 && isEmptyBlock(arr[0])) return false;
  return true;
}

async function ensureNextPageEditor(currentPageEl){
  let nextPage = getNextPageEl(currentPageEl);
  if (!nextPage){
    await createPage();
    nextPage = getNextPageEl(currentPageEl) || document.querySelector('.page:last-of-type');
  }
  return getEditorOfPage(nextPage);
}

async function flowForward(ed){
  let guard = 20;
  while (guard-- > 0){
    const {limit, used} = measureEditor(ed);
    const G = getFlowGuardPx(ed);
    if (used <= limit - G) break;

    const pageEl = getPageOfEditor(ed);
    const nextEd = await ensureNextPageEditor(pageEl);
    if (!nextEd) break;

    const node = popLastBlock(ed);
    if (!node) break;

    // If we popped an "empty placeholder", try one more
    if (isEmptyBlock(node)){
      if (!hasAnyRealContent(ed)) break;
      continue;
    }
    prependBlock(nextEd, node);
    ed = nextEd; // cascade if next page now overflows
  }
}

async function flowBackward(ed){
  let guard = 20;
  while (guard-- > 0){
    const {limit, used} = measureEditor(ed);
    const G = getFlowGuardPx(ed);
    if (used >= limit - G) break;

    const curPage = getPageOfEditor(ed);
    const nextPage = getNextPageEl(curPage);
    if (!nextPage) break;
    const nextEd = getEditorOfPage(nextPage);
    if (!nextEd || !hasAnyRealContent(nextEd)) break;

    const node = shiftFirstBlock(nextEd);
    if (!node) break;

    appendBlock(ed, node);

    // If that made us overflow, put it back and stop.
    const m = measureEditor(ed);
    if (m.used > m.limit - FLOW_GUARD){
      // revert move
      popLastBlock(ed);       // remove what we just appended
      prependBlock(nextEd, node);
      break;
    }
  }
}

async function rebalanceAround(ed){
  if (!ed) return;
  // 1) push forward from this page as needed (and cascade)
  await flowForward(ed);

  // 2) pull back to fill holes (then re-push if that caused overflow)
  await flowBackward(ed);
  await flowForward(ed);

  updatePageNumbers();
}

/* Hook the rebalance: every editor update triggers it */
function bindFlowHandlers(ed){
  if (ed._mcFlowBound) return;
  ed._mcFlowBound = true;
  ed.on('update', () => {
    // Rebalance the page that changed.
    // No inner scrolling occurs; content moves across pages.
    rebalanceAround(ed);
  });
}

/* Bind for existing and future editors */
MCEditors.all().forEach(bindFlowHandlers);



/* Expose current active editor getter */
window.__mc = window.__mc || {};
window.__mc.getActiveEditor = () => {
  const el = document.activeElement;
  if (el) {
    const page = el.closest('.page');
    if (page) {
      for (const ed of MCEditors.all()) {
        if (page.contains(ed.options.element)) return ed;
      }
    }
  }
  return MCEditors.first();
};

/* ===== Add Page wiring ===== */
const workEl   = document.getElementById('mc-work');
const addBtn   = document.getElementById('ctl-addpage');
const paperSel = document.getElementById('ctl-paper');

function nextPageNumber() {
  return document.querySelectorAll('.page').length + 1;
}
function currentPaperClass() {
  const val = (paperSel?.value || 'A4');
  return `size-${val}`;
}

async function createPage() {
  const n = nextPageNumber();

  const page = document.createElement('section');
  page.className = `page ${currentPaperClass()}`;
  page.dataset.page = String(n);
  page.tabIndex = 0;
  page.id = `page-${n}`;
  page.innerHTML = `
    <div class="page-header">
      <label class="logo-upload" title="Upload logo">
        <input type="file" accept="image/*" hidden>
        <img alt="Logo" />
        <span class="logo-fallback"></span>
      </label>
      <div class="header-center">
        <h1 class="title" contenteditable="true">Enter Syllabus Title</h1>
        <p class="subtitle" contenteditable="true">Enter Subtitle</p>
      </div>
    </div>
    <div class="tiptap" data-editor aria-label="Document area"></div>
    <footer class="page-footer" aria-label="Page footer">
      <span class="footer-left" contenteditable="true" data-placeholder="Footer Text">Footer Text</span>
      <span class="footer-right">Page <span class="page-num">${n}</span></span>
    </footer>
  `;

  /* copy the current logo if any */
  try {
    const firstPreview = document.getElementById('logoPreview');
    const src = firstPreview?.getAttribute('src');
    if (src) {
      const wrap = page.querySelector('.logo-upload');
      const img = wrap.querySelector('img');
      img.src = src;
      wrap.classList.add('has-image');
    }
  } catch {}

  workEl.appendChild(page);
  await makeEditorFor(page);

  /* focus new page */
  const edKey = page.id || page.dataset.page;
  const ed = MCEditors.get(edKey) || MCEditors.all().slice(-1)[0] || null;
  requestAnimationFrame(() => {
    try { (MCEditors.get(page.id || page.dataset.page) || MCEditors.first())?.commands.focus('start'); } catch {}
  });
try { ed?.chain().focus('start', { scrollIntoView: false }).run(); } catch { ed?.commands?.focus?.(); }
setTimeout(() => {
  try { ed?.chain().focus('start', { scrollIntoView: false }).run(); } catch { ed?.commands?.focus?.(); }
}, 0);


  /* make new page a valid drop target for the sidebar */
  window.__mc?.rewireDropTargets?.();

  /* update page numbers */
  updatePageNumbers();
}

function updatePageNumbers() {
  document.querySelectorAll('.page .page-num').forEach((span, idx) => {
    span.textContent = String(idx + 1);
  });
}

/* Buttons */
addBtn?.addEventListener('click', createPage);


/* Keep paper size consistent for all pages */
paperSel?.addEventListener('change', () => {
  document.querySelectorAll('.page').forEach(p => {
    p.classList.remove('size-A4','size-Letter','size-Legal');
    p.classList.add(currentPaperClass());
  });
});

/* ===== Logo uploader wiring ===== */
const upload  = document.getElementById('logoInput');
const preview = document.getElementById('logoPreview');
const wrapper = preview?.closest('.logo-upload');

function setLogo(src) {
  if (!preview || !wrapper) return;
  preview.src = src || '';
  wrapper.classList.toggle('has-image', !!src);
}

upload?.addEventListener('change', () => {
  const file = upload.files?.[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = () => setLogo(reader.result);
  reader.readAsDataURL(file);
});

/* ===== Sidebar collapse toggle ===== */
const STORAGE_KEY = 'mc-sb-collapsed';
const shell = document.getElementById('mc-shell');
const sbToggle = document.getElementById('sb-toggle');
const collapsed = localStorage.getItem(STORAGE_KEY) === '1';
if (collapsed) shell.classList.add('sb-collapsed');

sbToggle?.addEventListener('click', () => {
  shell.classList.toggle('sb-collapsed');
  localStorage.setItem(
    STORAGE_KEY,
    shell.classList.contains('sb-collapsed') ? '1' : '0'
  );
});
</script>

</body>
</html>