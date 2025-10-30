// /public/assets/js/rteditor/collab-editor.js
import { Editor } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";

import Underline    from "@tiptap/extension-underline";
import Strike       from "@tiptap/extension-strike";
import Subscript    from "@tiptap/extension-subscript";
import Superscript  from "@tiptap/extension-superscript";
import TextStyle    from "@tiptap/extension-text-style";
import Color        from "@tiptap/extension-color";
import Highlight    from "@tiptap/extension-highlight";
import TextAlign    from "@tiptap/extension-text-align";
import FontFamily   from "@tiptap/extension-font-family";

import Table        from "@tiptap/extension-table";
import TableRow     from "@tiptap/extension-table-row";
import TableCell    from "@tiptap/extension-table-cell";
import TableHeader  from "@tiptap/extension-table-header";

// Local nodes & extensions

import EnterShortcuts   from "./extensions/enter-shortcuts.js";
import ListShortcuts    from "./extensions/list-shortcuts.js";
import FontSize         from "./extensions/font-size.js";
import SpacingExtension from "./extensions/spacing.js";
//import AutoPageBreak    from "./extensions/auto-page-break.js";

import PageBreak        from "./nodes/page-break.js";
import SignatureField   from "./nodes/signature-field.js";

function postJSON(url, payload, extraHeaders = {}) {
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...extraHeaders,
    },
    body: JSON.stringify(payload),
    credentials: 'same-origin',
  });
}

// Build base path safely
function getBasePath() {
  if (typeof window.BASE_PATH !== 'undefined' && window.BASE_PATH) return window.BASE_PATH;
  const path = window.location.pathname;
  const cut = path.indexOf('/dashboard');
  return cut > -1 ? path.slice(0, cut) : '';
}

/** Build editor with common word-like extensions */
export default function initBasicEditor(opts) {
  const { selector, editable = true, initialHTML = "<p>Start typing…</p>" } = opts || {};
  const mount = document.querySelector(selector);
  if (!mount) throw new Error(`[RTEditor] mount not found: ${selector}`);

  const editor = new Editor({
    element: mount,
    editable,
    content: initialHTML,
    extensions: [
      StarterKit.configure({ 
        history: true,
        strike: false,
      }),
      EnterShortcuts,
      ListShortcuts,

      Underline,
      Strike,
      Subscript,
      Superscript,
      TextStyle,
      Color,
      Highlight,

      TextAlign.configure({
        types: ['heading', 'paragraph'],
        alignments: ['left', 'center', 'right', 'justify']
      }),

      FontFamily,
      FontSize,
      SpacingExtension,

      Table.configure({
        resizable: true,
        lastColumnResizable: true,
        allowTableNodeSelection: true,
      }),
      TableRow,
      TableHeader,
      TableCell,

      SignatureField,
      PageBreak,
      // AutoPageBreak,
    ],
  });

  try { editor.commands.focus("end"); } catch {}

  // Make available for other modules / debug tools
  window.editor = editor;

  // --- One-time hydration from server-injected payload (Option B) ---
  try {
    // Preferred: init-time object placed by the controller page before this script runs
    let tiptapDoc = null;

    if (window.__RT_pendingContent) {
      tiptapDoc = window.__RT_pendingContent;
      // clear after use to avoid re-using stale data
      window.__RT_pendingContent = null;
    } else {
      // Fallback: read the <script id="rt-loaded-content"> tag if present
      const tag = document.getElementById('rt-loaded-content');
      if (tag) {
        const payload = JSON.parse(tag.textContent || '{}');
        const raw     = payload && payload.content;
        tiptapDoc = (typeof raw === 'string') ? JSON.parse(raw) : (raw || null);

        // Optional: title hint for your UI
        if (payload && payload.title) {
          const titleEl = document.querySelector('[data-rt-title]') || document.querySelector('.card-title, h2, h1');
          if (titleEl) titleEl.textContent = payload.title + ' (Template)';
        }
      }
    }

    if (tiptapDoc) {
      editor.commands.setContent(tiptapDoc, false); // false = don't add a new history step
    }
  } catch (e) {
    console.warn('[RTEditor] hydration failed:', e);
  }

  return editor;
}

