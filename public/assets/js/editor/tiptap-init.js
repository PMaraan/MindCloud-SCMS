// /public/assets/js/editor/tiptap-init.js
// Creates TipTap editors per page, manages a registry, adds pages,
// and cooperates with TemplateBuilder-New.js utilities.

import { Editor, Extension, Node } from "https://esm.sh/@tiptap/core@2.6.6";
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
import Table from "https://esm.sh/@tiptap/extension-table@2.6.6";
import TableRow from "https://esm.sh/@tiptap/extension-table-row@2.6.6";
import TableHeader from "https://esm.sh/@tiptap/extension-table-header@2.6.6";
import TableCell from "https://esm.sh/@tiptap/extension-table-cell@2.6.6";

// ----------------------------
// 1) Small extras used by UI
// ----------------------------

// Minimal line-height helper used by toolbar buttons.
const LineHeight = Extension.create({
  name: 'lineHeight',
  addGlobalAttributes() {
    return [
      {
        types: ['paragraph', 'heading'],
        attributes: {
          styleLineHeight: {
            default: null,
            parseHTML: el => el.style.lineHeight || null,
            renderHTML: attrs =>
              attrs.styleLineHeight ? { style: `line-height:${attrs.styleLineHeight}` } : {},
          },
        },
      },
    ];
  },
  addCommands() {
    return {
      setLineHeight:
        value =>
        ({ commands }) =>
          // set on both paragraph + heading so current block gets it
          (commands.updateAttributes('paragraph', { styleLineHeight: value }) &&
           commands.updateAttributes('heading',  { styleLineHeight: value })),
      unsetLineHeight:
        () =>
        ({ commands }) =>
          (commands.updateAttributes('paragraph', { styleLineHeight: null }) &&
           commands.updateAttributes('heading',  { styleLineHeight: null })),
    };
  },
});

