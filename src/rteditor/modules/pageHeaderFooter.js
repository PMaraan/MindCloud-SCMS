/* /src/rteditor/modules/pageHeaderFooter.js */
/**
 * Module: pageHeaderFooter
 * ------------------------------------------------------------
 * Responsibility:
 * - Attaches editable visual page headers and footers
 * - Maintains per-page header/footer content during pagination
 * - Keeps caret behavior stable across focus/blur
 * - Provides UX affordances for discoverability (hover + dblclick hint)
 *
 * This module does NOT:
 * - Persist data to DB
 * - Decide section/global inheritance
 * - Perform pagination or layout math
 *
 * It strictly operates at the page DOM lifecycle layer.
 * 
 * NOTE ON POSITIONING
 * ------------------------------------------------------------
 * Headers and footers are positioned using page background
 * bounding rects and snapped to CSS margin variables
 * (--rt-margin-top / --rt-margin-bottom).
 *
 * This keeps visual alignment consistent with:
 * - Page background
 * - Margin guides
 * - ProseMirror padding
 *
 * No ProseMirror or Yjs state is affected.
 */

/**
 * In-memory cache for header/footer HTML per page.
 * key format: `${pageIndex}:header` | `${pageIndex}:footer`
 */
const headerFooterCache = new Map();

/**
 * Removes placeholder text once the user starts editing.
 */
function stripPlaceholder(el) {
  const ph = el.querySelector('.rt-hf-placeholder');
  if (ph) ph.remove();
}

/**
 * Restores placeholder if the editable element has no meaningful content.
 */
function restorePlaceholderIfEmpty(el, label) {
  const text = el.textContent?.trim() ?? '';

  if (text !== '') return;

  // Avoid duplicate placeholders
  if (el.querySelector('.rt-hf-placeholder')) return;

  el.innerHTML = `<span class="rt-hf-placeholder">${label}</span>`;
}

/**
 * Ensures caret is placed inside the editable element.
 * Prevents focus jumps when clicking headers/footers.
 */
function ensureCaretInside(el) {
  const sel = window.getSelection();
  if (!sel) return;

  if (sel.rangeCount > 0) {
    const range = sel.getRangeAt(0);
    if (el.contains(range.startContainer)) return;
  }

  const range = document.createRange();
  range.selectNodeContents(el);
  range.collapse(false);
  sel.removeAllRanges();
  sel.addRange(range);
}

/**
 * Adds hover + dblclick hint for discoverability.
 * Uses title attribute to avoid extra DOM clutter.
 */
function applyEditHint(el, label) {
  el.title = `Double-click to edit ${label}`;
  el.addEventListener('dblclick', e => {
    e.stopPropagation();
    el.focus();
  });
}

function positionOverlayHorizontally(el, hostRect, pageRect) {
  el.style.left =
    `${pageRect.left - hostRect.left}px`;
  el.style.width =
    `${pageRect.width}px`;
}

function getContentRect(pageRect, margins) {
  const left   = pageRect.left + margins.left;
  const right  = pageRect.right - margins.right;
  const top    = pageRect.top + margins.top;
  const bottom = pageRect.bottom - margins.bottom;

  return {
    left,
    top,
    width: right - left,
    right,
    bottom,
  };
}

function getLiveMarginsPx() {
  const root = document.querySelector('.ProseMirror');
  const cs = getComputedStyle(root);

  return {
    top:    parseFloat(cs.getPropertyValue('--rt-margin-top'))    || 0,
    bottom: parseFloat(cs.getPropertyValue('--rt-margin-bottom')) || 0,
    left:   parseFloat(cs.getPropertyValue('--rt-margin-left'))   || 0,
    right:  parseFloat(cs.getPropertyValue('--rt-margin-right'))  || 0,
  };
}

/**
 * Public entry point.
 * Attaches both header and footer to a page element.
 */
export function attachPageHeaderFooter(
  overlayHost,
  pageBgEl,
  pageIndex,
  { enabled = true } = {}
) {
  if (!enabled || !overlayHost || !pageBgEl) return;

  attachHeader(overlayHost, pageBgEl, pageIndex);
  attachFooter(overlayHost, pageBgEl, pageIndex);
}

/* ============================================================
   Header
   ============================================================ */

/**
 * Attaches and wires up an editable page header.
 */
