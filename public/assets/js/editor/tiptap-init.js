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
import { Plugin } from "https://esm.sh/prosemirror-state@1.4.3";


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

// --- Inline date input node used in the signature table ---
const SigDate = Node.create({
  name: 'sigDate',
  group: 'block',
  atom: true,
  selectable: true,

  addAttributes() {
    return {
      value: { default: '' },                 // ISO yyyy-mm-dd
      placeholder: { default: 'Date' },
    };
  },

  // TipTap should recognize either our div placeholder or an input (if pasted)
  parseHTML() {
    return [
      { tag: 'div.mc-date-box' },
      { tag: 'input.mc-date-box' },
    ];
  },

  // How it serializes back to HTML (keeps the same placeholder element)
  renderHTML({ HTMLAttributes }) {
    return ['div', { class: 'mc-date-box', 'data-ph': HTMLAttributes.placeholder || 'Date' }];
  },

  addNodeView() {
    return ({ node, getPos, editor }) => {
      const wrap = document.createElement('div');
      wrap.className = 'mc-date-box';

      const input = document.createElement('input');
      input.type = 'date';
      input.className = 'sig-date-input';
      if (node.attrs.value) input.value = node.attrs.value;

      /* NEW: keep the printed value in sync */
      wrap.dataset.value = input.value || '';

      input.addEventListener('change', () => {
        wrap.dataset.value = input.value || '';   // <— add this line

        const pos = getPos?.();
        if (typeof pos !== 'number') return;
        const tr = editor.state.tr.setNodeMarkup(pos, undefined, {
          ...node.attrs,
          value: input.value,
        });
        editor.view.dispatch(tr);
      });

      wrap.appendChild(input);

      return {
        dom: wrap,
        update(updatedNode) {
          if (updatedNode.type.name !== 'sigDate') return false;
          if (updatedNode.attrs.value !== input.value) {
            input.value = updatedNode.attrs.value || '';
          }
          return true;
        },
      };
    };
  },
});


// --- Disallow nested tables (same as working file) ---
const NoNestedTables = {
  name: 'noNestedTables',
  proseMirrorPlugins() {
    return [
      new Plugin({
        filterTransaction(tr) {
          if (!tr.docChanged) return true;
          let ok = true;
          tr.doc.descendants((node, pos) => {
            if (!ok) return false;
            if (node.type.name === 'table') {
              const $pos = tr.doc.resolve(pos);
              for (let d = $pos.depth - 1; d >= 0; d--) {
                if ($pos.node(d).type.name === 'table') { ok = false; break; }
              }
            }
            return ok;
          });
          return ok;
        },
      }),
    ];
  },
};

// --- Preserve table attrs (class, data-sig) and cell attrs (data-ph) ---
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
});

const TableCellWithAttrs = TableCell.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      'data-ph': {
        default: null,
        parseHTML: el => el.getAttribute('data-ph'),
        renderHTML: attrs => (attrs['data-ph'] ? { 'data-ph': attrs['data-ph'] } : {}),
      },
      class: {
        default: null,
        parseHTML: el => el.getAttribute('class'),
        renderHTML: attrs => (attrs.class ? { class: attrs.class } : {}),
      },
    };
  },
});


