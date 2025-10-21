<?php
/**
 * RT Editor – Clean Build (no TipTap, no Yjs)
 * Path: /app/Modules/RTEditor/Views/index.php
 *
 * Expects: $pageTitle (string), $canEdit (bool)
 * This is a simple, controlled environment to prove we can type.
 */
$ASSET_BASE = defined('BASE_PATH') ? BASE_PATH : '';
?>
<div class="container py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h5>
    <span class="badge <?= $canEdit ? 'text-bg-success' : 'text-bg-warning' ?>">
      <?= $canEdit ? 'Editable' : 'Read-only' ?>
    </span>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <strong>Typing Test Area</strong>
    </div>
    <div class="card-body">
      <!-- Plain contenteditable area with zero external dependencies -->
      <!-- TipTap mount (no contenteditable attribute—TipTap will manage it) -->

      <div class="rt-toolbar-sticky">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
          <!-- Toolbar -->
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

            <!-- Page Layout -->
            <div class="d-flex align-items-center ms-2 gap-2">
              <div class="d-flex align-items-center gap-1">
                <label class="small text-muted">Size</label>
                <select class="form-select form-select-sm" style="width:auto" data-page-size>
                  <option value="A4" selected>A4</option>
                  <option value="Letter">Letter</option>
                  <option value="Legal">Legal</option>
                  <option value="A5">A5</option>
                </select>
              </div>

              <div class="d-flex align-items-center gap-1">
                <label class="small text-muted">Orientation</label>
                <select class="form-select form-select-sm" style="width:auto" data-page-orientation>
                  <option value="portrait" selected>Portrait</option>
                  <option value="landscape">Landscape</option>
                </select>
              </div>

              <div class="d-flex align-items-center gap-1">
                <label class="small text-muted">Margins</label>
                <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-top value="25mm" placeholder="top">
                <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-right value="25mm" placeholder="right">
                <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-bottom value="25mm" placeholder="bottom">
                <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-left value="25mm" placeholder="left">
              </div>
              
            </div>

            <!-- Font Family -->
            <div class="d-flex align-items-center ms-2 gap-1">
              <label class="small text-muted">Font</label>
              <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setFontFamily">
                <!-- common web-safe fonts + a few nice fallbacks -->
                <option value="Times New Roman, Times, serif">Times New Roman</option>
                <option value="Georgia, serif">Georgia</option>
                <option value="Garamond, serif">Garamond</option>

                <option value="Arial, Helvetica, sans-serif" selected>Arial</option>
                <option value="Helvetica, Arial, sans-serif">Helvetica</option>
                <option value="Tahoma, Verdana, Segoe, sans-serif">Tahoma</option>
                <option value="Verdana, Tahoma, sans-serif">Verdana</option>
                <option value="Segoe UI, Roboto, Helvetica, Arial, sans-serif">Segoe UI</option>

                <option value="Courier New, Courier, monospace">Courier New</option>
                <option value="Consolas, Monaco, monospace">Consolas</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetFontFamily" title="Clear font family">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>

            <!-- Font Size -->
            <div class="d-flex align-items-center ms-2 gap-1">
              <label class="small text-muted">Size</label>
              <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setFontSize">
                <!-- Word-like presets (points) -->
                <option value="8pt">8</option>
                <option value="9pt">9</option>
                <option value="10pt">10</option>
                <option value="11pt" selected>11</option>
                <option value="12pt">12</option>
                <option value="14pt">14</option>
                <option value="16pt">16</option>
                <option value="18pt">18</option>
                <option value="20pt">20</option>
                <option value="22pt">22</option>
                <option value="24pt">24</option>
                <option value="28pt">28</option>
                <option value="32pt">32</option>
                <option value="36pt">36</option>
                <option value="48pt">48</option>
                <option value="72pt">72</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetFontSize" title="Clear font size">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>

            <!-- Line Spacing -->
            <div class="d-flex align-items-center ms-2 gap-1">
              <label class="small text-muted">Line</label>
              <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setLineSpacing">
                <!-- Word-like presets (unitless) -->
                <option value="1">1.0</option>
                <option value="1.15" selected>1.15</option>
                <option value="1.5">1.5</option>
                <option value="2">2.0</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetLineSpacing" title="Clear line spacing">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>

            <!-- Paragraph Spacing -->
            <div class="d-flex align-items-center ms-2 gap-1">
              <label class="small text-muted">Before</label>
              <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setParaBefore">
                <!-- points, like Word -->
                <option value="0pt" selected>0</option>
                <option value="6pt">6</option>
                <option value="12pt">12</option>
                <option value="18pt">18</option>
                <option value="24pt">24</option>
              </select>
              <label class="small text-muted ms-2">After</label>
              <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setParaAfter">
                <option value="0pt" selected>0</option>
                <option value="6pt">6</option>
                <option value="12pt">12</option>
                <option value="18pt">18</option>
                <option value="24pt">24</option>
              </select>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetParaSpacing" title="Clear paragraph spacing">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>

            <!-- Text styles -->
            <div class="btn-group btn-group-sm" role="group" aria-label="Text">
              <button type="button" class="btn btn-outline-secondary" data-cmd="toggleBold" title="Bold"><i class="bi bi-type-bold"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="toggleItalic" title="Italic"><i class="bi bi-type-italic"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="toggleUnderline" title="Underline"><i class="bi bi-type-underline"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="toggleStrike" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="toggleSubscript" title="Subscript">x<sub>2</sub></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="toggleSuperscript" title="Superscript">x<sup>2</sup></button>
            </div>

            <!-- Lists -->
            <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Lists">
              <button type="button" class="btn btn-outline-secondary" data-cmd="bulletList" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="orderedList" title="Numbered list"><i class="bi bi-list-ol"></i></button>
            </div>

            <!-- Indent / Outdent -->
            <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Indent">
              <button type="button" class="btn btn-outline-secondary" data-cmd="indentList" title="Increase indent (Tab)">
                <i class="bi bi-text-indent-right"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="outdentList" title="Decrease indent (Shift+Tab)">
                <i class="bi bi-text-indent-left"></i>
              </button>
            </div>

            <!-- Alignment -->
            <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Align">
              <button type="button" class="btn btn-outline-secondary" data-cmd="alignLeft" title="Align left"><i class="bi bi-text-left"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="alignCenter" title="Center"><i class="bi bi-text-center"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="alignRight" title="Align right"><i class="bi bi-text-right"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="alignJustify" title="Justify"><i class="bi bi-justify"></i></button>
            </div>

            <!-- Colors -->
            <div class="d-flex align-items-center ms-2 gap-1">
              <label class="small text-muted">Text</label>
              <input type="color" data-cmd-input="setColor" class="form-control form-control-color p-0" value="#000000" title="Text color">
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetColor" title="Clear text color"><i class="bi bi-x-circle"></i></button>
            </div>

            <div class="d-flex align-items-center ms-2 gap-1">
              <label class="small text-muted">Highlight</label>
              <!-- NEW: one-click apply using current color -->
              <button type="button" class="btn btn-warning btn-sm" data-cmd="applyHighlight" title="Highlight selection">
                <i class="bi bi-highlighter"></i>
              </button>
              <input type="color" data-cmd-input="setHighlight" class="form-control form-control-color p-0" value="#fff59d" title="Highlight color">
              <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetHighlight" title="Clear highlight"><i class="bi bi-x-circle"></i></button>
            </div>

            <!-- Undo/Redo -->
            <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="UndoRedo">
              <button type="button" class="btn btn-outline-secondary" data-cmd="undo" title="Undo"><i class="bi bi-arrow-90deg-left"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="redo" title="Redo"><i class="bi bi-arrow-90deg-right"></i></button>
            </div>

            <!-- Tables -->
            <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Table">
              <button type="button" class="btn btn-outline-secondary" data-cmd="insertTable" title="Insert 3×3 table">
                <i class="bi bi-table"></i>
              </button>

              <button type="button" class="btn btn-outline-secondary" data-cmd="addRowBefore" title="Row before">
                <i class="bi bi-border-top"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="addRowAfter" title="Row after">
                <i class="bi bi-border-bottom"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="deleteRow" title="Delete row">
                <i class="bi bi-border-width"></i>
              </button>

              <button type="button" class="btn btn-outline-secondary" data-cmd="addColumnBefore" title="Col before">
                <i class="bi bi-border-start"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="addColumnAfter" title="Col after">
                <i class="bi bi-border-end"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="deleteColumn" title="Delete column">
                <i class="bi bi-border-style"></i>
              </button>

              <button type="button" class="btn btn-outline-secondary" data-cmd="toggleHeaderRow" title="Toggle header row">
                <i class="bi bi-layout-three-columns"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="mergeCells" title="Merge cells">
                <i class="bi bi-layout-wtf"></i>
              </button>
              <button type="button" class="btn btn-outline-secondary" data-cmd="splitCell" title="Split cell">
                <i class="bi bi-grid-1x2"></i>
              </button>
              <button type="button" class="btn btn-outline-danger" data-cmd="deleteTable" title="Delete table">
                <i class="bi bi-trash"></i>
              </button>
            </div>

            <!-- Signature field -->
            <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Signature">
              <button type="button" class="btn btn-outline-secondary" data-cmd="insertSignature" title="Insert signature field">
                <i class="bi bi-pen"></i>
              </button>

              <!-- optional: role quick-set -->
              <select class="form-select form-select-sm" style="width:auto" data-cmd-input="sigSetRole" title="Signer role">
                <option value="">(role)</option>
                <option value="Instructor">Instructor</option>
                <option value="Program Chair">Program Chair</option>
                <option value="Dean">Dean</option>
                <option value="VPAA">VPAA</option>
              </select>

              <button type="button" class="btn btn-outline-secondary" data-cmd="sigToggleRequired" title="Toggle required">
                <i class="bi bi-check2-square"></i>
              </button>
            </div>

            <!-- Page Break -->
            <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Page Break">
              <button type="button" class="btn btn-outline-secondary" data-cmd="insertPageBreak" title="Insert page break (Ctrl+Enter)">
                <i class="bi bi-scissors"></i>
              </button>
            </div>

          </div>
          <!-- /Toolbar -->
        </div>
      </div>


      

      <link rel="stylesheet" href="<?= BASE_PATH ?>/public/assets/css/rteditor/collab-editor.css">

      

    </div>
  </div>

