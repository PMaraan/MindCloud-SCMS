// /src/rteditor/modules/editorInstance.js
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
    const doc = (parsed && typeof parsed === 'object' && parsed.json && typeof parsed.json === 'object') ? parsed.json : parsed;

    // flatten nested pageWrapper nodes (same logic as hydration.js)
    function flattenNode(node) {
      if (!node || typeof node !== 'object') return node;
      const t = (node.type || '').toString();
      if (!Array.isArray(node.content) || node.content.length === 0) return node;
      const isWrapper = (t === 'pageWrapper' || t === 'page-wrapper' || t === 'page_wrapper');
      if (isWrapper) {
        const newContent = [];
        for (const child of node.content) {
          if (child && typeof child === 'object') {
            const ct = (child.type || '').toString();
            if (ct === 'pageWrapper' || ct === 'page-wrapper' || ct === 'page_wrapper') {
              if (Array.isArray(child.content)) {
                for (const grand of child.content) {
                  newContent.push(flattenNode(grand));
                }
              }
            } else {
              newContent.push(flattenNode(child));
            }
          } else {
            newContent.push(child);
          }
        }
        return { ...node, content: newContent };
      }
      // non-wrapper: recurse
      return { ...node, content: node.content.map(c => flattenNode(c)) };
    }

    return flattenNode(doc);
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
      PageContainerExtension,
    ],
  });

  // ---- one-time normalization: flatten nested pageWrapper nodes ----
  function flattenExistingPageWrappers(editor) {
    try {
      const { state, view } = editor;
      const tr = state.tr;
      let changed = false;

      // Walk doc to find pageWrapper nodes that contain pageWrapper children
      state.doc.descendants((node, pos) => {
        if (!node || !node.type) return true;
        if (node.type.name !== 'pageWrapper') return true;

        // If any child is a pageWrapper, we will replace that child with its content
        const childrenToFlatten = [];
        node.forEach((child, idx) => {
          if (child && child.type && child.type.name === 'pageWrapper') {
            // compute child's start position relative to document
            let cursor = pos + 1; // first child offset
            for (let i = 0; i < idx; i++) cursor += node.child(i).nodeSize;
            const childFrom = cursor;
            const childTo = childFrom + child.nodeSize;
            childrenToFlatten.push({ from: childFrom, to: childTo });
          }
        });

        if (childrenToFlatten.length) {
          // We'll perform replacements from end->start so positions remain valid.
          for (let i = childrenToFlatten.length - 1; i >= 0; i--) {
            const { from, to } = childrenToFlatten[i];
            // replace the child wrapper with its inner content (slice)
            const slice = state.doc.slice(from, to).content;
            tr.replaceRangeWith(from, to, slice);
          }
          changed = true;
        }

        return true; // continue traversal
      });

      if (changed && tr.docChanged) {
        view.dispatch(tr.setMeta('addToHistory', false));
        console.info('[RTEditor] flattened nested pageWrapper nodes (one-time normalization)');
      }
    } catch (e) {
      console.warn('[RTEditor] flattenExistingPageWrappers failed:', e);
    }
  }

  // call the normalizer right after Editor created so state is clean for NodeViews
  flattenExistingPageWrappers(editor);

  try { editor.commands.focus("end"); } catch {}

  // Expose editor globally
  window.editor = editor;

  //
  // === Move NodeView pages into #pageContainer (safe reparent after PM rendered) ===
  // Rationale: TipTap/ProseMirror renders NodeView DOM inside the .ProseMirror root.
  // We reparent those `.rt-node-page` elements into our #pageContainer so they
  // become true top-level visual pages while keeping the NodeView contentDOM links intact.
  //
  /*
  (function reparentNodeViewPages() {
    // small helper to perform the reparent pass
    function doMove() {
      try {
        const container = document.getElementById('pageContainer');
        if (!container) {
          // If no pageContainer, nothing to do.
          return;
        }
        // ProseMirror root (editor.element may be hidden); look for node views inside it
        const pmRoot = editor && editor.view && editor.view.dom ? editor.view.dom : null;
        if (!pmRoot) return;

        // Move only direct node-view page elements found beneath the ProseMirror tree.
        // Use querySelectorAll with an array snapshot; appendChild will reparent in document.
        const nodes = Array.from(pmRoot.querySelectorAll('.rt-node-page'));
        if (!nodes.length) return;

        let moved = 0;
        for (const n of nodes) {
          // Only move if not already inside our container
          if (!container.contains(n)) {
            try {
              container.appendChild(n);
              moved++;
            } catch (e) {
              // ignore single-element failures
            }
          }
        }
        if (moved && window.__RT_debugAutoPaginate) {
          console.log(`[RTEditor] moved ${moved} node-view page(s) into #pageContainer`);
        }
      } catch (err) {
        // non-fatal
        if (window.__RT_debugAutoPaginate) console.warn('[RTEditor] reparentNodeViewPages failed', err);
      }
    }

    // Try a few times to cover timing: immediate + microtask + small timeout.
    // Some NodeViews may be created asynchronously. This sequence is resilient.
    try { doMove(); } catch (_) {}
    // microtask
    Promise.resolve().then(() => { try { doMove(); } catch (_) {} });
    // small timeout to catch late NodeView attachments
    setTimeout(() => { try { doMove(); } catch (_) {} }, 40);
    // one more delayed attempt - covers slower devices
    setTimeout(() => { try { doMove(); } catch (_) {} }, 220);
  })();*/

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

    // --- CLIENT-SIDE: Flatten nested pageWrapper nodes (safety net) ---
  try {
    // Build a transaction that unwraps nested pageWrapper nodes (outer->inner flatten)
    const { state, view } = editor;
    const tr = state.tr;
    let mutated = false;

    state.doc.descendants((node, pos) => {
      if (!node || !node.type) return true;
      if (node.type.name !== 'pageWrapper' && node.type.name !== 'page-wrapper' && node.type.name !== 'page_wrapper') return true;

      // Check if this pageWrapper has direct child that is pageWrapper -> we want to unwrap
      if (node.content && node.content.length > 0) {
        // detect any child being a pageWrapper
        for (let i = 0; i < node.content.childCount; i++) {
          const child = node.content.child(i);
          if (child && child.type && (child.type.name === 'pageWrapper' || child.type.name === 'page-wrapper' || child.type.name === 'page_wrapper')) {
            // we will replace the outer node [pos, pos + node.nodeSize) with the child's inner content
            // compute range of inner content
            const start = pos;
            const end = pos + node.nodeSize;
            // create a fragment that concatenates all inner children that are NOT pageWrapper wrappers
            // For simplicity, we'll extract the content slice between pos+1 .. pos+node.nodeSize-1 and use replaceRangeWith
            const slice = state.doc.slice(pos + 1, pos + node.nodeSize - 1);
            // replace the outer node with the slice content
            tr.replaceRangeWith(pos, end, slice.content);
            mutated = true;
            // stop descending this node (we've scheduled a replacement)
            return false;
          }
        }
      }
      return true;
    });

    if (mutated) {
      view.dispatch(tr.setMeta('addToHistory', false));
      // small settle
      setTimeout(() => {
        try { if (editor) editor.commands.focus('end'); } catch (_) {}
      }, 60);
    }
  } catch (err) {
    console.warn('[RTEditor] client flatten pass failed (nonfatal)', err);
  }

  return editor;
}
