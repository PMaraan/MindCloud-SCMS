// /public/assets/js/rteditor/modules/pageBootstrap.js
import initBasicEditor from "./editorInstance.js";
import { bindBasicToolbar } from "./toolbarBinder.js";
import { runAutoPaginate } from "./paginationEngine.js";
import { readInitialDocFromScriptTag, applyHydrationIfTrivial } from "./hydration.js";
import { bindPageLayoutControls, getCurrentPageConfig } from "../page-layout.js";
import { bindManualPagination } from "../manual-pagination.js";

/**
 * Start editor page.
 */
export async function startEditorPage(opts = {}) {
  const { selector = '#editor', editable = true, initialHTML = '<p>TipTap ready — start typing…</p>', debug = false, autoPaginateDebounceMs = 220 } = opts;

  const editor = initBasicEditor({ selector, editable, initialHTML });
  window.__RT_editor = editor;
  window.editor = editor;

  if (editable) {
    try { bindBasicToolbar(editor, document); } catch (e) { console.warn('[RTEditor] bindBasicToolbar failed:', e); }
  }

  // Hydration (if server doc present and editor trivial)
  try {
    const serverDoc = readInitialDocFromScriptTag();
    if (serverDoc) applyHydrationIfTrivial(editor, serverDoc);
  } catch (e) { console.warn('[RTEditor] hydration guard failed', e); }

  // bind page layout
  try {
    const pageEl    = document.getElementById('rtPage');
    const contentEl = document.getElementById('rtPageContent');
    bindPageLayoutControls(document, pageEl, contentEl);
  } catch (e) { console.warn('[RTEditor] bindPageLayoutControls failed', e); }

  // manual preview
  try {
    const previewRoot = document.getElementById('pagePreviewRoot');
    if (previewRoot) {
      const { refresh: refreshPreview } = bindManualPagination(editor, {
        pagePreviewRoot: previewRoot,
        headerEl: document.getElementById('rtHeader'),
        footerEl: document.getElementById('rtFooter'),
        getPageConfig: () => getCurrentPageConfig(),
      });
      document.addEventListener('rt:page-layout-updated', () => refreshPreview());
    }
  } catch (e) { console.warn('[RTEditor] bindManualPagination failed:', e); }

  // autoPaginate button
  try {
    const btn = document.querySelector('[data-cmd="autoPaginate"]');
    if (btn) {
      btn.addEventListener('click', (ev) => {
        ev.preventDefault();
        runAutoPaginate(editor, {
          pageEl: document.getElementById('rtPage'),
          contentEl: document.getElementById('rtPageContent'),
          headerEl: document.getElementById('rtHeader'),
          footerEl: document.getElementById('rtFooter'),
          getPageConfig: () => getCurrentPageConfig(),
          clearExisting: true,
          debug: !!debug
        });
      });
    }
  } catch (e) { console.warn('[RTEditor] autoPaginate binding failed', e); }

   // === Word-style continuous pagination (idle-aware + ResizeObserver; stabilized) ===
  try {
    const pageEl    = document.getElementById('rtPage');
    const contentEl = document.getElementById('rtPageContent');
    const headerEl  = document.getElementById('rtHeader');
    const footerEl  = document.getElementById('rtFooter');

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
        if (!pending) return;
        if (running) {
          clearTimeout(idleTimer);
          idleTimer = setTimeout(doPaginateIfNeeded, IDLE_MS);
          return;
        }

        // If pagination was just performed by runOnce (other tab), skip until cooldown expires
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
          if (DEBUG_FLAG) console.log('[RTEditor] pagination skipped (no material change)');
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
      }

      // 1) TipTap update hook: schedule when doc size changes (ignore selection-only updates)
      if (editor && typeof editor.on === 'function') {
        let lastSeenDocSize = (editor.state && editor.state.doc) ? editor.state.doc.content.size : null;
        editor.on('update', (tr) => {
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

  // optional debug helpers
  if (debug) {
    // overlay detector & keydown dumper — same as before; omitted for brevity (you can copy from prior code)
  }

  return editor;
}