// Collect consecutive tables that come *after the current text block*,
// skipping empty paragraphs between them.
function collectConsecutiveTables(ed) {
  const out = { items: [], totalHeight: 0 };
  try {
    const { state, view } = ed;

    // 1) Find the end position of the current text block (paragraph/heading/list item)
    const $from = state.selection.$from;
    let depth = $from.depth;
    while (depth > 0 && !$from.node(depth).type.isTextblock) depth--;
    const afterBlockPos = $from.end(depth);   // position *after* current block

    // 2) Walk forward from there, skipping empty paras, gathering tables
    let $pos = state.doc.resolve(afterBlockPos);
    let nodeAfter = $pos.nodeAfter;

    const isEmptyTextblock = n =>
      n && n.type.isTextblock && (n.textContent || '').trim() === '';

    while (isEmptyTextblock(nodeAfter)) {
      $pos = state.doc.resolve($pos.pos + nodeAfter.nodeSize);
      nodeAfter = $pos.nodeAfter;
    }

    while (nodeAfter && nodeAfter.type && nodeAfter.type.name === 'table') {
      const pos = $pos.pos;
      const dom = view.nodeDOM(pos);
      const h = dom?.getBoundingClientRect?.().height || 240;
      out.items.push({ pos, node: nodeAfter, dom: dom || null, height: h });
      out.totalHeight += h;

      // advance past this table, again skipping empty paras
      $pos = state.doc.resolve($pos.pos + nodeAfter.nodeSize);
      nodeAfter = $pos.nodeAfter;
      while (isEmptyTextblock(nodeAfter)) {
        $pos = state.doc.resolve($pos.pos + nodeAfter.nodeSize);
        nodeAfter = $pos.nodeAfter;
      }
    }
  } catch {}
  return out;
}



