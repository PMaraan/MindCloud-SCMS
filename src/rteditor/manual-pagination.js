// /src/rteditor/manual-pagination.js
// Manual Pagination (Preview): build page DIVs split by <div data-page-break...>

function splitByPageBreak(html) {
  // Split on our PageBreak node: <div data-page-break ...></div>
  // Be tolerant of attributes/whitespace and self-closing forms
  const re = /<div[^>]*data-page-break[^>]*>\s*<\/div>/i;
  const parts = html.split(re);
  return parts;
}

// TOP: helpers
const mmToPx = (mm) => (parseFloat(mm) || 0) / 25.4 * 96;
const cssLenToPx = (v) => {
  if (!v) return 0;
  const s = String(v).trim();
  if (s.endsWith('mm')) return (parseFloat(s) || 0) / 25.4 * 96;
  if (s.endsWith('cm')) return (parseFloat(s) || 0) * 10 / 25.4 * 96;
  if (s.endsWith('in')) return (parseFloat(s) || 0) * 96;
  if (s.endsWith('px')) return (parseFloat(s) || 0);
  return parseFloat(s) || 0;
};

// NEW: apply page metrics (size + header/footer thickness + content padding)
function applyPreviewPageMetrics(pageEl, cfg) {
  const isLandscape = cfg.orientation === 'landscape';
  const wmm = isLandscape ? (cfg.size.hmm || cfg.size.h) : (cfg.size.wmm || cfg.size.w);
  const hmm = isLandscape ? (cfg.size.wmm || cfg.size.w) : (cfg.size.hmm || cfg.size.h);

  pageEl.style.width  = `${mmToPx(wmm)}px`;
  pageEl.style.height = `${mmToPx(hmm)}px`;

  const headerEl = pageEl.querySelector('.rt-header');
  const footerEl = pageEl.querySelector('.rt-footer');
  const contentEl = pageEl.querySelector('.rt-page-content');

  if (headerEl) {
    headerEl.style.height    = cfg.margins.top;
    headerEl.style.minHeight = cfg.margins.top;
  }
  if (footerEl) {
    footerEl.style.height    = cfg.margins.bottom;
    footerEl.style.minHeight = cfg.margins.bottom;
  }

  if (contentEl) {
    // top/bottom zero (header/footer own that space), left/right = margins
    contentEl.style.paddingTop    = '0';
    contentEl.style.paddingBottom = '0';
    contentEl.style.paddingLeft   = cfg.margins.left;
    contentEl.style.paddingRight  = cfg.margins.right;
  }
}

export function renderManualPages({
  html,
  pageRoot,
  headerHTML = 'Header…',
  footerHTML = 'Footer…',
  size = { wmm: 210, hmm: 297 }, // A4 default
  orientation = 'portrait',
  margins = { top: '25.4mm', right: '25.4mm', bottom: '25.4mm', left: '25.4mm' },
}) {
  if (!pageRoot) return;

  // Compute page size in px for screen
  const isLandscape = orientation === 'landscape';
  const wmm = isLandscape ? size.hmm : size.wmm;
  const hmm = isLandscape ? size.wmm : size.hmm;

  // Clear old preview
  pageRoot.innerHTML = '';

  // Split into segments
  const segments = splitByPageBreak(html);

  segments.forEach((seg, idx) => {
    const page = document.createElement('div');
    page.className = 'rt-page';

    const head = document.createElement('div');
    head.className = 'rt-header';
    head.innerHTML = headerHTML;

    const cont = document.createElement('div');
    cont.className = 'rt-page-content';
    // Preview: header/footer use top/bottom; content only gets left/right
    cont.style.paddingTop    = '0';
    cont.style.paddingBottom = '0';
    cont.style.paddingLeft   = margins.left;
    cont.style.paddingRight  = margins.right;

    const body = document.createElement('div');
    body.className = 'rt-preview-body';
    body.innerHTML = seg; // read-only HTML

    cont.appendChild(body);

    const foot = document.createElement('div');
    foot.className = 'rt-footer';
    foot.innerHTML = footerHTML;

    // build head/cont/body/foot, append, add tag (your existing code)
    page.append(head, cont, foot);
    pageRoot.append(page);

    const tag = document.createElement('div');
    tag.className = 'rt-page-tag';
    tag.textContent = `Page ${idx + 1}`;
    page.appendChild(tag);

    // ✅ NEW: apply the same metrics logic the live page uses
    applyPreviewPageMetrics(page, {
      size, orientation, margins
    });
  });
}

/**
 * Bind manual pagination preview to a TipTap editor.
 * - Renders on init and on every editor update.
 */
export function bindManualPagination(editor, {
  pagePreviewRoot,
  headerEl,
  footerEl,
  getPageConfig, // ()=> { size, orientation, margins }
}) {
  if (!editor || !pagePreviewRoot) return;

  const doRender = () => {
    const html = editor.getHTML();
    const headerHTML = headerEl ? headerEl.innerHTML : 'Header…';
    const footerHTML = footerEl ? footerEl.innerHTML : 'Footer…';
    const cfg = (typeof getPageConfig === 'function') ? getPageConfig() : {};
    renderManualPages({
      html,
      pageRoot: pagePreviewRoot,
      headerHTML,
      footerHTML,
      size: cfg.size || { wmm: 210, hmm: 297 }, // A4
      orientation: cfg.orientation || 'portrait',
      margins: cfg.margins || { top: '25.4mm', right: '25.4mm', bottom: '25.4mm', left: '25.4mm' },
    });
  };

  // initial + on editor changes
  doRender();
  editor.on('update', doRender);
  if (headerEl) headerEl.addEventListener('input', doRender);
  if (footerEl) footerEl.addEventListener('input', doRender);

  // ✅ NEW: re-render when page layout (size/orientation/margins) changes
  const onLayout = () => doRender();
  document.addEventListener('rt:page-layout-updated', onLayout);

  return {
    refresh: doRender,
    // optional cleanup if you ever need it:
    destroy() { document.removeEventListener('rt:page-layout-updated', onLayout); }
  };
}