</div>

<!-- Full-width band for the page canvas -->
<div class="container-fluid py-3">
  <div id="pageRoot">
    <div id="rtPage" class="rt-page">
      <div id="rtHeader" class="rt-header" contenteditable="true">Header…</div>
      <div id="rtPageContent" class="rt-page-content">
        <div id="editor" class="border-0" style="min-height: 700px;"></div>
      </div>
      <div id="rtFooter" class="rt-footer" contenteditable="true">Footer…</div>
    </div>
  </div>

  <!-- Diagnostics BELOW the page -->
  <div class="mt-3 small text-muted">
    <div>Diagnostics:</div>
    <pre id="diag" class="p-2 border bg-light rounded" style="white-space:pre-wrap;"></pre>
  </div>
</div>

<script type="importmap">
{
  "imports": {
    "@tiptap/core": "https://esm.sh/@tiptap/core@2.6.6",
    "@tiptap/starter-kit": "https://esm.sh/@tiptap/starter-kit@2.6.6",
    "@tiptap/pm": "https://esm.sh/@tiptap/pm@2.6.6",

    "@tiptap/extension-underline": "https://esm.sh/@tiptap/extension-underline@2.6.6",
    "@tiptap/extension-strike": "https://esm.sh/@tiptap/extension-strike@2.6.6",
    "@tiptap/extension-subscript": "https://esm.sh/@tiptap/extension-subscript@2.6.6",
    "@tiptap/extension-superscript": "https://esm.sh/@tiptap/extension-superscript@2.6.6",
    "@tiptap/extension-text-style": "https://esm.sh/@tiptap/extension-text-style@2.6.6",
    "@tiptap/extension-color": "https://esm.sh/@tiptap/extension-color@2.6.6",
    "@tiptap/extension-highlight": "https://esm.sh/@tiptap/extension-highlight@2.6.6",
    "@tiptap/extension-text-align": "https://esm.sh/@tiptap/extension-text-align@2.6.6",
    "@tiptap/extension-font-family": "https://esm.sh/@tiptap/extension-font-family@2.6.6",

    "@tiptap/extension-table": "https://esm.sh/@tiptap/extension-table@2.6.6",
    "@tiptap/extension-table-row": "https://esm.sh/@tiptap/extension-table-row@2.6.6",
    "@tiptap/extension-table-cell": "https://esm.sh/@tiptap/extension-table-cell@2.6.6",
    "@tiptap/extension-table-header": "https://esm.sh/@tiptap/extension-table-header@2.6.6"
  }
}
</script>

