/* /src/rteditor/modules/editorInstance.js */
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

import EnterShortcuts   from "../extensions/enter-shortcuts.js";
import ListShortcuts    from "../extensions/list-shortcuts.js";
import FontSize         from "../extensions/font-size.js";
import SpacingExtension from "../extensions/spacing.js";

import SignatureField   from "../nodes/signature-field.js";

import { attachLivePagination } from "../modules/livePaginationController.js";

/* Hydration helper reused inside this module (keeps editor init tidy) */
function readInitialDocFromScriptTag() {
  const tag = document.getElementById('rt-initial-json');
  if (!tag) return null;

  const raw = (tag.textContent || '').replace(/^\uFEFF/, '').trim();
  if (!raw || raw.startsWith('<')) return null;

  try {
    const parsed = JSON.parse(raw);

    function stripPageBreaks(node) {
      if (!node || typeof node !== 'object') return node;
      if (Array.isArray(node)) return node.map(stripPageBreaks).filter(Boolean);

      if (node.type === 'pageBreak') return null;

      const out = { ...node };
      if (out.content) {
        out.content = out.content.map(stripPageBreaks).filter(Boolean);
      }
      return out;
    }

    return stripPageBreaks(parsed);
  } catch (err) {
    console.warn('[RTEditor] hydration skipped:', err);
    return null;
  }
}


export function getBasePath() {
  if (typeof window.BASE_PATH !== 'undefined' && window.BASE_PATH) return window.BASE_PATH;
  const path = window.location.pathname;
  const cut = path.indexOf('/dashboard');
  return cut > -1 ? path.slice(0, cut) : '';
}

export default function initBasicEditor(opts) {
  const { selector, editable = true, initialHTML = "<p>Start typing…</p>" } = opts || {};
  const mount = document.querySelector(selector);
  if (!mount) throw new Error(`[RTEditor] mount not found: ${selector}`);

  const editor = new Editor({
    element: mount,
    editable,
    content: (() => {
      const doc = readInitialDocFromScriptTag();
      if (doc && doc.type) return doc;
      return initialHTML;
    })(),
    pageContainerGetConfig: window.__RT_getPageConfig || (() => (typeof getCurrentPageConfig === 'function' ? getCurrentPageConfig() : null)),
    extensions: [
      StarterKit.configure({ history: true, strike: false }),
      EnterShortcuts,
      ListShortcuts,
      Underline, Strike, Subscript, Superscript,
      TextStyle, Color, Highlight,
      TextAlign.configure({ types: ['heading','paragraph'], alignments: ['left','center','right','justify'] }),
      FontFamily, FontSize, SpacingExtension,
      Table.configure({ resizable: true, lastColumnResizable: true, allowTableNodeSelection: true }),
      TableRow, TableHeader, TableCell,
      SignatureField,
      // PageContainerExtension, // Phase 1: virtual pages only
    ],
    editorProps: {
      handleDOMEvents: {
        focus(view, event) {
          if (document.body.classList.contains('rt-editing-header')) {
            return true; // 🛑 block PM focus
          }
          return false;
        },
        mousedown(view, event) {
          if (document.body.classList.contains('rt-editing-header')) {
            return true; // 🛑 block PM mouse handling
          }
          return false;
        }
      }
    }
  });

  try { editor.commands.focus('end'); } catch {}

  // Expose editor globally
  window.editor = editor;
  // === Phase 1: Live visual pagination (DOM-only, continuous flow) ===
  attachLivePagination(editor, {
    throttleMs: 120,
    debug: false,
  });

  // Expose safe runner to trigger pagination externally or from pageBootstrap.
  // Use editor instance by default; allow passing an explicit editor.
  try {
    window.__RT_runAutoPaginate = function (ed = editor, opts = {}) {
      try {
        // prefer the explicit exported API from paginationEngine
        return runAutoPaginate(ed, Object.assign({}, {
          pageEl: null,
          contentEl: document.getElementById('pageContainer') || document.getElementById('rt-canvas') || ed && ed.view && ed.view.dom,
          headerEl: document.getElementById('rtHeader'),
          footerEl: document.getElementById('rtFooter'),
          getPageConfig: ed && ed.options && ed.options.pageContainerGetConfig ? ed.options.pageContainerGetConfig : (typeof window.__RT_getPageConfig === 'function' ? window.__RT_getPageConfig : undefined),
          clearExisting: true,
        }, opts));
      } catch (e) {
        console.warn('[RTEditor] __RT_runAutoPaginate failed', e);
      }
    };
  } catch (e) { /* ignore */ }

  try {
    // If extension already attached one of them, prefer that function.
    const existingCleanup =
      (editor && editor.__rt_pageContainerCleanup) ||
      (editor && editor.__rt_cleanupPageContainer) ||
      null;

    // Set both properties (no-op if already identical)
    try { if (editor) editor.__rt_pageContainerCleanup = existingCleanup; } catch (e) { /* ignore */ }
    try { if (editor) editor.__rt_cleanupPageContainer = existingCleanup; } catch (e) { /* ignore */ }
  } catch (e) {
    // Defensive: don't break editor init if anything goes wrong here
    try { if (editor) editor.__rt_pageContainerCleanup = null; } catch (ee) { /* ignore */ }
    try { if (editor) editor.__rt_cleanupPageContainer = null; } catch (ee) { /* ignore */ }
  }

  return editor;
}
/* End of /src/rteditor/modules/editorInstance.js */