// --- AutoPageBreak: intercept Enter inside ProseMirror and add a page if needed ---
// --- AutoPageBreak: intercept Enter; if next block is a table and it would
// overflow the footer, create a new page and move that table to the new page.
const AutoPageBreak = Extension.create({
  name: 'autoPageBreak',

  addKeyboardShortcuts() {
    return {
      Enter: () => {
        const ed    = this.editor;
        const host  = ed.options.element;
        const pageEl = host.closest('.page');
        if (!pageEl) return false;

        // --- helpers ----
        const footerTop = (() => {
          const footer = pageEl.querySelector('.page-footer');
          return (footer?.getBoundingClientRect()?.top) ?? host.getBoundingClientRect().bottom;
        })();

        const caret = ed.view.coordsAtPos(ed.state.selection.head);
        const GAP_ABOVE_FOOTER = 16;     // keep a little breathing room
        const ONE_LINE_HEIGHT  = 24;     // plain Enter (no table) needs at least this much

        // --- EARLY HAND-OFF ---
        // If there isn't room for one more plain line and the next page exists,
        // jump the caret to the top paragraph of the next page (create it if needed).
        const remainingEarly = Math.max(0, footerTop - GAP_ABOVE_FOOTER - caret.bottom);
        const nElEarly = pageEl.nextElementSibling && pageEl.nextElementSibling.classList.contains('page')
          ? pageEl.nextElementSibling
          : null;
        const nextEdEarly = nElEarly ? (window.__mc?.MCEditors?.get?.(nElEarly.id) || null) : null;

        if (remainingEarly < ONE_LINE_HEIGHT && nextEdEarly) {
          ensureTopParagraphAndFocus(nextEdEarly);
          return true;
        }

        // What comes after the *current block*?
        const tables = collectConsecutiveTables(ed);

        // If there are no tables right after this block, it's a normal Enter.
        // (Still ensure we don't type into the footer.)
        if (tables.items.length === 0) {
          const remaining = Math.max(0, footerTop - GAP_ABOVE_FOOTER - caret.bottom);
          if (remaining < ONE_LINE_HEIGHT) {
            const newPage = addPageAfter(pageEl);
            const ed2 = window.__mc?.MCEditors?.get?.(newPage.id);
            ed2?.chain().focus().insertContent('<p></p>').setTextSelection(2).run();
            return true;
          }
          return false;
        }

        // There *are* tables right after this block. Project total height.
        // There *are* tables right after this block. Project total height.
        // There *are* tables right after this block. Project total height.
        const projectedBottom = caret.bottom + tables.totalHeight + 8;
        const bottomLimit     = footerTop - GAP_ABOVE_FOOTER;

        if (projectedBottom <= bottomLimit) {
          // They fit on this page → let Enter behave normally.
          return false;
        }

        // --- Overflow path (move ALL those tables to next page, keep caret here) ---
        const oldSelTo = ed.state.selection.to;

        // 1) Leave a paragraph here (so you keep typing on this page)
        ed.chain().focus().insertContent('<p></p>').run();

        // IMPORTANT: inserting the paragraph shifts document positions.
        // Re-collect the tables NOW so we have correct, current positions.
        const tablesAfterInsert = collectConsecutiveTables(ed);

        // If for any reason we don't see tables now, just stop handling.
        if (tablesAfterInsert.items.length === 0) {
          // Put caret back in the new paragraph and bail out.
          ed.chain().focus().setTextSelection(Math.min(oldSelTo + 1, ed.state.doc.content.size)).run();
          return true;
        }

        // 2) Delete tables from this page (reverse order to keep positions valid)
        for (let i = tablesAfterInsert.items.length - 1; i >= 0; i--) {
          const { pos, node } = tablesAfterInsert.items[i];
          ed.chain().focus().deleteRange({ from: pos, to: pos + node.nodeSize }).run();
        }

        // 3) Add next page and insert all tables at the top
        const newPage = addPageAfter(pageEl);
        const ed2 = window.__mc?.MCEditors?.get?.(newPage.id);
        if (ed2) {
        // Make sure the next page starts with a guard paragraph (NBSP)
        const firstOnNext = ed2.state.doc.firstChild;
        if (!firstOnNext || firstOnNext.type.name !== 'paragraph') {
          ed2.chain().insertContentAt(1, '<p>\u00A0</p>').run();
        }
          // Insert tables **after** that paragraph (place caret at end)
          ed2.chain().focus('end').run();

          for (let i = 0; i < tablesAfterInsert.items.length; i++) {
            const json = tablesAfterInsert.items[i].node.toJSON();
            try { ed2.chain().insertContent(json).run(); }
            catch { ed2.chain().insertTable({ rows: 2, cols: 4, withHeaderRow: false }).run(); }

            // keep a spacer paragraph between multiple tables
            if (i !== tablesAfterInsert.items.length - 1) {
              ed2.chain().insertContent('<p></p>').run();
            }
          }
        }

        // 4) Put the caret BACK on this page (and re-assert after a tick)
        ed.chain().focus().setTextSelection(Math.min(oldSelTo + 1, ed.state.doc.content.size)).run();
        setTimeout(() => {
          try { ed.chain().focus().setTextSelection(Math.min(oldSelTo + 1, ed.state.doc.content.size)).run(); } catch {}
        }, 0);

        return true;
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

      // ⬇️ use the attr-preserving table + cells
      TableWithAttrs.configure({ resizable: false }),
      TableRow,
      TableHeader,
      TableCellWithAttrs,

      LineHeight,
      UploadBox,
      SigDate,

      // ⬇️ block nested table transactions
      {
        name: 'noNestedTablesExt',
        addProseMirrorPlugins() { return NoNestedTables.proseMirrorPlugins(); }
      },

      AutoPageBreak,   // keep your Enter-handler extension
    ],
  });

  // ⬇️ Prevent paste of a <table> when selection is already inside a table
  editor.view.dom.addEventListener('paste', (e) => {
    try {
      const html = e.clipboardData?.getData('text/html') || '';
      if (!html) return;
      if (/<table[\s>]/i.test(html) && isSelectionInsideTableForInit(editor)) {
        e.preventDefault();
        forceCaretOutsideTableForInit(editor, 'after');
        editor.chain().focus().insertContent(html).run();
        editor.chain().focus().insertContent('<p></p>').run();
      }
    } catch {}
  }, true);

  // ⬇️ Bind the flow engine so pages rebalance automatically
  bindFlowHandlers(editor);

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

function isSelectionInsideTableForInit(ed) {
  try {
    const { $from } = ed.state.selection;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') return true;
    }
  } catch {}
  return false;
}
function forceCaretOutsideTableForInit(ed, dir = 'after') {
  try {
    const { $from } = ed.state.selection;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') {
        const pos = dir === 'before' ? $from.before(d) : $from.after(d);
        ed.chain().setTextSelection(pos).run();
        if (dir === 'after') ed.chain().insertContent('<p>\u200B</p>').run();
        return;
      }
    }
  } catch {}
}


