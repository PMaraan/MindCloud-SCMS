<?php
// Robust $ASSET_BASE for both routed and "open file in browser" cases
if (!defined('BASE_PATH')) {
    // Example URLs we must support:
    //  1) /MindCloud-SCMS/app/Views/TemplateBuilder-New.php           (direct open)
    //  2) /MindCloud-SCMS/public/...                                  (normal routed)
    // We want $ASSET_BASE to resolve to: /MindCloud-SCMS/public

    $script = $_SERVER['SCRIPT_NAME'] ?? '';              // URL path to THIS php, e.g. /MindCloud-SCMS/app/Views/TemplateBuilder-New.php
    $script = str_replace('\\', '/', $script);

    // If URL contains "/app/", strip from there to the end -> gives "/MindCloud-SCMS"
    if (strpos($script, '/app/') !== false) {
        $projectBase = preg_replace('#/app/.*$#', '', $script);
    } else {
        // Fallback: strip the filename, then go up until project root guess
        // e.g. /MindCloud-SCMS/TemplateBuilder-New.php  -> /MindCloud-SCMS
        $projectBase = rtrim(dirname($script), '/');
    }

    // Project root (no /public)
    $PROJECT_ROOT = rtrim($projectBase, '/');            // => /MindCloud-SCMS
    // Finally point to /public
    $ASSET_BASE   = $PROJECT_ROOT . '/public';           // => /MindCloud-SCMS/public
} else {
    // When routed properly, BASE_PATH already points to /public
    $ASSET_BASE   = BASE_PATH;                           // => /MindCloud-SCMS/public
    $PROJECT_ROOT = rtrim(dirname(BASE_PATH), '/');      // => /MindCloud-SCMS
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
  <link rel="stylesheet" href="<?= $ASSET_BASE ?>/assets/css/TemplateBuilder-New.css?v=<?= time() ?>">
</head>
<body>

  <!-- Top Maroon Bar (now TipTap toolbar inside) -->
  <header id="mc-topbar" class="bg-maroon text-white">
    <div class="container-fluid d-flex align-items-center gap-2">

      <a href="<?= $PROJECT_ROOT ?>/" class="mc-logo-link" title="Go to Home" aria-label="Go to Home">
        <img src="<?= $ASSET_BASE ?>/assets/images/logo_lpu.png" alt="Logo" class="mc-logo">
      </a>


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
        <button class="btn btn-icon" data-action="insertUploadBox" title="Image upload">
          <i class="bi bi-image"></i>
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
      <!-- Right: Send / Settings (pinned at right like the logo is at left) -->
      <div class="mc-actions">
        <button id="ctl-send" class="btn btn-icon" title="Save">
          <i class="bi bi-send"></i>
        </button>
        <button id="ctl-settings" class="btn btn-icon" title="Settings">
          <i class="bi bi-gear"></i>
        </button>
      </div>
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
  import "<?= $ASSET_BASE ?>/assets/js/TemplateBuilder-New.js?v=<?= time() ?>";

/* ==== Multi-page editor manager ==== */
// --- Tiny UploadBox node (block atom) ---

import { Plugin } from "https://esm.sh/prosemirror-state@1.4.3";

const NoNestedTables = Extension.create({
  name: 'noNestedTables',
  addProseMirrorPlugins() {
    return [
      new Plugin({
        filterTransaction(tr, state) {
          if (!tr.docChanged) return true;

          let ok = true;
          tr.doc.descendants((node, pos) => {
            if (!ok) return false;

            if (node.type.name === 'table') {
              // Walk up from this position; if any ancestor is a table → block
              const $pos = tr.doc.resolve(pos);
              for (let d = $pos.depth - 1; d >= 0; d--) {
                if ($pos.node(d).type.name === 'table') { ok = false; break; }
              }
            }
            return ok;
          });

          return ok; // returning false cancels the transaction
        }
      })
    ];
  },
});


import { Node } from "https://esm.sh/@tiptap/core@2.6.6";


const UploadBox = Node.create({
  name: 'uploadBox',
  group: 'block',
  atom: true,
  selectable: true,

  addAttributes() {
    return {
      src: { default: null },
      alt: { default: '' },
    };
  },

  parseHTML() { return [{ tag: 'upload-box' }]; },
  renderHTML({ HTMLAttributes }) { return ['upload-box', HTMLAttributes]; },

  addCommands() {
    return {
      insertUploadBox:
        (attrs = {}) =>
        ({ chain }) =>
          chain().insertContent({ type: this.name, attrs }).run(),
    };
  },

  addNodeView() {
    return ({ node, updateAttributes }) => {
      const dom = document.createElement('div');
      dom.className = 'mc-upload-box';
      dom.setAttribute('contenteditable', 'false');

      const img = document.createElement('img');
      img.className = 'mc-upload-img';

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mc-upload-btn';
      btn.textContent = 'Upload';

      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.style.display = 'none';

      const show = (src) => {
        if (src) {
          dom.dataset.hasImage = '1';
          img.src = src;
          img.style.display = 'block';
          btn.style.display = 'none';
        } else {
          dom.dataset.hasImage = '0';
          img.removeAttribute('src');
          img.style.display = 'none';
          btn.style.display = 'inline-block';
        }
      };

      // keep PM from swallowing clicks
      const stop = (e) => { e.preventDefault(); e.stopPropagation(); };
      dom.addEventListener('mousedown', stop);
      btn.addEventListener('mousedown', stop);
      img.addEventListener('mousedown', stop);
      input.addEventListener('mousedown', stop);

      btn.addEventListener('click', (e) => { e.preventDefault(); input.value = ''; input.click(); });
      img.addEventListener('click', () => { input.value = ''; input.click(); });

      // add this near the other locals inside addNodeView()
      let objectUrl = null;

      input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;

        // clean up any previous blob URL
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);

        // 1) update the DOM immediately so the user sees it right away
        dom.dataset.hasImage = '1';
        img.src = objectUrl;
        img.style.display = 'block';
        btn.style.display = 'none';

        // 2) also persist to the document (so it survives re-renders)
        updateAttributes({ src: objectUrl, alt: file.name || '' });
        console.log('[UploadBox] file picked:', file?.name, objectUrl);

      });


      dom.append(img, btn, input);
      show(node.attrs.src);

      return {
        dom,
        update(updatedNode) {
          if (updatedNode.type.name !== 'uploadBox') return false;
          show(updatedNode.attrs.src);
          return true;
        },
        ignoreMutation: () => true,
        destroy() {
          // prevent memory leaks if this node view is destroyed
          if (objectUrl) URL.revokeObjectURL(objectUrl);
        }
      };
    };
  },
});
export default UploadBox;


