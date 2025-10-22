// /public/assets/js/rteditor/page-layout.js
// Pure, framework-free page layout utilities for the “Word view”

// Remember the last applied page config so others (preview) can read it
let __lastPageCfg = {
  size: { wmm: 210, hmm: 297 }, // A4 default
  orientation: 'portrait',
  margins: { top: '25mm', right: '25mm', bottom: '25mm', left: '25mm' },
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

  const preset = PAGE_PRESETS[opts.size] || PAGE_PRESETS.A4;
  const isLandscape = opts.orientation === 'landscape';
  const wmm = isLandscape ? preset.hmm : preset.wmm;
  const hmm = isLandscape ? preset.wmm : preset.hmm;

  // On-screen size
  pageEl.style.width  = `${mmToPx(wmm)}px`;
  pageEl.style.height = `${mmToPx(hmm)}px`;

  // Content padding from margins (accepts mm/pt/in/px)
  const m = opts.margins;
  contentEl.style.paddingTop    = m.top;
  contentEl.style.paddingRight  = m.right;
  contentEl.style.paddingBottom = m.bottom;
  contentEl.style.paddingLeft   = m.left;

  // Real print @page
  const styleTag = ensureStyleTag();
  styleTag.textContent =
`@page {
  size: ${mmToCSS(wmm)} ${mmToCSS(hmm)};
  margin: ${m.top} ${m.right} ${m.bottom} ${m.left};
}`;

  // <-- UPDATE the shared config here (where preset & opts exist)
  __lastPageCfg = {
    size: preset,
    orientation: opts.orientation,
    margins: { ...opts.margins },
  };

  // Fire a custom event so the preview can refresh instantly
  document.dispatchEvent(new CustomEvent('rt:page-layout-updated', { detail: __lastPageCfg }));
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

  const getCfg = () => ({
    size:        (sizeSel?.value || 'A4'),
    orientation: (orientSel?.value || 'portrait'),
    margins: {
      top:    (mTop?.value || '25mm'),
      right:  (mRight?.value || '25mm'),
      bottom: (mBottom?.value || '25mm'),
      left:   (mLeft?.value || '25mm'),
    },
  });

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
