// /public/assets/js/rteditor/modules/editorInstance.js
import { Editor } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
// Page container plugin — visually group PM children into page boxes (split at data-page-break)
//import attachPageContainer from "../extensions/pageContainer.js";

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

import PageBreak        from "../nodes/page-break.js";
import SignatureField   from "../nodes/signature-field.js";
import PageWrapper from "../nodes/page-wrapper.js";

// page container extension (installs the wrapper on editor lifecycle)
import PageContainerExtension from "../extensions/pageContainerExtension.js";

/* Hydration helper reused inside this module (keeps editor init tidy) */
function readInitialDocFromScriptTag() {
  const tag = document.getElementById('rt-initial-json');
  if (!tag) return null;
  const raw = (tag.textContent || '').replace(/^\uFEFF/, '').trim();
  if (!raw || raw.startsWith('<')) return null;
  try {
    const parsed = JSON.parse(raw);
    if (parsed && typeof parsed === 'object' && parsed.json && typeof parsed.json === 'object') {
      return parsed.json;
    }
    return parsed;
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
      SignatureField, PageBreak,
      // pageWrapper Node (serializable): convert flow into per-page nodes + NodeView
      PageWrapper,
    ],
  });

  try { editor.commands.focus("end"); } catch {}

  // Expose editor globally
  window.editor = editor;

  // Ensure page-container cleanup is available under both common property names.
  // Some implementations attach cleanup under __rt_pageContainerCleanup, others
  // used __rt_cleanupPageContainer. Normalize so console checks and callers work.
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

  // Optional: auto-convert top-level flow with pageBreak markers into pageWrapper nodes
  try {
    // run once after initialization to transform into page nodes if any pageBreak exists
    if (editor && typeof editor.commands.wrapIntoPages === 'function') {
      // attempt to convert top-level flow into pageWrapper nodes — nonfatal if it fails (module mismatch)
      try {
        if (editor.commands && typeof editor.commands.wrapIntoPages === 'function') {
          const ok = editor.commands.wrapIntoPages();
          if (!ok) console.info('[RTEditor] wrapIntoPages returned false (no pages created)');
        }
      } catch (wrapErr) {
        console.warn('[RTEditor] wrapIntoPages skipped due to runtime mismatch (nonfatal):', wrapErr && wrapErr.message ? wrapErr.message : wrapErr);
        // keep running — auto-pagination + page-container wrappers still work (we'll fix the root cause next)
      }
    }
  } catch (e) { console.warn('[RTEditor] wrapIntoPages failed', e); }

  return editor;
}
