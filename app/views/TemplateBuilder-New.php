<?php
// /app/Views/TemplateBuilder-New.php
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

      <!-- Left: doc controls you already have -->
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


          <!-- Font color -->
          <!-- Color dropdown -->
          <div class="dropdown">
              <button class="btn btn-icon dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Text color">
              <i class="bi bi-palette2"></i> Color
            </button>
            <ul class="dropdown-menu dropdown-menu-dark">
              <!-- Presets -->
              <li><button class="dropdown-item" data-action="setColor" data-value="#111827"><span style="color:#111827">A</span> Default</button></li>
              <li><button class="dropdown-item" data-action="setColor" data-value="#dc2626"><span style="color:#dc2626">A</span> Red</button></li>
              <li><button class="dropdown-item" data-action="setColor" data-value="#2563eb"><span style="color:#2563eb">A</span> Blue</button></li>
              <li><button class="dropdown-item" data-action="setColor" data-value="#16a34a"><span style="color:#16a34a">A</span> Green</button></li>
              <li><hr class="dropdown-divider"></li>
              <!-- Native color picker -->
              <li><button class="dropdown-item" data-action="pickColor">Custom…</button></li>
              <li><button class="dropdown-item" data-action="setColor" data-value=""><i class="bi bi-eraser"></i> Clear</button></li>
            </ul>

            <!-- Hidden native input that backs "Custom…" -->
            <input id="ctl-color-hidden" type="color" value="#111827" hidden>
          </div>

        </div>

        <!-- Undo / Redo -->
        <button class="btn btn-icon" data-action="undo" title="Undo"><i class="bi bi-arrow-counterclockwise"></i></button>
        <button class="btn btn-icon" data-action="redo" title="Redo"><i class="bi bi-arrow-clockwise"></i></button>

        <div class="toolbar-sep"></div>

        <!-- Headings dropdown -->
        <div class="dropdown">
          <button class="btn btn-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="dd-heading">
            <span id="dd-heading-label">P</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><button class="dropdown-item" data-action="setParagraph">Paragraph</button></li>
            <li><button class="dropdown-item" data-action="setHeading" data-level="1">Heading 1</button></li>
            <li><button class="dropdown-item" data-action="setHeading" data-level="2">Heading 2</button></li>
            <li><button class="dropdown-item" data-action="setHeading" data-level="3">Heading 3</button></li>
            <li><button class="dropdown-item" data-action="setHeading" data-level="4">Heading 4</button></li>
            <li><button class="dropdown-item" data-action="setHeading" data-level="5">Heading 5</button></li>
            <li><button class="dropdown-item" data-action="setHeading" data-level="6">Heading 6</button></li>
          </ul>
        </div>

        <!-- Lists -->
        <button class="btn btn-icon" data-action="toggleBulletList" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
        <button class="btn btn-icon" data-action="toggleOrderedList" title="Numbered list"><i class="bi bi-list-ol"></i></button>
        <button class="btn btn-icon" data-action="toggleTaskList" title="Task list"><i class="bi bi-ui-checks"></i></button>

        <div class="toolbar-sep"></div>

        <!-- Inline marks -->
        <button class="btn btn-icon" data-action="toggleBold" title="Bold"><i class="bi bi-type-bold"></i></button>
        <button class="btn btn-icon" data-action="toggleItalic" title="Italic"><i class="bi bi-type-italic"></i></button>
        <button class="btn btn-icon" data-action="toggleUnderline" title="Underline"><i class="bi bi-type-underline"></i></button>
        <button class="btn btn-icon" data-action="toggleStrike" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
        <button class="btn btn-icon" data-action="toggleCode" title="Code"><i class="bi bi-code"></i></button>
        <button class="btn btn-icon" data-action="toggleSuperscript" title="Superscript">x<sup>2</sup></button>
        <button class="btn btn-icon" data-action="toggleSubscript" title="Subscript">x<sub>2</sub></button>
        <button class="btn btn-icon" data-action="setLink" title="Link"><i class="bi bi-link-45deg"></i></button>
        <button class="btn btn-icon" data-action="unsetLink" title="Remove link"><i class="bi bi-link-45deg"></i><i class="bi bi-x-lg ms-n2 small"></i></button>

        <div class="toolbar-sep"></div>

        <!-- Blocks -->
        <button class="btn btn-icon" data-action="toggleBlockquote" title="Blockquote"><i class="bi bi-blockquote-left"></i></button>
        <button class="btn btn-icon" data-action="toggleCodeBlock" title="Code block"><i class="bi bi-code-slash"></i></button>

        <div class="toolbar-sep"></div>

        <!-- Alignment -->
        <button class="btn btn-icon" data-action="alignLeft" title="Align left"><i class="bi bi-text-left"></i></button>
        <button class="btn btn-icon" data-action="alignCenter" title="Align center"><i class="bi bi-text-center"></i></button>
        <button class="btn btn-icon" data-action="alignRight" title="Align right"><i class="bi bi-text-right"></i></button>
        <button class="btn btn-icon" data-action="alignJustify" title="Justify"><i class="bi bi-justify"></i></button>

        <div class="toolbar-sep"></div>

        <!-- Add menu -->
        <div class="dropdown">
          <button class="btn btn-icon" data-bs-toggle="dropdown" title="Add"><i class="bi bi-plus-lg"></i> Add</button>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><button class="dropdown-item" data-action="setHorizontalRule"><i class="bi bi-dash-lg me-2"></i>Horizontal Rule</button></li>
            <li><button class="dropdown-item" data-action="insertTable"><i class="bi bi-table me-2"></i>Table (2x3)</button></li>
            <li><button class="dropdown-item" data-action="insertImage"><i class="bi bi-image me-2"></i>Image (URL)</button></li>
          </ul>
        </div>
      </div>

      <!-- Right icons (optional) -->
        <i class="bi bi-send"></i>
        <i class="bi bi-gear"></i>
      </div>
    </div>
  </header>

  <!-- Editor Shell -->
  <div id="mc-shell">
    <aside class="gutter"></aside>

    <main id="mc-work">
      <section class="page size-A4" id="page-1" data-page="1" tabindex="0">
        <div class="page-header">
          <!-- left logo uploader -->
          <label class="logo-upload" title="Upload logo">
            <input id="logoInput" type="file" accept="image/*" hidden>
            <img id="logoPreview" alt="Logo" />
            <!-- fallback dashed box if no image yet -->
            <span class="logo-fallback"></span>
          </label>
          <div class="header-center">
            <h1 class="title" contenteditable="true">Enter Syllabus Title</h1>
            <p class="subtitle" contenteditable="true">Enter Subtitle</p>
          </div>
        </div>

        <div id="editor" class="tiptap" data-editor aria-label="Document editor"></div>

        <!-- Footer -->
        <footer class="page-footer" aria-label="Page footer">
          <span class="footer-left" contenteditable="true" data-placeholder="Footer Text">Footer Text</span>
          <span class="footer-right">Page <span class="page-num">1</span></span>
        </footer>
      </section>
    </main>

    <!-- Right palette (drag to page to insert) -->
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
  import { Editor, Extension }   from "https://esm.sh/@tiptap/core@2.6.6";
  import StarterKit              from "https://esm.sh/@tiptap/starter-kit@2.6.6";
  import Underline               from "https://esm.sh/@tiptap/extension-underline@2.6.6";
  import Link                    from "https://esm.sh/@tiptap/extension-link@2.6.6";
  import TextAlign               from "https://esm.sh/@tiptap/extension-text-align@2.6.6";
  import Placeholder             from "https://esm.sh/@tiptap/extension-placeholder@2.6.6";

    // === Multi-page editor manager ===
  const MCEditors = {
    map: new Map(),                  // pageId -> Editor
    get(pageId) { return this.map.get(pageId) || null; },
    set(pageId, ed) { this.map.set(pageId, ed); return ed; },
    first() { return this.map.values().next().value || null; },
    all() { return Array.from(this.map.values()); }
  };

  // Expose for non-module scripts (Toolbar/Sidebar wiring)
  window.MCEditors = MCEditors;

  // Create a TipTap editor inside a given page's [data-editor] container
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
        TextStyle, Color, FontFamily,
        FontSize, LineHeight,
        Superscript,
        Subscript,
        TaskList.configure({ HTMLAttributes: { class: 'tt-tasklist' } }),
        TaskItem.configure({ nested: true, HTMLAttributes: { class: 'tt-taskitem' } }),

        // FontSize + LineHeight are still declared later in this file
      ],
      content: '<p></p>',
      autofocus: false,
    });

    MCEditors.set(pageId, ed);

    // Let the auto-pagination watcher see changes on this editor
    ed.on('update', () => {
      const box = pageEl.querySelector('[data-editor]');
      if (box) maybeAddPageFor(box);
    });

    return ed;
  }

  import TextStyle               from "https://esm.sh/@tiptap/extension-text-style@2.6.6";
  import Color                   from "https://esm.sh/@tiptap/extension-color@2.6.6";
  import FontFamily              from "https://esm.sh/@tiptap/extension-font-family@2.6.6";

  import Superscript             from "https://esm.sh/@tiptap/extension-superscript@2.6.6";
  import Subscript               from "https://esm.sh/@tiptap/extension-subscript@2.6.6";
  import TaskList                from "https://esm.sh/@tiptap/extension-task-list@2.6.6";
  import TaskItem                from "https://esm.sh/@tiptap/extension-task-item@2.6.6";

  // Load your sidebar/toolbar wiring (it waits for window.__mc.editor)
  import "<?= $ASSET_BASE ?>/assets/js/TemplateBuilder-New.js";

  // Minimal font-size extension via textStyle
  const FontSize = Extension.create({
    name: 'fontSize',
    addGlobalAttributes() {
      return [{
        types: ['textStyle'],
        attributes: {
          fontSize: {
            default: null,
            parseHTML: el => el.style.fontSize || null,
            renderHTML: attrs => attrs.fontSize ? { style: `font-size:${attrs.fontSize}` } : {}
          }
        }
      }];
    },
  });

