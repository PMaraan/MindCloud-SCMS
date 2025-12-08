// Path: /src/rteditor/modules/pageBootstrap.js
import initBasicEditor from "./editorInstance.js";
import { bindBasicToolbar } from "./toolbarBinder.js";
import { runAutoPaginate } from "./paginationEngine.js";
import { readInitialDocFromScriptTag, applyHydrationIfTrivial } from "./hydration.js";
import { bindPageLayoutControls, getCurrentPageConfig } from "../page-layout.js";

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
    autoPaginateDebounceMs = 220
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
  function getPageEls() {
    return {
      pageEl: document.getElementById('rtPage'),
      contentEl: document.getElementById('rtPageContent'),
      headerEl: document.getElementById('rtHeader'),
      footerEl: document.getElementById('rtFooter'),
    };
  }

  // --- Initial pagination pipeline (safe, 2025-fix) ---
  (async () => {
    try {
      // small settle so layout roots exist
      await new Promise(r => setTimeout(r, 40));

      // Immediately apply hydration if server doc present so subsequent
      // pagination / wrapIntoPages run against the actual server document.
      try {
        const serverDoc = readInitialDocFromScriptTag();
        if (serverDoc) {
          // applyHydrationIfTrivial will setContent only if editor currently has a trivial doc
          const hydrated = applyHydrationIfTrivial(editor, serverDoc);
          if (hydrated && !!window.__RT_debugAutoPaginate) {
            console.log('[RTEditor] Applied server hydration (initial).');
          }
        }
      } catch (e) {
        console.warn('[RTEditor] initial hydration failed (nonfatal)', e);
      }

      const { pageEl, contentEl, headerEl, footerEl } = getPageEls();

      // 1) One-shot autoPaginate (DO NOT mutate if already in wrapper mode)
      try {
        if (!document.body.classList.contains('rt-pagewrapper-active')) {
          await runAutoPaginate(editor, {
            pageEl,
            contentEl,
            headerEl,
            footerEl,
            getPageConfig: () => getCurrentPageConfig(),
            clearExisting: true,
            safety: 14,
            debug: !!window.__RT_debugAutoPaginate,
          });
        }
      } catch (e) {
        console.warn('[RTEditor] initial autoPaginate failed (nonfatal)', e);
      }

      // 2) Convert pageBreaks → pageWrappers (if available)
      try {
        if (editor?.commands?.wrapIntoPages) {
          editor.commands.wrapIntoPages();
        }
      } catch (wrapErr) {
        console.warn('[RTEditor] wrapIntoPages() skipped', wrapErr);
      }
    } catch (outer) {
      console.warn('[RTEditor] initial pagination pipeline failed (swallowed)', outer);
    }
  })();

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

  // Bind page layout controls (safe to call even if elements are missing)
  try {
    const { pageEl, contentEl } = getPageEls();
    bindPageLayoutControls(document, pageEl, contentEl);
  } catch (e) {
    console.warn('[RTEditor] bindPageLayoutControls failed', e);
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
          clearExisting: true,
          debug: !!debug
        });
      });
    }
  } catch (e) {
    console.warn('[RTEditor] autoPaginate binding failed', e);
  }

  /* =========================================================================
     Word-style continuous pagination (idle-aware + ResizeObserver; stabilized)
     - Scheduler unchanged (behavior preserved) but DOM lookups consolidated.
     - Anchors: tuning constants located here for quick maintenance.
     ========================================================================= */
  try {
    const { pageEl, contentEl, headerEl, footerEl } = getPageEls();

    if (!pageEl || !contentEl) {
      console.warn('[RTEditor] live pagination skipped: missing page/content elements');
    } else {
      // Tunables (raised to avoid micro-looping)
      const IDLE_MS = typeof autoPaginateDebounceMs === 'number' ? autoPaginateDebounceMs : 220;
      const COOLDOWN_MS = 400;     // longer cooldown so layout + editor settle
      const HEIGHT_EPS_PX = 6;     // ignore tiny height changes <= 6px

      let idleTimer = null;
      let running = false;
      let pending = false;
      let lastHeight = 0;
      let lastDocSig = '';
      let cooldown = false;

      function docSignature() {
        try {
          const s = editor?.state?.doc;
          if (!s) return '';
          return `${s.content.size}|${s.content.childCount}`;
        } catch (e) {
          return '';
        }
      }

      function schedule() {
        // If pagination process is currently mutating the document, ignore scheduling.
        if (editor && editor.__rt_applyingPageBreaks) return;
        // If we've just paginated, avoid scheduling immediate re-run
        if (cooldown) {
          pending = true;
          return;
        }
        pending = true;
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => doPaginateIfNeeded(), IDLE_MS);
      }

      async function doPaginateIfNeeded() {
        try {
          if (!pending) return;
          if (running) {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(doPaginateIfNeeded, IDLE_MS);
            return;
          }

          const lastPagAt = editor && editor.__rt_lastPaginateAt ? editor.__rt_lastPaginateAt : 0;
          if (Date.now() - lastPagAt < COOLDOWN_MS) {
            // keep pending so we'll try again after cooldown
            clearTimeout(idleTimer);
            idleTimer = setTimeout(doPaginateIfNeeded, COOLDOWN_MS);
            return;
          }

          const curHeight = contentEl.scrollHeight || contentEl.getBoundingClientRect().height || 0;
          const curDocSig = docSignature();

          if (Math.abs(curHeight - lastHeight) <= HEIGHT_EPS_PX && curDocSig === lastDocSig) {
            pending = false;
            if (!!window.__RT_debugAutoPaginate) console.log('[RTEditor] pagination skipped (no material change)');
            return;
          }

          // Avoid running while user scrolls (we still keep pending)
          const scrollContainer = document.querySelector('.main-content') || window;
          if (scrollContainer && scrollContainer.__rt_userScrolling) {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(doPaginateIfNeeded, IDLE_MS + 120);
            return;
          }

          pending = false;
          running = true;
          try {
            await Promise.resolve(runAutoPaginate(editor, {
              pageEl,
              contentEl,
              headerEl,
              footerEl,
              getPageConfig: () => getCurrentPageConfig(),
              clearExisting: true,
              debug: !!window.__RT_debugAutoPaginate,
            }));

            // small settle time then snapshot the final state
            await new Promise(r => setTimeout(r, 60));

            lastHeight = contentEl.scrollHeight || contentEl.getBoundingClientRect().height || lastHeight;
            lastDocSig = docSignature();

            // mark cooldown to avoid immediate re-triggers (also visible to runOnce)
            cooldown = true;
            if (editor) editor.__rt_lastPaginateAt = Date.now();
            setTimeout(() => { cooldown = false; if (pending) schedule(); }, COOLDOWN_MS);
          } catch (err) {
            console.warn('[RTEditor] live pagination error', err);
          } finally {
            running = false;
          }
        } catch (outerErr) {
          console.warn('[RTEditor] live paginate scheduler fatal error (swallowed)', outerErr);
          running = false;
          pending = false;
        }
      }

      // 1) TipTap update hook: schedule when doc size changes (ignore selection-only updates)
      if (editor && typeof editor.on === 'function') {
        let lastSeenDocSize = (editor.state && editor.state.doc) ? editor.state.doc.content.size : null;
        editor.on('update', (tr) => {
          if (editor && editor.__rt_applyingPageBreaks) return;
          try {
            if (tr && typeof tr.docChanged !== 'undefined' && !tr.docChanged) return;
            const nowSize = (editor.state && editor.state.doc) ? editor.state.doc.content.size : null;
            if (nowSize === lastSeenDocSize) return;
            lastSeenDocSize = nowSize;
          } catch (e) { /* ignore */ }
          schedule();
        });
      }

      // 2) Layout changes: observe content + page element
      if (window.ResizeObserver) {
        const ro = new ResizeObserver(() => schedule());
        ro.observe(contentEl);
        ro.observe(pageEl);
      } else {
        window.addEventListener('resize', () => schedule(), { passive: true });
      }

      // 3) Run once at init
      requestAnimationFrame(() => { setTimeout(() => schedule(), 60); });

      window.__RT_requestPagination = () => schedule();
    }
  } catch (e) {
    console.warn('[RTEditor] word-style live pagination failed to initialize', e);
  }

  // optional debug helpers anchor — paste debug helpers here if you want them restored
  if (debug) {
    /* ANCHOR: Debug helpers
       If you want the overlay detector & keydown dumper back, paste the original code here.
    */
  }

  return editor;
}