// --- DateInput (inline atom) ---
const DateInput = Node.create({
  name: 'dateInput',
  group: 'inline',
  inline: true,
  atom: true,
  selectable: true,
  draggable: false,

  addAttributes() {
    return {
      value: { default: '' },           // persisted YYYY-MM-DD
      placeholder: { default: 'YYYY-MM-DD' }
    };
  },

  // allow <date-input> in HTML import/export
  parseHTML() { return [{ tag: 'date-input' }]; },
  renderHTML({ HTMLAttributes }) { return ['date-input', HTMLAttributes]; },

  addCommands() {
    return {
      insertDateInput:
        (attrs = {}) =>
        ({ commands }) =>
          commands.insertContent({ type: this.name, attrs }),
    };
  },

  addNodeView() {
    return ({ node, editor, getPos }) => {
      const input = document.createElement('input');
      input.type = 'date';
      input.className = 'sig-date-input';           // use your existing CSS
      input.value = node.attrs.value || '';
      input.placeholder = node.attrs.placeholder || 'YYYY-MM-DD';
      input.setAttribute('data-tt', 'date-input');
      input.contentEditable = 'false';              // let the input handle focus, not PM

      // keep TipTap attrs in sync with UI
      const updateAttr = (val) => {
        const pos = getPos && getPos();
        if (typeof pos === 'number') {
          editor.view.dispatch(
            editor.state.tr.setNodeMarkup(pos, undefined, { ...node.attrs, value: val })
          );
        }
      };

      input.addEventListener('change', () => updateAttr(input.value));
      input.addEventListener('input',  () => updateAttr(input.value));

      // Let clicks focus the input (don’t select the node)
      input.addEventListener('mousedown', (e) => e.stopPropagation());

      return {
        dom: input,
        update(updated) {
          if (updated.type.name !== 'dateInput') return false;
          if (updated.attrs.value !== input.value) input.value = updated.attrs.value || '';
          return true;
        },
        ignoreMutation: () => true,
      };
    };
  },
});