// --- LineHeight extension (ESM-safe) ---
const LineHeight = Extension.create({
  name: 'lineHeight',

  addGlobalAttributes() {
    return [
      {
        types: ['paragraph', 'heading', 'listItem', 'blockquote'],
        attributes: {
          lineHeight: {
            default: null,
            parseHTML: el => el.style.lineHeight || null,
            renderHTML: attrs => {
              if (!attrs.lineHeight) return {};
              return { style: `line-height:${attrs.lineHeight}` };
            },
          },
        },
      },
    ];
  },

  addCommands() {
    function setLH(value) {
      return ({ tr, state, dispatch, editor }) => {
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
    }

    return {
      setLineHeight: (value) => setLH(value),
      unsetLineHeight: () => setLH(null),
    };
  },
});


// --- Create TipTap editors for all pages present on load
// (FontSize + LineHeight are defined below; we’ll push them in after.)
async function bootEditorsForExistingPages() {
  const pages = document.querySelectorAll('.page');
  for (const p of pages) await makeEditorFor(p);
}
await bootEditorsForExistingPages();


  // --- After: const editor = new Editor({ ... }) ---

// --- Auto-pagination (generalized for any page/editor) ---
const PAGED = new WeakSet();
// Reserve some bottom space above the footer when deciding to add a new page
const BOTTOM_GUARD_PX = 20; // tweak to taste (matches the CSS padding-bottom)


function getEditorMeasureTargets(editorBoxEl){
  const prose = editorBoxEl.querySelector('.ProseMirror') || editorBoxEl.firstElementChild || editorBoxEl;
  return { box: editorBoxEl, content: prose };
}

function maybeAddPageFor(editorBoxEl){
  if (!editorBoxEl || PAGED.has(editorBoxEl)) return;

  const { box, content } = getEditorMeasureTargets(editorBoxEl);
  const BUFFER = 4;

  // Look up the footer height (if present) and reserve some extra breathing room.
  const pageEl = editorBoxEl.closest('.page');
  const footerH = pageEl?.querySelector('.page-footer')?.offsetHeight || 0;

  // Use whichever is larger: your fixed guard or the actual footer height + a small spacer.
  const reservedBottom = Math.max(BOTTOM_GUARD_PX, footerH + 24);

  // Trigger paging BEFORE text visually collides with the footer.
  const effectiveHeight = Math.max(0, box.clientHeight - reservedBottom);
  const overflows = (content.scrollHeight - BUFFER) >= effectiveHeight;

  // Only fire if caret/focus is inside THIS editor box (incl. .ProseMirror)
  const activeInside = box.contains(document.activeElement);


  if (activeInside && overflows) {
    // prevent the old editor from eating the next keystroke
    try { window.__mc?.getActiveEditor?.()?.commands?.blur(); } catch {}
    PAGED.add(editorBoxEl);
    createPage();
  }
}


// Start watching an editor box
function setupOverflowWatcher(editorBoxEl){
  if (!editorBoxEl) return;

  const ro = new ResizeObserver(() => maybeAddPageFor(editorBoxEl));
  ro.observe(editorBoxEl);

  editorBoxEl.addEventListener('input', () => maybeAddPageFor(editorBoxEl), { passive: true });
  editorBoxEl.addEventListener('keyup',  () => maybeAddPageFor(editorBoxEl), { passive: true });
}

// Wire all existing pages
document.querySelectorAll('.page [data-editor]').forEach(setupOverflowWatcher);


// Expose a getter so other code can reach the current editor if needed
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

  // Watch the first page editor box for overflow


    // ---------- Add Page wiring ----------
  const workEl  = document.getElementById('mc-work');
  const addBtn  = document.getElementById('ctl-addpage');
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

    // build a new page section (no duplicate #editor!)
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

      <!-- No TipTap instance here; sidebar blocks can still be dropped -->
      <div class="tiptap" data-editor aria-label="Document area"></div>

      <footer class="page-footer" aria-label="Page footer">
        <span class="footer-left" contenteditable="true" data-placeholder="Footer Text">Footer Text</span>
        <span class="footer-right">Page <span class="page-num">${n}</span></span>
      </footer>
    `;

    // copy the current logo, if any
    try {
      const firstPreview = document.getElementById('logoPreview');
      const src = firstPreview?.getAttribute('src');
      if (src) {
        const wrap = page.querySelector('.logo-upload');
        const img  = wrap.querySelector('img');
        img.src = src;
        wrap.classList.add('has-image');
      }
    } catch {}

    workEl.appendChild(page);
    await makeEditorFor(page);


    // --- Focus caret into the newly created page and keep it there ---
    const edKey = page.id || page.dataset.page;
    const ed    = MCEditors.get(edKey) || MCEditors.all().slice(-1)[0] || null;

    // Bring the new page into view (nice UX)
    page.scrollIntoView({ behavior: 'smooth', block: 'start' });

    // Focus now (same tick). Land at end of its first paragraph.
    try { ed?.chain().focus('start').run(); } catch { ed?.commands?.focus?.(); }

    // Guard: if the browser/ProseMirror steals focus, reclaim it next tick
    setTimeout(() => {
      try { ed?.chain().focus('start').run(); } catch { ed?.commands?.focus?.(); }
    }, 0);


    // Start overflow watching on the new page’s editor box
    const newEditorBox = page.querySelector('[data-editor]');
    if (newEditorBox) setupOverflowWatcher(newEditorBox);


    // make the new page a valid drop target for the sidebar
    window.__mc?.rewireDropTargets?.();

    // update page numbers
    updatePageNumbers();
  }

  function updatePageNumbers() {
    document.querySelectorAll('.page .page-num').forEach((span, idx) => {
      span.textContent = String(idx + 1);
    });
  }

  // --- Auto-pagination: watch the editor box for overflow and add a page ---


  addBtn?.addEventListener('click', createPage);

  // keep paper size consistent for new pages, too
  paperSel?.addEventListener('change', () => {
    document.querySelectorAll('.page').forEach(p => {
      p.classList.remove('size-A4','size-Letter','size-Legal');
      p.classList.add(currentPaperClass());
    });
  });


  // ── Logo uploader wiring ──────────────────────────────
  const upload  = document.getElementById('logoInput');
  const preview = document.getElementById('logoPreview');
  const wrapper = preview?.closest('.logo-upload');

  function setLogo(src){
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

  // ── Sidebar collapse toggle (declare vars BEFORE use) ─────────
  const STORAGE_KEY = 'mc-sb-collapsed';
  const shell    = document.getElementById('mc-shell');
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
