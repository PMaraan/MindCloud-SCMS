// /src/rteditor/modules/virtualPages.js
/**
 * Virtual Page Renderer
 *
 * Pure DOM-only rendering of page sheets and margins.
 * No pagination decisions.
 */

import { attachPageHeaderFooter } from './pageHeaderFooter.js';

let _resizeRAF = null;

function scheduleVirtualPagesRender(editor, pageH_px, marginTop_px, marginBottom_px) {
  if (_resizeRAF) cancelAnimationFrame(_resizeRAF);

  _resizeRAF = requestAnimationFrame(() => {
    renderVirtualPages(editor, pageH_px, marginTop_px, marginBottom_px);
  });
}

export function renderVirtualPages(editor, pageH_px, marginTop_px, marginBottom_px) {
  const pageRoot = document.getElementById('pageRoot');
  if (!pageRoot || !editor?.view?.dom) return;

  // Cache last page layout config for resize reflow
  editor.__rt_lastPageConfig = {
    pageHeightPx: pageH_px,
    marginTopPx: marginTop_px,
    marginBottomPx: marginBottom_px,
    size: editor.__rt_lastPageConfig?.size,
    orientation: editor.__rt_lastPageConfig?.orientation
  };

  // The editor DOM (ProseMirror root)
  const prose = editor.view.dom;
  const editorEl = prose.parentElement; // <-- RESTORED, REQUIRED

  const bgHost = document.getElementById('pageVisualLayer');
  const hfHost = document.getElementById('headerFooterLayer');
  if (!bgHost || !hfHost) return;

  // Clear previous pages
  Array.from(bgHost.children).forEach(n => n.remove());
  Array.from(hfHost.children).forEach(n => n.remove());

  const proseRect = prose.getBoundingClientRect();
  const editorRect = pageRoot.getBoundingClientRect();

  const cs = getComputedStyle(prose);
  const contentHeight =
    prose.scrollHeight
    - parseFloat(cs.paddingTop)
    - parseFloat(cs.paddingBottom);

  const pageCount = Math.max(1, Math.ceil(contentHeight / pageH_px));

  for (let i = 0; i < pageCount; i++) {
    const pageTop = proseRect.top - editorRect.top + (i * pageH_px);

    const page = document.createElement('div');
    page.className = 'rt-page-bg';
    page.style.top = `${Math.round(pageTop)}px`;
    page.style.height = `${Math.round(pageH_px)}px`;

    let pageW_px = 0;

    if (editor.__rt_lastPageConfig?.size) {
      const { wmm, hmm } = editor.__rt_lastPageConfig.size;
      const isLandscape = editor.__rt_lastPageConfig.orientation === 'landscape';
      const w = isLandscape ? hmm : wmm;
      pageW_px = mmToPx(w);
    }

    if (!pageW_px || pageW_px < 200) {
      pageW_px = mmToPx(210);
    }

    page.style.width = `${Math.round(pageW_px)}px`;

    const mt = document.createElement('div');
    mt.className = 'rt-margin-top';
    mt.style.top = `${marginTop_px}px`;

    const mb = document.createElement('div');
    mb.className = 'rt-margin-bottom';
    mb.style.bottom = `${marginBottom_px}px`;

    const ml = document.createElement('div');
    ml.className = 'rt-margin-left';

    const mr = document.createElement('div');
    mr.className = 'rt-margin-right';

    page.appendChild(mt);
    page.appendChild(mb);
    page.appendChild(ml);
    page.appendChild(mr);

    bgHost.appendChild(page);

    // Header/footer rendered into overlay layer
    attachPageHeaderFooter(hfHost, page, i, {
      pageTop,
      pageHeight: pageH_px,
      marginTop: marginTop_px,
      marginBottom: marginBottom_px,
    });
  }

  // Ensure resize listener is attached once
  if (!editor.__rt_resizeBound) {
    editor.__rt_resizeBound = true;

    window.addEventListener('resize', () => {
      if (!editor.__rt_lastPageConfig) return;

      const { pageHeightPx, marginTopPx, marginBottomPx } =
        editor.__rt_lastPageConfig;

      scheduleVirtualPagesRender(
        editor,
        pageHeightPx,
        marginTopPx,
        marginBottomPx
      );
    });
  }
}

function mmToPx(mm) {
  return (parseFloat(mm) || 0) / 25.4 * 96;
}

export function cssLenToPx(val) {
  if (val == null) return 0;
  const s = String(val).trim();
  if (s.endsWith('mm')) return mmToPx(parseFloat(s));
  if (s.endsWith('px')) return parseFloat(s) || 0;
  if (s.endsWith('in')) return (parseFloat(s) || 0) * 96;
  if (s.endsWith('cm')) return (parseFloat(s) || 0) * 10 / 25.4 * 96;
  return parseFloat(s) || 0;
}