function addPageAfter(existingPageEl) {
  const pageNum = nextPageNumber();
  const sec = pageTemplate(pageNum);
  existingPageEl.after(sec);
  const ed = initEditorForPage(sec);
  return sec;
}

// ===================== FLOW ENGINE =====================
function pageOfEditor(ed){ return ed?.options?.element?.closest?.('.page') || null; }
function nextPageEl(el){ const n = el?.nextElementSibling; return (n && n.classList.contains('page')) ? n : null; }
function prevPageEl(el){ const p = el?.previousElementSibling; return (p && p.classList.contains('page')) ? p : null; }

function measureEditor(ed){
  const pageEl = pageOfEditor(ed);
  const box    = pageEl?.querySelector('[data-editor]');
  const prose  = box?.querySelector('.ProseMirror') || box;
  const footer = pageEl?.querySelector('.page-footer');
  if (!box || !prose) return { limit: 0, used: 0 };

  const boxTop     = box.getBoundingClientRect().top;
  const bottomEdge = footer ? footer.getBoundingClientRect().top
                            : box.getBoundingClientRect().bottom;

  const limit = Math.max(0, bottomEdge - boxTop);
  const used  = prose.scrollHeight;
  return { limit, used };
}

const MIN_GUARD = 24;
function getFlowGuardPx(ed){
  const pageEl = pageOfEditor(ed);
  const box    = pageEl?.querySelector('[data-editor]');
  const prose  = box?.querySelector('.ProseMirror') || box;
  if (!prose) return MIN_GUARD;

  const cs = getComputedStyle(prose);
  let lh = parseFloat(cs.lineHeight);
  if (!isFinite(lh)) {
    const fs = parseFloat(cs.fontSize) || 16;
    lh = fs * 1.25;
  }
  return Math.max(MIN_GUARD, Math.ceil(lh + 4));
}

function cloneJSON(obj){ return JSON.parse(JSON.stringify(obj || {})); }
function isEmptyBlock(node){
  if (!node) return true;
  if (node.type !== 'paragraph') return false;
  const c = node.content || [];
  if (!c.length) return true;
  if (c.length === 1 && c[0].type === 'text' && (!c[0].text || !c[0].text.trim())) return true;
  return false;
}
function ensureDocNotEmpty(json){
  if (!json.content || json.content.length === 0) {
    json.content = [{ type:'paragraph' }];
  }
  return json;
}

function nbspParagraph(){ return { type:'paragraph', content:[{ type:'text', text:'\u00A0' }]}; }
function isTableNode(n){ return n && n.type === 'table'; }
// Treat normal spaces, NBSP (\u00A0) and ZWSP (\u200B) as whitespace
function textIsBlank(str = '') {
  return str.replace(/[\u00A0\u200B]/g, '').trim() === '';
}
function isEmptyParagraphNode(n){
  if (!n || n.type !== 'paragraph') return false;
  const c = n.content || [];
  if (!c.length) return true;
  return (
    c.length === 1 &&
    c[0].type === 'text' &&
    (!c[0].text || textIsBlank(c[0].text))
  );
}
function isEmptyTopLevelParagraph(n){
  if (!n || n.type !== 'paragraph') return false;
  const c = n.content || [];
  if (c.length === 0) return true;
  if (c.length === 1 && c[0].type === 'text' && textIsBlank(c[0].text || '')) return true;
  return false;
}
function isGuardParagraphNode(n){
  if (!n || n.type?.name !== 'paragraph') return false;
  const c = n.content;
  if (!c || c.childCount === 0) return true;
  if (
    c.childCount === 1 &&
    c.firstChild.type.name === 'text' &&
    textIsBlank(c.firstChild.text || '')
  ) return true;
  return false;
}


// --- keep selection while we mutate current page content ---
function preserveCaret(ed, mutate) {
  try {
    const pos = ed.state.selection?.to ?? 1;
    mutate();
    const safe = Math.min(Math.max(1, pos), ed.state.doc.content.size);
    ed.chain().setTextSelection(safe).run();
  } catch {
    mutate();
  }
}

