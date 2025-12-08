// /src/rteditor/extensions/pageContainer.js
// Light, safe wrapper: do NOT mutate .ProseMirror children.
// Instead we wrap the existing editor.view.dom in a wrapper that contains
// a header slot, the editor DOM (left untouched) and a footer slot.
// This prevents ProseMirror from ever seeing header/footer clones and stops
// mutation-feedback loops. This is an immediate hotfix — full per-page layout
// (NodeView) is the next step.

function mmToPx(mm) { return (parseFloat(mm) || 0) / 25.4 * 96; }

function createWrapperDom() {
  const wrap = document.createElement('div');
  wrap.className = 'rt-page-wrapper-outer';

  const header = document.createElement('div');
  header.className = 'rt-page-header';
  header.setAttribute('data-page-part', 'header');
  header.setAttribute('contenteditable', 'false');

  const inner = document.createElement('div');
  inner.className = 'rt-page-edit-host';
  inner.setAttribute('data-page-part', 'body');

  const footer = document.createElement('div');
  footer.className = 'rt-page-footer';
  footer.setAttribute('data-page-part', 'footer');
  footer.setAttribute('contenteditable', 'false');

  // Structure: wrapper -> header, inner, footer
  wrap.appendChild(header);
  wrap.appendChild(inner);
  wrap.appendChild(footer);
  return { wrap, header, inner, footer };
}

function syncHeaderFooterSlots(headerSlot, footerSlot, headerMaster, footerMaster) {
  try {
    headerSlot.innerHTML = headerMaster ? headerMaster.innerHTML : '';
  } catch (e) { headerSlot.textContent = headerMaster ? headerMaster.textContent : ''; }
  try {
    footerSlot.innerHTML = footerMaster ? footerMaster.innerHTML : '';
  } catch (e) { footerSlot.textContent = footerMaster ? footerMaster.textContent : ''; }
}

/**
 * attachPageContainer(editor, options)
 * - This DOES NOT mutate .ProseMirror children.
 * - It will wrap editor.view.dom in a wrapper and copy header/footer into slots.
 * - Returns cleanup() to restore original DOM.
 */