<script type="module">
  import initBasicEditor, { bindBasicToolbar } from "<?= BASE_PATH ?>/public/assets/js/rteditor/collab-editor.js";
  import { bindPageLayoutControls } from "<?= BASE_PATH ?>/public/assets/js/rteditor/page-layout.js";

  // TipTap editor init
  const editor = initBasicEditor({
    selector: '#editor',
    editable: true,
    initialHTML: '<p>TipTap ready — start typing…</p>'
  });
  window.__RT_editor = editor;
  bindBasicToolbar(editor, document);

  // Page layout (independent)
  const pageEl = document.getElementById('rtPage');
  const contentEl = document.getElementById('rtPageContent');
  bindPageLayoutControls(document, pageEl, contentEl);

  // Diagnostics
  (function() {
    const ed = document.querySelector('#editor .ProseMirror');
    const out = document.getElementById('diag');
    const log = (m) => { out.textContent += (m + '\n'); };

    function report() {
      if (!ed) { log('[Warn] .ProseMirror not found'); return; }
      const cs = getComputedStyle(ed);
      log('[Report] TipTap contenteditable=' + ed.getAttribute('contenteditable'));
      log('[Report] pointer-events=' + cs.pointerEvents + ', user-select=' + cs.userSelect + ', display=' + cs.display + ', visibility=' + cs.visibility);
    }

    if (ed) {
      ed.addEventListener('keydown', () => log('[Event] keydown detected (TipTap)'));
      report();
    } else {
      log('[Warn] ProseMirror not ready at init');
      setTimeout(() => {
        const ed2 = document.querySelector('#editor .ProseMirror');
        if (ed2) {
          ed2.addEventListener('keydown', () => log('[Event] keydown detected (TipTap)'));
          const cs = getComputedStyle(ed2);
          log('[Report] TipTap contenteditable=' + ed2.getAttribute('contenteditable'));
          log('[Report] pointer-events=' + cs.pointerEvents + ', user-select=' + cs.userSelect + ', display=' + cs.display + ', visibility=' + cs.visibility);
        } else {
          log('[Error] ProseMirror still not found after delay.');
        }
      }, 100);
    }
  })();