// Very small "UploadBox" node so the toolbar’s insertUploadBox works.
// (It’s a simple node-view with a hidden <input type="file">.)
const UploadBox = Node.create({
  name: 'uploadBox',
  group: 'block',
  atom: true,
  selectable: true,
  parseHTML() { return [{ tag: 'div.mc-upload-box' }]; },
  renderHTML() {
    return ['div', { class: 'mc-upload-box', 'data-has-image': '0' },
      ['img', { class: 'mc-upload-img' }],
      ['button', { class: 'mc-upload-btn', type: 'button' }, 'Upload'],
    ];
  },
  addNodeView() {
    return ({ node }) => {
      const dom = document.createElement('div');
      dom.className = 'mc-upload-box';
      dom.dataset.hasImage = '0';

      const img = document.createElement('img');
      img.className = 'mc-upload-img';

      const btn = document.createElement('button');
      btn.className = 'mc-upload-btn';
      btn.type = 'button';
      btn.textContent = 'Upload';

      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.style.display = 'none';

      btn.onclick = () => input.click();
      input.onchange = () => {
        const f = input.files?.[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = () => {
          img.src = r.result;
          dom.dataset.hasImage = '1';
        };
        r.readAsDataURL(f);
      };

      dom.appendChild(img);
      dom.appendChild(btn);
      dom.appendChild(input);

      return { dom };
    };
  },
  addCommands() {
    return {
      insertUploadBox:
        () =>
        ({ commands }) =>
          commands.insertContent({ type: 'uploadBox' }),
    };
  },
});

// --- AutoPageBreak: intercept Enter inside ProseMirror and add a page if needed ---
const AutoPageBreak = Extension.create({
  name: 'autoPageBreak',
  addKeyboardShortcuts() {
    return {
      Enter: () => {
        const ed = this.editor;
        const host = ed.options.element;
        const pageEl = host.closest('.page');
        if (!pageEl) return false;

        const caret = ed.view.coordsAtPos(ed.state.selection.head);
        const footer = pageEl.querySelector('.page-footer');
        const footerTop = footer?.getBoundingClientRect().top ?? host.getBoundingClientRect().bottom;

        const GAP = 16;   // space you want to keep above the footer
        const LINE = 24;  // approx line-height that a new line needs
        const remaining = Math.max(0, footerTop - GAP - caret.bottom);

        if (remaining < LINE) {
          const newPage = addPageAfter(pageEl);               // you already have this helper
          const ed2 = window.__mc?.MCEditors?.get?.(newPage.id);
          ed2?.chain().focus().insertContent('<p></p>').setTextSelection(2).run();
          return true; // handled (prevents overflow on current page)
        }
        return false;   // let Enter behave normally
      },
    };
  },
});


// --------------------------------------
// 2) Editor registry exposed on window
// --------------------------------------
const MCEditors = (() => {
  const map = new Map(); // key = page id
  let activeKey = null;

  return {
    add(key, ed) {
      map.set(key, ed);
      ed.on('focus', () => { activeKey = key; });
      ed.on('destroy', () => { if (activeKey === key) activeKey = null; map.delete(key); });
    },
    get(key) { return map.get(key) || null; },
    all() { return Array.from(map.values()); },
    getActive() { return (activeKey && map.get(activeKey)) || null; },
  };
})();

window.__mc = window.__mc || {};
window.__mc.MCEditors = {
  add: (key, ed) => MCEditors.add(key, ed),
  get: key => MCEditors.get(key),
  all: () => MCEditors.all(),
};
window.__mc.getActiveEditor = () => MCEditors.getActive();

// --------------------------------------
// 3) Page helpers
// --------------------------------------
function nextPageNumber() {
  const last = document.querySelector('#mc-work .page:last-of-type');
  const n = +(last?.dataset.page || 1);
  return n + 1;
}

function pageTemplate(pageNum) {
  const sec = document.createElement('section');
  sec.className = 'page size-A4';
  sec.id = `page-${pageNum}`;
  sec.dataset.page = String(pageNum);
  sec.tabIndex = 0;
  sec.innerHTML = `
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
    <div class="tiptap" data-editor aria-label="Document editor"></div>
    <footer class="page-footer" aria-label="Page footer">
      <span class="footer-left" contenteditable="true" data-placeholder="Footer Text">Footer Text</span>
      <span class="footer-right">Page <span class="page-num">${pageNum}</span></span>
    </footer>
  `;
  return sec;
}

function initEditorForPage(pageEl) {
  const host = pageEl.querySelector('.tiptap');
  const pageKey = pageEl.id || pageEl.dataset.page || Math.random().toString(36).slice(2);

  const editor = new Editor({
    element: host,
    content: '<p></p>',
    extensions: [
      StarterKit.configure({ bulletList: { keepMarks: true } }),
      Underline,
      Link.configure({ openOnClick: false }),
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Placeholder.configure({ placeholder: 'Start typing…' }),
      TextStyle, Color, FontFamily,
      Superscript, Subscript,
      TaskList, TaskItem,
      Table.configure({ resizable: false }),
      TableRow, TableHeader, TableCell,
      LineHeight,
      UploadBox,
      AutoPageBreak,
    ],
  });

  // reflect active editor for toolbar positioning in TemplateBuilder-New.js
  editor.on('focus', () => {
    try {
      const bar = document.body.querySelector('.tt-tablebar');
      if (bar) bar.dataset.editorKey = pageEl.id || pageEl.dataset.page || '';
    } catch {}
  });

  MCEditors.add(pageKey, editor);

  // Let overlay/toolbar code wire to this page/editor
  setTimeout(() => {
    window.__mc?.rewireDropTargets?.();
    document.dispatchEvent(new Event('mc:rewire'));
  }, 0);

  return editor;
}

function addPageAfter(existingPageEl) {
  const pageNum = nextPageNumber();
  const sec = pageTemplate(pageNum);
  existingPageEl.after(sec);
  const ed = initEditorForPage(sec);
  return sec;
}

// --- NEW: ensure there is room on the current page; otherwise add a page ---
function ensureRoomForTable(editor, pageEl, approxHeightPx = 220) {
  try {
    const host = pageEl.querySelector('.tiptap');
    if (!host) return editor; // nowhere to compare

    const hostRect = host.getBoundingClientRect();

    // where is the caret right now?
    const { to } = editor.state.selection;
    const caret = editor.view.coordsAtPos(to);

    // how much space remains above the footer/padding?
    const bottomLimit = hostRect.bottom - 72;         // ~footer padding
    const projected = caret.bottom + approxHeightPx;  // caret + table height

    // If it won't fit, spawn next page and return THAT editor for insertion.
    if (projected > bottomLimit) {
      const newPage = addPageAfter(pageEl);
      const ed2 = window.__mc?.MCEditors?.get?.(newPage.id);
      ed2?.commands.focus('end');
      return ed2 || editor;
    }
  } catch {}
  return editor;
}

// expose to TemplateBuilder-New.js
window.__mc = window.__mc || {};
window.__mc.ensureRoomForTable = (approxHeightPx = 220) => {
  const ed = window.__mc?.getActiveEditor?.();
  if (!ed) return null;
  const pageEl = ed?.options?.element?.closest('.page');
  if (!pageEl) return ed;
  return ensureRoomForTable(ed, pageEl, approxHeightPx);
};


// --------------------------------------
// 4) Controls: Paper size + Add Page
// --------------------------------------
function applyPaperSize(size) {
  document.querySelectorAll('.page').forEach(p => {
    p.classList.remove('size-A4', 'size-Letter', 'size-Legal');
    p.classList.add(`size-${size}`);
  });
}

function wireControls() {
  const selPaper = document.getElementById('ctl-paper');
  const btnAdd = document.getElementById('ctl-addpage');

  if (selPaper) {
    selPaper.addEventListener('change', () => applyPaperSize(selPaper.value || 'A4'));
  }
  if (btnAdd) {
    btnAdd.addEventListener('click', () => {
      const last = document.querySelector('#mc-work .page:last-of-type');
      if (last) addPageAfter(last);
    });
  }

  // Sidebar collapse toggle (optional: if you want it)
  const sbToggle = document.getElementById('sb-toggle');
  const shell = document.getElementById('mc-shell');
  sbToggle?.addEventListener('click', () => {
    shell?.classList.toggle('sb-collapsed');
  });
}

// --------------------------------------
// 5) Boot
// --------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  // Initialize the first (server-rendered) page
  const firstPage = document.querySelector('#mc-work .page');
  if (firstPage) initEditorForPage(firstPage);

  wireControls();
  applyPaperSize((document.getElementById('ctl-paper')?.value) || 'A4');
});