export function bindBasicToolbar(editor, root = document) {
  let currentTextColor = '#000000';
  let currentHighlightColor = '#fff59d';

  const highlightInput = root.querySelector('[data-cmd-input="setHighlight"]');
  if (highlightInput?.value) currentHighlightColor = highlightInput.value;
  const textColorInput = root.querySelector('[data-cmd-input="setColor"]');
  if (textColorInput?.value) currentTextColor = textColorInput.value;

  const map = {
    toggleBold: () => editor.chain().focus().toggleBold().run(),
    toggleItalic: () => editor.chain().focus().toggleItalic().run(),
    toggleUnderline: () => editor.chain().focus().toggleUnderline().run(),
    toggleStrike: () => editor.chain().focus().toggleStrike().run(),
    toggleSubscript: () => editor.chain().focus().toggleSubscript().run(),
    toggleSuperscript: () => editor.chain().focus().toggleSuperscript().run(),

    bulletList: () => editor.chain().focus().toggleBulletList().run(),
    orderedList: () => editor.chain().focus().toggleOrderedList().run(),
    indentList: () => editor.chain().focus().sinkListItem('listItem').run(),
    outdentList: () => editor.chain().focus().liftListItem('listItem').run(),

    alignLeft: () => editor.chain().focus().setTextAlign('left').run(),
    alignCenter: () => editor.chain().focus().setTextAlign('center').run(),
    alignRight: () => editor.chain().focus().setTextAlign('right').run(),
    alignJustify: () => editor.chain().focus().setTextAlign('justify').run(),

    setColor: (hex) => { currentTextColor = hex || currentTextColor; return editor.chain().focus().setColor(currentTextColor).run(); },
    unsetColor: () => editor.chain().focus().unsetColor().run(),

    applyHighlight: () => {
      const color = currentHighlightColor || '#fff59d';
      if (editor.isActive('highlight', { color })) {
        return editor.chain().focus().unsetHighlight().run();
      }
      return editor.chain().focus().setHighlight({ color }).run();
    },
    setHighlight: (color) => { currentHighlightColor = color || currentHighlightColor; return editor.chain().focus().setHighlight({ color: currentHighlightColor }).run(); },
    unsetHighlight: () => editor.chain().focus().unsetHighlight().run(),

    undo: () => editor.chain().focus().undo().run(),
    redo: () => editor.chain().focus().redo().run(),

    setFontFamily: (family) => editor.chain().focus().setFontFamily(family).run(),
    unsetFontFamily: () => editor.chain().focus().setFontFamily(null).run(),

    setFontSize: (size) => editor.chain().focus().setFontSize(size).run(),
    unsetFontSize: () => editor.chain().focus().unsetFontSize().run(),

    setLineSpacing: (lh) => editor.chain().focus().setLineHeight(lh).run(),
    unsetLineSpacing: () => editor.chain().focus().unsetLineHeight().run(),
    setParaBefore: (pt) => editor.chain().focus().setParagraphSpacingBefore(pt).run(),
    setParaAfter:  (pt) => editor.chain().focus().setParagraphSpacingAfter(pt).run(),
    unsetParaSpacing: () => editor.chain().focus().unsetParagraphSpacing().run(),

    insertTable: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    addRowBefore: () => editor.chain().focus().addRowBefore().run(),
    addRowAfter:  () => editor.chain().focus().addRowAfter().run(),
    deleteRow:    () => editor.chain().focus().deleteRow().run(),
    addColumnBefore: () => editor.chain().focus().addColumnBefore().run(),
    addColumnAfter:  () => editor.chain().focus().addColumnAfter().run(),
    deleteColumn:    () => editor.chain().focus().deleteColumn().run(),
    toggleHeaderRow: () => editor.chain().focus().toggleHeaderRow().run(),
    mergeCells:      () => editor.chain().focus().mergeCells().run(),
    splitCell:       () => editor.chain().focus().splitCell().run(),
    deleteTable:     () => editor.chain().focus().deleteTable().run(),

    insertSignature: () => editor.chain().focus().insertSignatureField({ label: 'Signature', role: '', required: true }).run(),
    sigSetRole: (role) => editor.chain().focus().updateSignatureField({ role }).run(),
    sigToggleRequired: () => {
      const current = editor.getAttributes('signatureField')?.required;
      return editor.chain().focus().updateSignatureField({ required: !current }).run();
    },

    insertPageBreak: () => editor.chain().focus().insertPageBreak().run(),

    saveDoc: async () => {
      try {
        // Decide scope & id from URL
        // ---------------------------------------------------------------------------
        // [Purpose] Resolve current editing target (template|syllabus) and numeric id
        // - Accepts: ?templateId=, ?syllabusId=
        // - Fallback: ?id= (assume template unless scope=syllabus is explicitly present)
        // ---------------------------------------------------------------------------
        const params = new URLSearchParams(location.search);
        const _tpl = parseInt(params.get('templateId') || '0', 10);
        const _syl = parseInt(params.get('syllabusId') || '0', 10);

        let id   = parseInt(params.get('id') || '0', 10);
        let scope = '';

        // Preferred explicit params
        if (_tpl > 0)        { id = _tpl; scope = 'template'; }
        else if (_syl > 0)   { id = _syl; scope = 'syllabus'; }
        else if (id > 0)     { scope = (params.get('scope') === 'syllabus') ? 'syllabus' : 'template'; }

        if (!id) {
          // Redirect back to Syllabus Templates with a flash flag; controller will convert it to FlashHelper
          const base = (typeof window.BASE_PATH === 'string') ? window.BASE_PATH : '';
          window.location.href = `${base}/dashboard?page=syllabus-templates&flash=missing-id`;
          throw new Error('Missing id'); // stop further execution
        }

        // ---------------------------------------------------------------------------
        // [Purpose] Read initial TipTap JSON payload from the view (safe hydration)
        // - Looks for <script id="rt-initial-json" type="application/json">...</script>
        // - Returns JS object or null
        // ---------------------------------------------------------------------------
        function getInitialTipTapJSON() {
          const tag = document.getElementById('rt-initial-json');
          if (!tag) return null;
          const raw = tag.textContent || '';
          if (!raw.trim()) return null;
          try { return JSON.parse(raw); }
          catch (e) {
            console.warn('[RTEditor] hydration failed:', e);
            return null;
          }
        }

        // ---------------------------------------------------------------------------
        // [Purpose] Resolve scope/id from #rt-meta if URL didn’t have them
        // ---------------------------------------------------------------------------
        (function syncScopeIdFromMeta() {
          const metaEl = document.getElementById('rt-meta');
          if (!metaEl) return;
          const ds = metaEl.dataset || {};
          if (!scope && ds.scope) scope = ds.scope;
          if (!id && (ds.id|0) > 0) id = ds.id|0;
        })();

        // Collect TipTap JSON
        const json = editor.getJSON();

        // Optional filename (you can wire an input later)
        const filename = null;

        // CSRF (if you expose a token, add it here)
        const headers = {};
        if (window.CSRF_TOKEN) headers['X-CSRF-Token'] = window.CSRF_TOKEN;

        const base = getBasePath();
        const url  = `${base}/dashboard?page=rteditor&action=snapshot`;

        const resp = await postJSON(url, { scope, id, json, filename }, headers);
        const data = await resp.json();

        if (!resp.ok || !data?.ok) {
          throw new Error(data?.error || `HTTP ${resp.status}`);
        }

        // Tiny UX tick (replace with your own toast/flash)
        console.log('[RTEditor] Saved', data);
        // Optional: visual confirmation
        const btn = root.querySelector('[data-cmd="saveDoc"]');
        if (btn) {
          const old = btn.innerHTML;
          btn.innerHTML = '<i class="bi bi-check2-circle"></i>';
          setTimeout(() => { btn.innerHTML = old; }, 1200);
        }
      } catch (e) {
        console.error('[RTEditor] Save failed:', e);
        const btn = root.querySelector('[data-cmd="saveDoc"]');
        if (btn) {
          const old = btn.innerHTML;
          btn.innerHTML = '<i class="bi bi-x-circle"></i>';
          setTimeout(() => { btn.innerHTML = old; }, 1500);
        }
      }
    },

  };

  // buttons
  root.querySelectorAll('[data-cmd]').forEach(btn => {
    const cmd = btn.getAttribute('data-cmd');
    if (!map[cmd]) return;
    btn.addEventListener('click', e => { e.preventDefault(); map[cmd](); });
  });

  // inputs
  root.querySelectorAll('[data-cmd-input]').forEach(inp => {
    const cmd = inp.getAttribute('data-cmd-input');
    if (!map[cmd]) return;
    inp.addEventListener('input', e => {
      const val = e.target.value;
      if (!val) return;
      if (cmd === 'setHighlight') currentHighlightColor = val;
      if (cmd === 'setColor') currentTextColor = val;
      map[cmd](val);
    });
    inp.addEventListener('dblclick', () => {
      const clearCmd = (cmd === 'setColor') ? 'unsetColor' : (cmd === 'setHighlight' ? 'unsetHighlight' : null);
      if (clearCmd && map[clearCmd]) map[clearCmd]();
    });
  });

  // Ctrl+S / Cmd+S
  root.addEventListener('keydown', (ev) => {
    const isMac = /Mac|iPhone|iPad/.test(navigator.platform);
    const mod = isMac ? ev.metaKey : ev.ctrlKey;
    if (mod && ev.key.toLowerCase() === 's') {
      ev.preventDefault();
      map.saveDoc();
    }
  });
}