// Preserve "data-sig" and "class" on TipTap table nodes
// Preserve attrs AND lock structure for signature tables (data-sig="1"/.sig-table)
const TableWithAttrs = Table.extend({
  addAttributes() {
    return {
      class: {
        default: null,
        parseHTML: el => el.getAttribute('class'),
        renderHTML: attrs => (attrs.class ? { class: attrs.class } : {}),
      },
      'data-sig': {
        default: null,
        parseHTML: el => el.getAttribute('data-sig'),
        renderHTML: attrs => (attrs['data-sig'] ? { 'data-sig': attrs['data-sig'] } : {}),
      },
    };
  },

  // ✅ Allow ALL table commands everywhere (no signature lock)
  addCommands() {
    const parent = this.parent?.();
    return { ...parent };
  },

  // keep your Tab/Shift-Tab overrides as-is
  addKeyboardShortcuts() {
    const parentKeys = this.parent?.() || {};
    const move = (dir) => () => {
      if (!isSignatureTablePM?.(this.editor)) {
        return parentKeys[dir === 1 ? 'Tab' : 'Shift-Tab']
          ? parentKeys[dir === 1 ? 'Tab' : 'Shift-Tab']()
          : false;
      }
      const cell = currentCellElement?.(this.editor);
      const tbl  = cell?.closest?.('table');
      if (!cell || !tbl) return true;

      const firstCell = tbl.querySelector('tr:first-child td:first-child, tr:first-child th:first-child');
      const lastCell  = tbl.querySelector('tr:last-child  td:last-child');
      if (dir === 1 && cell === lastCell) {
        moveCaretOutsideEnclosingTable?.(this.editor, 'after');
        return true;
      }
      if (dir === -1 && cell === firstCell) {
        moveCaretOutsideEnclosingTable?.(this.editor, 'before');
        return true;
      }
      return this.editor.commands.goToNextCell?.(dir) || true;
    };

    return { ...parentKeys, Tab: move(1), 'Shift-Tab': move(-1) };
  },
});


// Keep custom attrs on table cells (e.g., data-ph)
const TableCellWithAttrs = TableCell.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      'data-ph': {
        default: null,
        parseHTML: el => el.getAttribute('data-ph'),
        renderHTML: attrs => attrs['data-ph'] ? { 'data-ph': attrs['data-ph'] } : {},
      },
      // (optional) allow class on TD/TH too
      class: {
        default: null,
        parseHTML: el => el.getAttribute('class'),
        renderHTML: attrs => attrs.class ? { class: attrs.class } : {},
      },
    };
  },
});



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