export default function attachPageContainer(editor, options = {}) {
  const { getPageConfig = null } = options;
  if (!editor || !editor.view || !editor.view.dom) {
    console.warn('[pageContainer] attach skipped: no editor/view dom');
    return () => {};
  }

  const pmDom = editor.view.dom; // this is the .ProseMirror root element
  // If pmDom is already wrapped by our wrapper, don't double-wrap
  if (pmDom.__rt_wrappedByPageContainer) {
    return () => {};
  }

  // Create wrapper and find master header/footer
  const { wrap, header, inner, footer } = createWrapperDom();

  // Prefer header/footer that live inside the visible page shell (#rtPage) so they are included in page sizing.
  // Fallback to global elements if not found.
  const pageShell = document.getElementById('rtPage');
  let headerMaster = null, footerMaster = null;
  if (pageShell) {
    headerMaster = pageShell.querySelector('#rtHeader') || document.getElementById('rtHeader');
    footerMaster = pageShell.querySelector('#rtFooter') || document.getElementById('rtFooter');
  } else {
    headerMaster = document.getElementById('rtHeader');
    footerMaster = document.getElementById('rtFooter');
  }

  // If the editable master header/footer already live inside the same page shell
  // that contains the ProseMirror DOM, we must NOT show the wrapper clones for that
  // particular host page (otherwise user sees duplicates).
  const pmInsidePageShell = pageShell && pageShell.contains(pmDom);
  const masterHeaderInsidePage = headerMaster && pageShell && pageShell.contains(headerMaster);
  const masterFooterInsidePage = footerMaster && pageShell && pageShell.contains(footerMaster);

  // If pmDom is inside the page shell and the master header/footer are present there,
  // suppress the wrapper slots so we don't duplicate content. We still keep the wrapper
  // structure for potential future multi-page rendering and for sizing.
  const suppressHeaderSlot = !!(pmInsidePageShell && masterHeaderInsidePage);
  const suppressFooterSlot = !!(pmInsidePageShell && masterFooterInsidePage);

  // Move the existing ProseMirror dom into inner (preserve node identity)
  // Note: we must replace pmDom in the DOM with wrap, then append pmDom into inner.
  const parent = pmDom.parentNode;
  if (!parent) {
    console.warn('[pageContainer] pmDom has no parent — cannot wrap safely');
    return () => {};
  }

  parent.replaceChild(wrap, pmDom);
  inner.appendChild(pmDom);

  // Mark wrapped so re-attaches won't duplicate
  pmDom.__rt_wrappedByPageContainer = true;
  wrap.__rt_wrapMarker = true;

  // If we need to hide the header/footer slots for this host (master already visible),
  // set display:none — they remain in DOM but are invisible and not kept in sync.
  if (suppressHeaderSlot) header.style.display = 'none';
  if (suppressFooterSlot) footer.style.display = 'none';

  // initial sync (only fill slots if not suppressed)
  if (!suppressHeaderSlot || !suppressFooterSlot) {
    syncHeaderFooterSlots(header, footer, headerMaster, footerMaster);
  }

  // Keep header/footer clones in sync with masters via MutationObserver (light)
  const moConfig = { childList: true, subtree: true, characterData: true };
  let headerObserver = null, footerObserver = null;
  try {
    if (headerMaster && !suppressHeaderSlot) {
      headerObserver = new MutationObserver(() => syncHeaderFooterSlots(header, footer, headerMaster, footerMaster));
      headerObserver.observe(headerMaster, moConfig);
    }
    if (footerMaster && !suppressFooterSlot) {
      footerObserver = new MutationObserver(() => syncHeaderFooterSlots(header, footer, headerMaster, footerMaster));
      footerObserver.observe(footerMaster, moConfig);
    }
  } catch (e) { /* ignore */ }

  // If page config supplies width/height, apply to wrapper (optional)
  function applyPageConfig() {
    try {
      if (typeof getPageConfig !== 'function') return;
      const cfg = getPageConfig();
      if (!cfg || !cfg.size) return;
      const isLandscape = cfg.orientation === 'landscape';
      const pageW_mm = isLandscape ? (cfg.size.hmm || cfg.size.h) : (cfg.size.wmm || cfg.size.w);
      const pageH_mm = isLandscape ? (cfg.size.wmm || cfg.size.w) : (cfg.size.hmm || cfg.size.h);
      const toPx = (mm) => (parseFloat(mm) || 0) / 25.4 * 96;
      const w = Math.round(toPx(pageW_mm || cfg.size.w || 210));
      const h = Math.round(toPx(pageH_mm || cfg.size.h || 297));
      wrap.style.width = `${w}px`;
      wrap.style.minHeight = `${h}px`;
    } catch (e) { /* ignore */ }
  }
  applyPageConfig();

  // Optional: expose manual refresh
  window.__RT_refreshPageContainerWrapper = () => {
    syncHeaderFooterSlots(header, footer, headerMaster, footerMaster);
    applyPageConfig();
  };

  // ---- expose cleanup on the editor instance so tests / devtools can remove the wrapper ----
  // Save a reference to the cleanup function on the editor so you can call:
  //   window.editor.__rt_pageContainerCleanup && window.editor.__rt_pageContainerCleanup();
  // from the console during development.
  const cleanup = function cleanup() {
    try {
      // Move pmDom back to original parent position (replace wrap)
      if (wrap.parentNode) {
        wrap.parentNode.replaceChild(pmDom, wrap);
      } else {
        // fallback: append to body
        document.body.appendChild(pmDom);
      }
      delete pmDom.__rt_wrappedByPageContainer;
      if (headerObserver) headerObserver.disconnect();
      if (footerObserver) footerObserver.disconnect();
      // clear functions
      if (window.__RT_refreshPageContainerWrapper) delete window.__RT_refreshPageContainerWrapper;
      // remove cleanup reference from editor (tidy)
      if (editor && editor.__rt_pageContainerCleanup) delete editor.__rt_pageContainerCleanup;
    } catch (e) {
      console.warn('[pageContainer] cleanup failed', e);
    }
  };

  // attach cleanup back to the editor instance for developer access
  try { if (editor) editor.__rt_pageContainerCleanup = cleanup; } catch (e) { /* ignore */ }

  // Return cleanup to restore DOM to original state
  return cleanup;
}