function popLastRealBlock(ed, keepCaret = false){
  const doIt = () => {
    const json = cloneJSON(ed.getJSON());
    const arr = json.content || [];
    if (!arr.length) return null;

    while (arr.length && isEmptyTopLevelParagraph(arr[arr.length - 1])) arr.pop();
    if (!arr.length) {
      ensureDocNotEmpty(json);
      ed.commands.setContent(json, false);
      return null;
    }

    const node = arr.pop();
    ensureDocNotEmpty(json);
    ed.commands.setContent(json, false);
    return node;
  };
  if (!keepCaret) return doIt();
  let out = null;
  preserveCaret(ed, () => { out = doIt(); });
  return out;
}

function popLastBlock(ed, keepCaret = false){
  const doIt = () => {
    const json = cloneJSON(ed.getJSON());
    const arr  = json.content || [];
    if (!arr.length) return null;
    const last = arr.pop();
    ensureDocNotEmpty(json);
    ed.commands.setContent(json, false);
    return last;
  };
  if (!keepCaret) return doIt();
  let out = null;
  preserveCaret(ed, () => { out = doIt(); });
  return out;
}

function shiftFirstBlock(ed, keepCaret = false){
  const doIt = () => {
    const json = cloneJSON(ed.getJSON());
    const arr  = json.content || [];
    if (!arr.length) return null;
    const first = arr.shift();
    ensureDocNotEmpty(json);
    ed.commands.setContent(json, false);
    return first;
  };
  if (!keepCaret) return doIt();
  let out = null;
  preserveCaret(ed, () => { out = doIt(); });
  return out;
}

function prependBlock(ed, node, keepCaret = false){
  const doIt = () => {
    const json = cloneJSON(ed.getJSON());
    const arr  = json.content || [];
    if (isTableNode(node)){
      if (isEmptyParagraphNode(arr[0])) {
        arr[0] = nbspParagraph();
        json.content = [arr[0], node, ...arr.slice(1)];
      } else {
        json.content = [nbspParagraph(), node, ...arr];
      }
    } else {
      json.content = [node, ...arr];
    }
    ed.commands.setContent(json, false);
  };
  return keepCaret ? preserveCaret(ed, doIt) : doIt();
}

function appendBlock(ed, node, keepCaret = false){
  const doIt = () => {
    const json = cloneJSON(ed.getJSON());
    const arr  = json.content || [];
    const last = arr[arr.length-1];
    if (isTableNode(node)){
      if (isEmptyParagraphNode(last)) {
        arr[arr.length-1] = nbspParagraph();
        json.content = [...arr, node];
      } else {
        json.content = [...arr, nbspParagraph(), node];
      }
    } else {
      json.content = [...arr, node];
    }
    ed.commands.setContent(json, false);
  };
  return keepCaret ? preserveCaret(ed, doIt) : doIt();
}

function hasAnyRealContent(ed){
  const json = ed.getJSON();
  const arr = json.content || [];
  if (!arr.length) return false;
  if (arr.length === 1 && isEmptyBlock(arr[0])) return false;
  return true;
}

async function ensureNextPageEditor(curPageEl){
  let n = nextPageEl(curPageEl);
  if (!n){
    n = addPageAfter(curPageEl);
  }
  const key = n.id || n.dataset.page;
  return window.__mc?.MCEditors?.get?.(key) || null;
}

function getPrevPageEditor(ed) {
  try {
    const curPage = pageOfEditor(ed);
    const prevEl = prevPageEl(curPage);
    if (!prevEl) return null;
    return window.__mc?.MCEditors?.get?.(prevEl.id) || null;
  } catch { return null; }
}

// Approx height of the first table on this page (fallback to 240px)
function estimateFirstTableHeightPx(ed) {
  try {
    const dom = ed?.options?.element?.querySelector?.('.ProseMirror table');
    const h = dom?.getBoundingClientRect?.().height;
    return (h && isFinite(h)) ? h : 240;
  } catch { return 240; }
}