</script>




<script>
// ====== SINGLE KEYDOWN REGISTRATION & ENTER TRACE (TEMP) ======
(function () {
  const out = document.getElementById('diag');
  if (!out) return;
  const log = (...a) => { out.textContent += a.join(' ') + '\n'; };

  // Capture WHO registers 'keydown' (target + stack at registration time)
  const origAdd = EventTarget.prototype.addEventListener;
  const registry = [];
  EventTarget.prototype.addEventListener = function (type, handler, opts) {
    if (type === 'keydown' && typeof handler === 'function') {
      const stack = (new Error('addEventListener stack')).stack?.split('\n').slice(2, 8).join('\n') ?? '(no stack)';
      registry.push({
        target: this,
        name: handler.name || '(anonymous)',
        opts,
        stackAtRegistration: stack
      });
    }
    return origAdd.call(this, type, handler, opts);
  };

  // Dump helper you can run from console
  window.dumpKeydownListeners = function () {
    log('--- Registered keydown listeners ---');
    const nodeDesc = (n) => {
      if (n === window) return 'window';
      if (n === document) return 'document';
      if (n instanceof HTMLElement) {
        const id = n.id ? `#${n.id}` : '';
        const cls = n.className ? '.' + String(n.className).trim().replace(/\s+/g, '.') : '';
        return `<${n.tagName.toLowerCase()}${id}${cls}>`;
      }
      return String(n);
    };
    registry.forEach((r, i) => {
      log(`#${i} target=${nodeDesc(r.target)} handler=${r.name} opts=${JSON.stringify(r.opts || {})}`);
      log(r.stackAtRegistration);
      log('---');
    });
    log('Total:', registry.length);
  };

  // Trace Enter propagation & who calls preventDefault/stop*
  const origPD  = Event.prototype.preventDefault;
  const origSP  = Event.prototype.stopPropagation;
  const origSIP = Event.prototype.stopImmediatePropagation;

  function shortStack() {
    return (new Error().stack?.split('\n').slice(2, 8).join('\n')) ?? '(no stack)';
  }
  Event.prototype.preventDefault = function () {
    if (this.type === 'keydown' && this.key === 'Enter') {
      log('[TRACE] preventDefault on Enter (target=', String(this.target), ')\n', shortStack());
    }
    return origPD.apply(this, arguments);
  };
  Event.prototype.stopPropagation = function () {
    if (this.type === 'keydown' && this.key === 'Enter') {
      log('[TRACE] stopPropagation on Enter (target=', String(this.target), ')\n', shortStack());
    }
    return origSP.apply(this, arguments);
  };
  Event.prototype.stopImmediatePropagation = function () {
    if (this.type === 'keydown' && this.key === 'Enter') {
      log('[TRACE] stopImmediatePropagation on Enter (target=', String(this.target), ')\n', shortStack());
    }
    return origSIP.apply(this, arguments);
  };

  function phase(label, e) {
    if (e.key !== 'Enter') return;
    log(`[TRACE] ${label} defaultPrevented=${e.defaultPrevented} target=`, e.target?.outerHTML?.slice(0, 80) ?? String(e.target));
  }
  document.addEventListener('keydown', e => phase('CAPTURE', e), true);
  document.addEventListener('keydown', e => phase('BUBBLE ', e), false);

  log('[TRACE] ready. Do the two Enter tests, then run dumpKeydownListeners() in console.');
})();
</script>