function attachHeader(host, pageBg, pageIndex) {
  const pageRect = pageBg.getBoundingClientRect();
  const hostRect = host.getBoundingClientRect();
  const margins = getLiveMarginsPx();
  const contentRect = getContentRect(pageRect, margins);

  if (!pageRect.height) return;

  let header = host.querySelector(
    `.rt-page-header[data-page-index="${pageIndex}"]`
  );

  if (!header) {
    header = document.createElement('div');
    header.className = 'rt-page-header';
    header.contentEditable = 'true';
    header.dataset.page = pageIndex + 1;
    header.innerHTML =
      `<span class="rt-hf-placeholder">Header — Page ${pageIndex + 1}</span>`;
    host.appendChild(header);
  }

  header.dataset.role = 'header';
  header.dataset.pageIndex = pageIndex;
  header.tabIndex = 0;
  header.style.pointerEvents = 'auto';

  header.style.position = 'absolute';

  // const top  = pageRect.top  - hostRect.top;
  // const left = pageRect.left - hostRect.left;
  // const width = pageRect.width;

  header.style.top =
    `${pageRect.top - hostRect.top}px`;

  header.style.left =
    `${contentRect.left - hostRect.left}px`;

  header.style.width =
    `${contentRect.width}px`;

  header.style.height =
    `${margins.top}px`;
  header.style.pointerEvents = 'auto';

  host.appendChild(header);

  applyEditHint(header, 'header');

  const cacheKey = `${pageIndex}:header`;
  if (headerFooterCache.has(cacheKey)) {
    header.innerHTML = headerFooterCache.get(cacheKey);
  }

  header.addEventListener('input', () => {
    const hasText = header.textContent.trim() !== '';

    if (hasText) {
      headerFooterCache.set(cacheKey, header.innerHTML);
    } else {
      headerFooterCache.delete(cacheKey);
    }
  });

  header.addEventListener('click', e => e.stopPropagation());
  header.addEventListener('keydown', e => e.stopPropagation());

  header.addEventListener('focus', () => {
    stripPlaceholder(header);
    ensureCaretInside(header);
    document.body.classList.add('rt-editing-header');
  });

  header.addEventListener('blur', () => {
    restorePlaceholderIfEmpty(
      header,
      `Header — Page ${pageIndex + 1}`
    );
    document.body.classList.remove('rt-editing-header');
  });
}

/* ============================================================
   Footer
   ============================================================ */

/**
 * Attaches and wires up an editable page footer.
 */
function attachFooter(host, pageBg, pageIndex) {
  const pageRect = pageBg.getBoundingClientRect();
  const hostRect = host.getBoundingClientRect();
  const margins = getLiveMarginsPx();
  const contentRect = getContentRect(pageRect, margins);

  if (!pageRect.height) return;

  let footer = host.querySelector(
    `.rt-page-footer[data-page-index="${pageIndex}"]`
  );

  if (!footer) {
    footer = document.createElement('div');
    footer.className = 'rt-page-footer';
    footer.contentEditable = 'true';
    footer.dataset.page = pageIndex + 1;
    footer.dataset.pageIndex = pageIndex;
    footer.innerHTML =
      `<span class="rt-hf-placeholder">Footer — Page ${pageIndex + 1}</span>`;
    host.appendChild(footer);
  }

  footer.dataset.role = 'footer';
  footer.tabIndex = 0;
  footer.style.pointerEvents = 'auto';

  footer.style.position = 'absolute';

  /* Snap footer to bottom of page */
  const footerHeight = footer.offsetHeight;

  // const top  = pageRect.bottom - hostRect.top - footerHeight;
  // const left = pageRect.left   - hostRect.left;
  // const width = pageRect.width;

  // footer.style.top = `${top}px`;
  // footer.style.left = `${left}px`;
  // footer.style.width = `${width}px`;

  footer.style.top =
    `${pageRect.bottom - margins.bottom - hostRect.top}px`;

  footer.style.left =
    `${contentRect.left - hostRect.left}px`;

  footer.style.width =
    `${contentRect.width}px`;

  footer.style.height =
    `${margins.bottom}px`;

  applyEditHint(footer, 'footer');

  // positionOverlayHorizontally(footer, hostRect, pageRect);

  const cacheKey = `${pageIndex}:footer`;
  if (headerFooterCache.has(cacheKey)) {
    footer.innerHTML = headerFooterCache.get(cacheKey);
  }

  footer.addEventListener('input', () => {
    const hasText = footer.textContent.trim() !== '';
    if (hasText) {
      headerFooterCache.set(cacheKey, footer.innerHTML);
    } else {
      headerFooterCache.delete(cacheKey);
    }
  });

  footer.addEventListener('click', e => e.stopPropagation());
  footer.addEventListener('keydown', e => e.stopPropagation());

  footer.addEventListener('focus', () => {
    stripPlaceholder(footer);
    ensureCaretInside(footer);
    document.body.classList.add('rt-editing-footer');
  });

  footer.addEventListener('blur', () => {
    restorePlaceholderIfEmpty(
      footer,
      `Footer — Page ${pageIndex + 1}`
    );
    document.body.classList.remove('rt-editing-footer');
  });
}
/* End of /src/rteditor/modules/pageHeaderFooter.js */
