// /public/assets/js/rteditor/page-layout.js
// Pure, framework-free page layout utilities for the “Word view”

// Remember the last applied page config so others (preview) can read it
let __lastPageCfg = {
  size: { wmm: 210, hmm: 297 }, // A4 default
  orientation: 'portrait',
  margins: { top: '25.4mm', right: '25.4mm', bottom: '25.4mm', left: '25.4mm' },
};

export function getCurrentPageConfig() {
  return __lastPageCfg;
}

export const PAGE_PRESETS = {
  A4:     { wmm: 210, hmm: 297 },
  Letter: { wmm: 216, hmm: 279 },
  Legal:  { wmm: 216, hmm: 356 },
  A5:     { wmm: 148, hmm: 210 },
};

// helpers
const mmToPx  = (mm) => (mm / 25.4) * 96; // 96dpi
const mmToCSS = (mm) => `${mm}mm`;

// convert CSS lengths (mm/px/in/cm/number) → px
function cssLenToPx(val) {
  if (val == null) return 0;
  const s = String(val).trim();
  if (s.endsWith('mm')) return (parseFloat(s) || 0) / 25.4 * 96;
  if (s.endsWith('cm')) return (parseFloat(s) || 0) * 10 / 25.4 * 96;
  if (s.endsWith('in')) return (parseFloat(s) || 0) * 96;
  if (s.endsWith('px')) return (parseFloat(s) || 0);
  return parseFloat(s) || 0; // bare number treated as px
}

function ensureStyleTag() {
  let tag = document.getElementById('rt-page-style');
  if (!tag) {
    tag = document.createElement('style');
    tag.id = 'rt-page-style';
    document.head.appendChild(tag);
  }
  return tag;
}

/**
 * Apply page size/orientation/margins to DOM & print (@page).
 */
export function applyPageLayout(pageEl, contentEl, opts) {
  if (!pageEl || !contentEl) return;

  // normalize size key (handles "legal" / "letter" / "a4")
  const sizeKeyRaw = (opts?.size ?? 'A4');
  const sizeKey = String(sizeKeyRaw).trim();
  const preset =
    PAGE_PRESETS[sizeKey] ||
    PAGE_PRESETS[sizeKey.toUpperCase()] ||
    PAGE_PRESETS.A4;

  const isLandscape = opts.orientation === 'landscape';
  const wmm = isLandscape ? preset.hmm : preset.wmm;
  const hmm = isLandscape ? preset.wmm : preset.hmm;

  // 1) Size the page box (px from mm)
  pageEl.style.width  = `${mmToPx(wmm)}px`;
  pageEl.style.height = `${mmToPx(hmm)}px`;

  // 2) Header/Footer thickness = margins (top/bottom)
  const m = opts.margins;
  const headerEl = pageEl.querySelector('.rt-header');
  const footerEl = pageEl.querySelector('.rt-footer');

  if (headerEl) {
    headerEl.style.minHeight = m.top;      // e.g. "25.4mm"
    headerEl.style.height    = m.top;      // fix visual thickness
  }
  if (footerEl) {
    footerEl.style.minHeight = m.bottom;
    footerEl.style.height    = m.bottom;
  }

  // 3) Content padding: we let header/footer *own* the top/bottom margin area.
  // So top/bottom padding must be 0 to avoid double-subtraction.
  contentEl.style.paddingTop    = '0';
  contentEl.style.paddingBottom = '0';
  // Keep left/right as margins on the content box so text is inset from page edge.
  contentEl.style.paddingLeft   = m.left;
  contentEl.style.paddingRight  = m.right;

  // 4) Real print @page still uses full margins (so print matches Word)
  const styleTag = ensureStyleTag();
  styleTag.textContent =
  `@page {
    size: ${mmToCSS(wmm)} ${mmToCSS(hmm)};
    margin: ${m.top} ${m.right} ${m.bottom} ${m.left};
  }`;

  // ---- keep the live config in sync and broadcast ----
  __lastPageCfg = {
    size: { wmm, hmm },
    orientation: opts.orientation,
    margins: { ...opts.margins },
  };

  document.dispatchEvent(new CustomEvent('rt:page-layout-updated', {
    detail: { cfg: __lastPageCfg }
  }));
}

// tiny debounce so typing in margin fields won’t thrash
function debounce(fn, ms = 50) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

/**
 * Bind live controls (auto-apply on change/input). No Apply button needed.
 */
export function bindPageLayoutControls(root, pageEl, contentEl) {
  if (!pageEl || !contentEl) {
    console.warn('[page-layout] Missing #rtPage or #rtPageContent in DOM.');
    return;
  }

  const sizeSel   = root.querySelector('[data-page-size]');
  const orientSel = root.querySelector('[data-page-orientation]');
  const mTop      = root.querySelector('[data-page-margin-top]');
  const mRight    = root.querySelector('[data-page-margin-right]');
  const mBottom   = root.querySelector('[data-page-margin-bottom]');
  const mLeft     = root.querySelector('[data-page-margin-left]');

  const getCfg = () => {
    const sizeVal = sizeSel?.value || 'A4';
    const key = String(sizeVal).trim();
    const size =
      PAGE_PRESETS[key] ? key :
      PAGE_PRESETS[key.toUpperCase()] ? key.toUpperCase() :
      'A4';

    return {
      size,
      orientation: orientSel?.value || 'portrait',
      margins: {
        // match your 1" default (25.4mm) consistently
        top:    mTop?.value    || '25.4mm',
        right:  mRight?.value  || '25.4mm',
        bottom: mBottom?.value || '25.4mm',
        left:   mLeft?.value   || '25.4mm',
      },
    };
  };

  const apply = debounce(() => applyPageLayout(pageEl, contentEl, getCfg()), 50);

  // initial apply
  apply();

  // live updates
  sizeSel   && sizeSel.addEventListener('change', apply);
  orientSel && orientSel.addEventListener('change', apply);
  [mTop, mRight, mBottom, mLeft].forEach(inp => {
    if (!inp) return;
    inp.addEventListener('input', apply);  // apply while typing
    inp.addEventListener('change', apply); // safety
  });
}