<script>
// ===== OVERLAY DETECTOR (keep this) =====
(function () {
  const ed = document.querySelector('#editor');
  if (!ed) return;
  ed.addEventListener('click', (ev) => {
    const x = ev.clientX, y = ev.clientY;
    const topEl = document.elementFromPoint(x, y);
    if (!topEl) return;
    if (!ed.contains(topEl)) {
      console.warn('[OVERLAY] Click resolved to element OUTSIDE #editor at', topEl);
    } else {
      const z = getComputedStyle(topEl).zIndex;
      console.log('[OVERLAY] Click inside editor resolved to', topEl, 'z-index=', z);
    }
  });
})();
</script>







<script>
// ===== OVERLAY DETECTOR (TEMP) =====
(function () {
  const ed = document.querySelector('#editor');
  if (!ed) return;
  ed.addEventListener('click', (ev) => {
    const x = ev.clientX, y = ev.clientY;
    const topEl = document.elementFromPoint(x, y);
    if (!topEl) return;
    if (!ed.contains(topEl)) {
      console.warn('[OVERLAY] Click resolved to element OUTSIDE #editor at', topEl);
    } else {
      const z = getComputedStyle(topEl).zIndex;
      console.log('[OVERLAY] Click inside editor resolved to', topEl, 'z-index=', z);
    }
  });
})();
</script>