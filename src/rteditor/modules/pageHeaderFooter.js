// /src/rteditor/modules/pageHeaderFooter.js
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
  // Header aligns to the top edge of the page
  // Header: anchored to page top
  header.style.top = `${pageRect.top - hostRect.top}px`;
  header.style.left = `${pageRect.left - hostRect.left}px`;
  header.style.width = `${pageRect.width}px`;
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
  // Align footer to bottom margin area of the SAME page
  // Footer must be positioned using its actual rendered height
  // (CSS vars live on ProseMirror, not pageBg)
  const footerHeight = footer.offsetHeight;
  footer.style.top =
    `${pageRect.bottom - hostRect.top - footerHeight}px`;
  footer.style.left = `${pageRect.left - hostRect.left}px`;
  footer.style.width = `${pageRect.width}px`;

  applyEditHint(footer, 'footer');

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
