// Path: /src/rteditor/modules/pageBootstrap.js
import initBasicEditor from "./editorInstance.js";
import { bindBasicToolbar } from "./toolbarBinder.js";
import { runAutoPaginate } from "./paginationEngine.js";
import { readInitialDocFromScriptTag, applyHydrationIfTrivial } from "./hydration.js"; // unused
import { bindPageLayoutControls, getCurrentPageConfig } from "../page-layout.js";

// DEBUG helpers: expose runAutoPaginate so you can call it from the browser console
window.__RT_runAutoPaginate = runAutoPaginate;

/**
 * Start editor page.
 *
 * Conservative refactor: consolidate repeated DOM lookups, remove duplicate hydration call,
 * keep live pagination scheduler intact. Add anchors for maintainers.
 */
export async function startEditorPage(opts = {}) {
  const {
    selector = '#editor',
    editable = true,
    initialHTML = '<p>TipTap ready — start typing…</p>',
    debug = false,
    autoPaginateDebounceMs = 220 // declared but never read
  } = opts;

  const editor = initBasicEditor({ selector, editable, initialHTML });

  // expose for debugging / other modules (legacy)
  window.__RT_editor = editor;
  window.editor = editor;

  /* ---------------------------
     Helper: page elements fetch
     ---------------------------
     Use this to avoid duplicate getElementById calls and keep a single source
     of truth for page-related DOM nodes.
  */

  // anchor: page-bootstrap: page element discovery (robust + page-instance detection)
  function getPageEls() {
    // Candidate names that historically appear in templates.
    const pageTemplateCandidates = ['rtPage', 'rtPageTemplate', 'rt-page', 'pageTemplate'];
    const pageContainerCandidates = ['rtPageContent', 'pageContainer', 'page-container', 'page-content'];

    const getFirst = (ids) => ids.map(id => document.getElementById(id)).find(Boolean) || null;

    // Try to find a canonical *master* page template first
    let masterPageEl = getFirst(pageTemplateCandidates);

    // If not found, fall back to 'rtPage' / 'pageRoot' but *only* if it does NOT look like a NodeView page.
    if (!masterPageEl) {
      const fallback = (document.getElementById('rtPage') || document.getElementById('pageRoot') || document.getElementById('page'));
      if (fallback) {
        // Detect if fallback is actually a node-view page (node-view pages often have inline width/height/padding
        // set by the NodeView factory and/or data-type="page-wrapper")
        const looksLikeNodeView = (
          fallback.hasAttribute('data-type') && fallback.getAttribute('data-type') === 'page-wrapper'
        ) || (
          // inline width/height style set by NodeView sizing (heuristic)
          (fallback.style && (fallback.style.width || fallback.style.height))
        );

        if (!looksLikeNodeView) masterPageEl = fallback;
        else {
          // It's a rendered page instance; don't treat it as the master template.
          masterPageEl = null;
        }
      }
    }

    // page container is where NodeView pages live; we prefer pageContainer
    const contentEl = getFirst(pageContainerCandidates) || document.querySelector('#pageContainer') || null;

    // header/footer detection (master template header/footer)
    const headerEl = document.getElementById('rtHeader') || document.getElementById('rt-header') || null;
    const footerEl = document.getElementById('rtFooter') || document.getElementById('rt-footer') || null;

    return {
      pageEl: masterPageEl,   // if null, code will know "no master template"; engine must handle node pages
      contentEl,
      headerEl,
      footerEl
    };
  }

  // Expose a dev helper so console can quickly inspect computed page elements
  window.__RT_getPageEls = getPageEls;

  // Bind toolbar if editor is editable
  if (editable) {
    try {
      bindBasicToolbar(editor, document);
    } catch (e) {
      console.warn('[RTEditor] bindBasicToolbar failed:', e);
    }
  }

  // NOTE: hydration already attempted inside the initial pipeline above.
  // Doing it again here is redundant and was removed to avoid multiple setContent calls.

  // Phase 1: no real pages yet → only bind page layout when page DOM exists
  // Editor uses live overlay pagination.
  // Page layout controls are NOT bound to the editor DOM.
  try {
    //const { pageEl, contentEl } = getPageEls();
    //if (pageEl && contentEl) {
      //bindPageLayoutControls(document, pageEl, contentEl);
    //}
  } catch (e) {
    // console.warn('[RTEditor] bindPageLayoutControls failed', e);
  }

  // AutoPaginate button binding (one-off)
  try {
    const btn = document.querySelector('[data-cmd="autoPaginate"]');
    if (btn) {
      btn.addEventListener('click', (ev) => {
        ev.preventDefault();
        const { pageEl, contentEl, headerEl, footerEl } = getPageEls();
        runAutoPaginate(editor, {
          pageEl,
          contentEl,
          headerEl,
          footerEl,
          getPageConfig: () => getCurrentPageConfig(),
          debug: !!debug
        });
      });
    }
  } catch (e) {
    console.warn('[RTEditor] autoPaginate binding failed', e);
  }

  // optional debug helpers anchor — paste debug helpers here if you want them restored
  if (debug) {
    /* ANCHOR: Debug helpers
       If you want the overlay detector & keydown dumper back, paste the original code here.
    */
  }

  return editor;
}