// --- helpers used by caret-carry ---
function topIndexOfSelection(ed) {
  try { return ed.state.selection.$from.index(0); } catch { return 0; }
}
function lastRealTopIndex(ed) {
  try {
    const { doc } = ed.state;
    for (let i = doc.childCount - 1; i >= 0; i--) {
      const n = doc.child(i);
      if (n.type.name !== 'paragraph') return i;
      const c = n.content;
      if (!c || c.childCount === 0) continue;
      if (c.childCount === 1 && c.firstChild.type.name === 'text' && !c.firstChild.text?.trim()) continue;
      return i;
    }
    return Math.max(0, doc.childCount - 1);
  } catch { return 0; }
}

// Put caret at the end of the FIRST block, preferring the paragraph
// that we insert above a table when it becomes the first node.
function caretToTopParagraph(ed) {
  try {
    const first = ed.state.doc.firstChild;
    // If the first node is a paragraph, put the caret inside it (end of content)
    if (first && first.type && first.type.name === 'paragraph') {
      const start = 1; // first node in the doc starts at position 1
      const contentSize = first.content ? first.content.size : 0;
      ed.chain().focus().setTextSelection(start + contentSize).run();
    } else {
      // Fallback: put caret at the very start
      ed.chain().focus().setTextSelection(1).run();
    }
    ed.view.scrollIntoView();
  } catch {}
}

// Ensure there is a paragraph as the first node, then focus it and put the caret at its end.
function ensureTopParagraphAndFocus(ed) {
  try {
    const doc = ed.state.doc;
    const first = doc.firstChild;

    if (!first || first.type.name !== 'paragraph') {
      ed.commands.setContent({
        type: 'doc',
        content: [{ type: 'paragraph' }, ...(doc.toJSON().content || [])],
      }, false);
    }

    const nowFirst = ed.state.doc.firstChild;
    const startPos = 1; // first top-level node starts at 1
    const len = nowFirst?.content?.size || 0;
    ed.chain().focus().setTextSelection(startPos + len).run();
    ed.view.scrollIntoView();
  } catch {}
}

// Put caret at the end of the paragraph that is *immediately above*
// the last table in the document. Returns true if it moved the caret.
function caretToParagraphAboveLastTable(ed) {
  try {
    const doc = ed.state.doc;

    // find the last table and the paragraph right before it
    for (let i = doc.childCount - 1; i >= 1; i--) {
      const node = doc.child(i);
      if (node.type?.name === 'table') {
        const prev = doc.child(i - 1);
        if (prev && prev.type?.name === 'paragraph') {
          // compute the start position of that previous paragraph
          let startPos = 1;
          for (let k = 0; k < i - 1; k++) startPos += doc.child(k).nodeSize;

          // caret at the end of that paragraph’s content
          const caretPos = startPos + (prev.content?.size || 0);
          ed.chain().focus().setTextSelection(caretPos).run();
          ed.view.scrollIntoView();
          return true;
        }
        break; // found a table but no paragraph above it
      }
    }
  } catch {}
  return false;
}


async function flowForward(ed){
  let guard = 20;
  while (guard-- > 0){
    const { limit, used } = measureEditor(ed);
    const G = getFlowGuardPx(ed);
    if (used <= limit - G) break;

    const curPage = pageOfEditor(ed);
    const nextEd  = await ensureNextPageEditor(curPage);
    if (!nextEd) break;

    const selTopIdx = topIndexOfSelection(ed);
    let lastRealIdx = ed.state.doc.childCount - 1;
    while (lastRealIdx >= 0 && isEmptyTopLevelParagraph(ed.state.doc.child(lastRealIdx))) lastRealIdx--;
    const carryCaret = selTopIdx >= lastRealIdx && lastRealIdx >= 0;

    // mutate CURRENT page → keep caret
    const node = popLastRealBlock(ed, true);
    if (!node) break;

    // mutate NEXT page → don't touch its caret
    prependBlock(nextEd, node, false);

    if (carryCaret) {
      requestAnimationFrame(() => {
        try {
        // place caret in the paragraph *above* the table (if present)
        caretToTopParagraph(nextEd);
        } catch {}
      });
    }
    ed = nextEd;
  }
}

