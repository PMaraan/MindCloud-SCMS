// /public/assets/js/rteditor/manual-pagination.js
// Manual Pagination (Preview): build page DIVs split by <div data-page-break...>

function splitByPageBreak(html) {
  // Split on our PageBreak node: <div data-page-break ...></div>
  // Be tolerant of attributes/whitespace and self-closing forms
  const re = /<div[^>]*data-page-break[^>]*>\s*<\/div>/i;
  const parts = html.split(re);
  return parts;
}

function mmToPx(mm) { return (mm / 25.4) * 96; }

export function renderManualPages({
  html,
  pageRoot,
  headerHTML = 'Header…',
  footerHTML = 'Footer…',
  size = { wmm: 210, hmm: 297 }, // A4 default
  orientation = 'portrait',
  margins = { top: '25mm', right: '25mm', bottom: '25mm', left: '25mm' },
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
    page.style.width  = `${mmToPx(wmm)}px`;
    page.style.minHeight = `${mmToPx(hmm)}px`;

    const head = document.createElement('div');
    head.className = 'rt-header';
    head.innerHTML = headerHTML;

    const cont = document.createElement('div');
    cont.className = 'rt-page-content';
    // Apply margins as padding to content area
    cont.style.paddingTop    = margins.top;
    cont.style.paddingRight  = margins.right;
    cont.style.paddingBottom = margins.bottom;
    cont.style.paddingLeft   = margins.left;

    const body = document.createElement('div');
    body.className = 'rt-preview-body';
    body.innerHTML = seg; // read-only HTML

    cont.appendChild(body);

    const foot = document.createElement('div');
    foot.className = 'rt-footer';
    foot.innerHTML = footerHTML;

    page.append(head, cont, foot);
    pageRoot.append(page);

    // Visual label (optional)
    const tag = document.createElement('div');
    tag.className = 'rt-page-tag';
    tag.textContent = `Page ${idx + 1}`;
    page.appendChild(tag);
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
      margins: cfg.margins || { top: '25mm', right: '25mm', bottom: '25mm', left: '25mm' },
    });
  };

  // Initial render
  doRender();

  // Re-render on every transaction that changes the doc
  editor.on('update', doRender);

  // If your header/footer are editable, re-render on blur/input too
  if (headerEl) headerEl.addEventListener('input', doRender);
  if (footerEl) footerEl.addEventListener('input', doRender);

  return { refresh: doRender };
}