/* ---- TabListIndent (keep focus; Tab/Shift+Tab behave like Google Docs in lists) ---- */
const TabListIndent = Extension.create({
  name: 'tabListIndent',
  addKeyboardShortcuts() {
    return {
      Tab: () => {
        // If we’re in a list item, try to indent. Even if it can’t indent,
        // return true to prevent the browser from tabbing focus to the footer.
        if (this.editor.isActive('listItem')) {
          const ok = this.editor.chain().focus().sinkListItem('listItem').run();
          return ok || true; // handled -> prevent default focus jump
        }
        return false; // not in a list -> let Tab behave normally
      },
      'Shift-Tab': () => {
        if (this.editor.isActive('listItem')) {
          const ok = this.editor.chain().focus().liftListItem('listItem').run();
          return ok || true; // handled (even if no outdent possible)
        }
        return false;
      },
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
      TableWithAttrs.configure({ resizable: true }),
      TableRow,
      TableHeader,
      TableCellWithAttrs,
      TabListIndent,
      UploadBox,
      DateInput,
      NoNestedTables,
    ],
    content: '<p></p>',
    autofocus: false,
  });

    // Prevent table-in-table on paste (must be bound per editor)
  ed.view.dom.addEventListener('paste', (e) => {
    try {
      const html = e.clipboardData?.getData('text/html') || '';
      if (!html) return;
      if (/<table[\s>]/i.test(html) && isSelectionInsideTable(ed)) {
        e.preventDefault();
        // Move after enclosing table, then paste there
        forceCaretOutsideTable(ed, 'after');
        ed.chain().focus().insertContent(html).run();
        ed.chain().focus().insertContent('<p></p>').run();
      }
    } catch {}
  }, true);


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
    if (m.used > m.limit - getFlowGuardPx(ed)){
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

// ==== Google Docs–style: Backspace on an empty page removes the page ====
(function setupDeleteEmptyPageHotkey(){
  // A page can be deleted only if its editor is empty and it has no overlay blocks
  const isEditorEmpty = (ed) => !hasAnyRealContent(ed);
  const canDeletePage = (pageEl) => {
    const ov = pageEl?.querySelector('.mc-block-overlay');
    return !(ov && ov.querySelector('.mc-block')); // don't nuke a page that has free-positioned blocks
  };

  document.addEventListener('keydown', (ev) => {
    if (ev.key !== 'Backspace' || ev.defaultPrevented) return;

    // Only act when the event came from a TipTap editor
    const pm = ev.target?.closest?.('.ProseMirror');
    if (!pm) return;

    const ed = window.__mc?.getActiveEditor?.();
    if (!ed) return;

    const sel = ed.state?.selection;
    // Must be at the very start of the doc and the editor must be empty
    if (!sel?.empty || sel.from !== 1 || !isEditorEmpty(ed)) return;

    const pageEl  = getPageOfEditor(ed);
    const prevEl  = getPrevPageEl(pageEl);
    if (!pageEl || !prevEl || !canDeletePage(pageEl)) return;

    // We handle it: prevent the default and do the Google Docs behavior
    ev.preventDefault();
    ev.stopPropagation();

    // Tear-down must run after TipTap finishes its own handlers
    setTimeout(() => {
      try { ed.destroy(); } catch {}

      // Remove from the MCEditors registry
      try {
        for (const [k, v] of MCEditors.map.entries()) {
          if (v === ed) { MCEditors.map.delete(k); break; }
        }
      } catch {}

      // Remove page from DOM and renumber
      try { pageEl.remove(); } catch {}
      updatePageNumbers();

      // Put the caret at the end of the previous page
      const prevEd = getEditorOfPage(prevEl) || MCEditors.first();
      try {
        prevEd?.chain().focus('end', { scrollIntoView: true }).run();
      } catch {
        try { prevEd?.commands?.focus?.('end'); } catch {}
      }

      // Keep drag/drop targets healthy
      window.__mc?.rewireDropTargets?.();
    }, 0);
  }, true); // capture so we beat ProseMirror's own Backspace handling
})();


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

let logoUrl = null;

upload?.addEventListener('change', () => {
  const file = upload.files?.[0];
  if (!file) return;

  if (logoUrl) URL.revokeObjectURL(logoUrl);
  logoUrl = URL.createObjectURL(file);

  setLogo(logoUrl); // your setLogo() already toggles .has-image and sets img.src
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