async function flowBackward(ed){
  let guard = 20;
  while (guard-- > 0){
    const { limit, used } = measureEditor(ed);
    const G = getFlowGuardPx(ed);
    if (used >= limit - G) break;

    const curPage = pageOfEditor(ed);
    const nEl = nextPageEl(curPage);
    if (!nEl) break;

    const nEd = window.__mc?.MCEditors?.get?.(nEl.id) || null;
    if (!nEd || !hasAnyRealContent(nEd)) break;

    const carryBack = (topIndexOfSelection(nEd) === 0);

    // mutate NEXT page, but NEVER drag content that would break a page-start table
    const firstNode  = nEd.state.doc.firstChild;
    const secondNode = firstNode ? nEd.state.doc.child(1) : null;

    // Case A: table is already first on the next page → don't pull anything back
    if (firstNode && firstNode.type && firstNode.type.name === 'table') {
      break;
    }

    // Case B: guard paragraph followed by a table → keep the guard with the table
    if (isGuardParagraphNode(firstNode) && secondNode && secondNode.type?.name === 'table') {
      break;
    }

    const node = shiftFirstBlock(nEd, false);
    if (!node) break;

    // mutate CURRENT page where user is typing → keep caret
    appendBlock(ed, node, true);

    const m = measureEditor(ed);
    if (m.used > m.limit - getFlowGuardPx(ed)){
      popLastBlock(ed, true);
      prependBlock(nEd, node, false);
      break;
    }

    if (carryBack) {
      requestAnimationFrame(() => {
        try { ed.chain().focus('end', { scrollIntoView: true }).run(); } catch {}
      });
    }
  }
}

async function rebalanceAround(ed){
  if (!ed) return;
  await flowForward(ed);
  await flowBackward(ed);
  await flowForward(ed);
  updatePageNumbers();
}

function bindFlowHandlers(ed){
  if (ed._mcFlowBound) return;
  ed._mcFlowBound = true;
  ed.on('update', () => { rebalanceAround(ed); });
}

function updatePageNumbers(){
  document.querySelectorAll('.page .page-num').forEach((span, idx) => {
    span.textContent = String(idx + 1);
  });
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

// ==== Backspace on an empty page removes that page ====
(function setupDeleteEmptyPageHotkey(){
  const isEditorEmpty = (ed) => {
    try {
      const j = ed.getJSON();
      const c = j.content || [];
      if (!c.length) return true;

      if (c.length === 1 && c[0].type === 'paragraph') {
        const p = c[0];
        if (!p.content || !p.content.length) return true;
        if (p.content.length === 1 && p.content[0].type === 'text') {
          const t = p.content[0].text || '';
          return textIsBlank(t);
        }
      }
      return false;
    } catch { return false; }
  };
  const canDeletePage = (pageEl) => {
    const ov = pageEl?.querySelector('.mc-block-overlay');
    return !(ov && ov.querySelector('.mc-block'));
  };

  document.addEventListener('keydown', (ev) => {
    if (ev.key !== 'Backspace' || ev.defaultPrevented) return;

    const pm = ev.target?.closest?.('.ProseMirror');
    if (!pm) return;

    // resolve active editor
    let ed = null;
    try {
      const all = window.__mc?.MCEditors?.all?.() || [];
      for (const e of all) {
        if (e.options.element.contains(pm)) { ed = e; break; }
      }
    } catch {}

    if (!ed) return;
    const sel = ed.state?.selection;

    // === Handle guard-paragraph + table AT THE TOP OF PAGE (runs BEFORE any early return) ===
    try {
      const topIdx = topIndexOfSelection(ed);
      const doc = ed.state.doc;
      const first  = doc.firstChild;
      const second = doc.childCount > 1 ? doc.child(1) : null;

      const inFirstBlock   = sel?.empty && topIdx === 0;
      const atStartOfFirstBlock =
      sel?.empty &&
      topIdx === 0 &&
      (sel.$from.parentOffset === 0);  // <— only when caret is at the very start

      const guardPlusTable = isGuardParagraphNode(first) && (second && second.type?.name === 'table');
      console.log('guard?', isGuardParagraphNode(first),
            'second is table?', !!(second && second.type?.name === 'table'));

    
    if (atStartOfFirstBlock && guardPlusTable) {

        const prevEd = getPrevPageEditor(ed);

        // If there's no previous page/editor, let native Backspace happen.
        if (!prevEd) return;

        ev.preventDefault();
        ev.stopPropagation();

        // --- optimistic move: pull table up, then validate and rollback if needed ---
        // 1) remove the guard paragraph on current page
        const guardNode = shiftFirstBlock(ed, false);

        // 2) pull the (now first) table node
        const tableNode = shiftFirstBlock(ed, false);

        if (tableNode) {
          // 3) append the table to the end of the previous page
          appendBlock(prevEd, tableNode, true);

          // 4) validate: if previous page overflows after the move, rollback
          const { limit, used } = measureEditor(prevEd);
          const G = getFlowGuardPx(prevEd);

          if (used > (limit - G)) {
            // rollback → remove what we just appended and put it back
            const rolledBack = popLastBlock(prevEd, true); // table we appended
            if (rolledBack) {
              // restore table to current page at the top
              prependBlock(ed, rolledBack, false);
            }
            // restore guard paragraph if it existed
            if (guardNode) prependBlock(ed, guardNode, false);

            // not enough room → just move caret to previous page so user can make space
            try { prevEd.chain().focus('end', { scrollIntoView: true }).run(); } catch {}
            return;
          }
          // 5) success: table stayed on previous page. If this page is now empty of real content, remove it.
          const pageElToMaybeRemove = ed?.options?.element?.closest('.page');
          const focusAboveTable = () => {
            if (!caretToParagraphAboveLastTable(prevEd)) {
              // fallback if structure isn't as expected
              try { prevEd.chain().focus('end', { scrollIntoView: true }).run(); } catch {}
            }
          };

          if (!hasAnyRealContent(ed)) {
            setTimeout(() => {
              try { ed.destroy(); } catch {}
              try { pageElToMaybeRemove?.remove(); } catch {}
              updatePageNumbers();
              focusAboveTable();
              window.__mc?.rewireDropTargets?.();
            }, 0);
          } else {
            focusAboveTable();
          }
        } else {
          // No table found after guard (shouldn't happen). Put caret on previous page.
          try { prevEd.chain().focus('end', { scrollIntoView: true }).run(); } catch {}
        }
        return;
      }


      // --- NEW: plain caret hop when you're at the very top (even without guard+table) ---
    if (atStartOfFirstBlock) {
        const prevEd = getPrevPageEditor(ed);
        if (prevEd) {
          ev.preventDefault();
          ev.stopPropagation();
          try { prevEd.chain().focus('end', { scrollIntoView: true }).run(); } catch {}
          return; // handled
        }
      }
    } catch {}

    // === If NOT handled above, fall back to original “delete empty page” rule ===
    if (!sel?.empty || sel.from !== 1 || !isEditorEmpty(ed)) return;

    const pageEl = ed?.options?.element?.closest('.page');
    const prevEl = pageEl ? pageEl.previousElementSibling : null;
    if (!pageEl || !(prevEl && prevEl.classList.contains('page')) || !canDeletePage(pageEl)) return;

    ev.preventDefault();
    ev.stopPropagation();

    setTimeout(() => {
      try { ed.destroy(); } catch {}
      try {
        const map = window.__mc?.MCEditors;
        if (map && map.get) {
          // remove this editor key from registry
          // (our registry removes on 'destroy', so this is just belt+suspenders)
        }
      } catch {}
      try { pageEl.remove(); } catch {}
      updatePageNumbers();

      // focus end of previous page
      const prevEd = getPrevPageEditor(ed);
      try { prevEd?.chain().focus('end', { scrollIntoView: true }).run(); } catch {}
      window.__mc?.rewireDropTargets?.();
    }, 0);
  }, true);
})();

