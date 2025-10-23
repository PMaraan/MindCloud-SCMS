// /public/assets/js/rteditor/auto-pagination.js
// Auto-pagination for TipTap content using manual PageBreak nodes.
// Safe to drop in. No other files need edits for this to work.

function beginMeasure(pageEl, contentEl) {
  const targets = [];
  if (pageEl)    targets.push(pageEl);
  if (contentEl) targets.push(contentEl);

  const prev = targets.map(t => t.style.overflow);
  targets.forEach(t => { t.style.overflow = 'visible'; });
  return () => {
    targets.forEach((t, i) => { t.style.overflow = prev[i]; });
  };
}

// ---------- tiny utils ----------
function num(v) { return parseFloat(v) || 0; }
function getMargins(el) {
  const cs = getComputedStyle(el);
  return { mt: num(cs.marginTop), mb: num(cs.marginBottom) };
}
function px(v) { return Math.max(0, v|0); }
function outerHeightWithMargins(el) {
  const r = el.getBoundingClientRect();
  const cs = getComputedStyle(el);
  return r.height + (parseFloat(cs.marginTop) || 0) + (parseFloat(cs.marginBottom) || 0);
}
function mmToPx(mm) { return (parseFloat(mm) || 0) / 25.4 * 96; }
function cssLenToPx(val) {
  if (val == null) return 0;
  const s = String(val).trim();
  if (s.endsWith('mm')) return mmToPx(parseFloat(s));
  if (s.endsWith('px')) return parseFloat(s) || 0;
  if (s.endsWith('in')) return (parseFloat(s) || 0) * 96;
  if (s.endsWith('cm')) return (parseFloat(s) || 0) * 10 / 25.4 * 96;
  return parseFloat(s) || 0;
}

// ---------- usable height calculators ----------
/**
 * Best source of truth: the *inner* text area inside the content box.
 * Uses clientHeight (which includes padding) minus paddings.
 */
// NEW: get actual usable height of the content area (padding removed)
function getUsableContentHeight(contentEl) {
  const cs = getComputedStyle(contentEl);
  const pt = parseFloat(cs.paddingTop)    || 0;
  const pb = parseFloat(cs.paddingBottom) || 0;
  return contentEl.clientHeight - pt - pb;
}

/**
 * Secondary sanity calculator (pieces): page - header - footer - paddings.
 * Useful to clamp against layout drift.
 */
function getUsableContentHeightFromPieces({ pageEl, headerEl, footerEl, contentEl }) {
  const pageH   = pageEl.getBoundingClientRect().height;
  const headerH = headerEl ? headerEl.getBoundingClientRect().height : 0;
  const footerH = footerEl ? footerEl.getBoundingClientRect().height : 0;
  const cs = getComputedStyle(contentEl);
  const pt = num(cs.paddingTop);
  const pb = num(cs.paddingBottom);
  return pageH - headerH - footerH - pt - pb;
}

// ---------- low-noise debug (won't keep huge object refs in console) ----------
const DEBUG_FLAG = !!window.__RT_debugAutoPaginate; // set to true in console when needed
function debugSnapshot({ pageEl, headerEl, footerEl, contentEl, blocks }) {
  if (!DEBUG_FLAG) return;

  const byContentBox = getUsableContentHeight(contentEl);
  const byPieces     = getUsableContentHeightFromPieces({ pageEl, headerEl, footerEl, contentEl });

  // Only log primitive values + a slim copy of first 5 blocks to avoid console retaining DOM refs
  const head = blocks.slice(0, 5).map(b => ({
    pos: b.pos,
    tag: b.tag,
    h: Math.round(b.h)
  }));

  console.log('[auto-pagination] budgets',
    { usableH_contentBox: Math.round(byContentBox), usableH_pieces: Math.round(byPieces) });
  console.log('[auto-pagination] blocks', { count: blocks.length, first5: head });
}

function posBeforeDomBlock(editor, el) {
  const view = editor?.view;
  if (!view || !el) return null;

  // Map DOM → a position at the *start* of this DOM node
  let pos = null;
  try { pos = view.posAtDOM(el, 0); } catch { return null; }
  if (typeof pos !== 'number') return null;

  // Resolve and get a position *before* the block node
  const { state } = view;
  const $pos = state.doc.resolve(pos);

  // Walk up until we find a node that actually corresponds to this DOM element boundary
  // Then take the position before that node.
  for (let d = $pos.depth; d >= 0; d--) {
    const before = $pos.before(d + 1); // pos before the node at depth d+1
    if (Number.isFinite(before) && before >= 0) return before;
  }
  return pos;
}

// ---------- block collection (DOM-measured heights + PM positions) ----------
/**
 * We measure real DOM block boxes under .ProseMirror so we include margins.
 * We also find a safe position *before* each block to insert a PageBreak.
 */
function collectBlockHeights(editor, contentEl) {
  const view = editor?.view;
  const pm = contentEl?.querySelector('.ProseMirror');
  if (!view || !pm) return [];

  const blocks = [];
  // Only top-level block children: good enough for pagination without over-measuring
  const kids = Array.from(pm.children);
  for (const el of kids) {
    // Skip existing pageBreak markers so we don't try to break *before* a break again
    if (el.hasAttribute('data-page-break')) continue;

    const rect = el.getBoundingClientRect();
    if (!rect || rect.height <= 0) continue;

    // Height including top/bottom margins
    const h = outerHeightWithMargins(el);

    // Position before this DOM node
    const pos = posBeforeDomBlock(editor, el);
    if (pos == null) continue;

    blocks.push({
      el,
      tag: el.tagName?.toLowerCase() || 'div',
      h,
      pos,
    });
  }

  return blocks;
}

// ---------- break insertion / removal ----------
function insertBreaksAt(editor, positions) {
  if (!positions || !positions.length) return;
  // Insert from end to start so earlier positions don't shift
  const sorted = positions.slice().sort((a, b) => b - a);
  const chain = editor.chain().focus();
  for (const pos of sorted) {
    chain.insertContentAt(pos, { type: 'pageBreak' }, { updateSelection: false });
  }
  chain.run();
}

function removeAllPageBreaks(editor) {
  const { state } = editor;
  const breaks = [];
  state.doc.nodesBetween(0, state.doc.content.size, (node, pos) => {
    if (node.type.name === 'pageBreak') breaks.push({ pos, nodeSize: node.nodeSize });
  });
  if (!breaks.length) return;

  const tr = state.tr;
  // Delete from end to start
  for (let i = breaks.length - 1; i >= 0; i--) {
    const b = breaks[i];
    tr.delete(b.pos, b.pos + b.nodeSize);
  }
  editor.view.dispatch(tr);
}

// ---------- main entry (DROP-IN) ----------
/**
 * Insert pageBreak nodes so that each "page" consumes up to usableH
 * measured from the current DOM (A4/Letter + margins).
 */
export function runOnce(editor, {
  pageEl,
  contentEl,
  headerEl,
  footerEl,
  getPageConfig,          // <- we’ll use this now
  clearExisting = true,
  safety = 6,
} = {}) {
  if (!editor || !pageEl || !contentEl) return;

  // --- compute usable height from CONFIG (source of truth) ---
  const cfg = (typeof getPageConfig === 'function') ? getPageConfig() : null;
  let usableH_cfg = 0;
  try {
    if (cfg && cfg.size) {
      const isLandscape = cfg.orientation === 'landscape';
      const pageH_mm = isLandscape ? (cfg.size.wmm || cfg.size.w) : (cfg.size.hmm || cfg.size.h);
      const pageH_px = mmToPx(pageH_mm);
      const headerH  = headerEl ? headerEl.getBoundingClientRect().height : 0;
      const footerH  = footerEl ? footerEl.getBoundingClientRect().height : 0;

      // padding on the content box is set from the margin controls (e.g., "25mm")
      const cs = getComputedStyle(contentEl);
      const pt = cssLenToPx(cs.paddingTop);
      const pb = cssLenToPx(cs.paddingBottom);

      usableH_cfg = pageH_px - headerH - footerH - pt - pb;
    }
  } catch { /* ignore; fallback below */ }

  // --- compute usable height from the DOM box (clientHeight minus padding) ---
  const cs2 = getComputedStyle(contentEl);
  const pt2 = parseFloat(cs2.paddingTop) || 0;
  const pb2 = parseFloat(cs2.paddingBottom) || 0;
  const usableH_dom = contentEl.clientHeight - pt2 - pb2;

  // take the safer, smaller budget and subtract a tiny safety cushion
  const usableH = Math.max(0, Math.min(usableH_cfg || Infinity, usableH_dom || Infinity) - safety);

  // unconditional sanity log so you SEE it
  console.log('[auto-pagination] usableH(cfg/dom)=', {
    fromConfig: Math.round(usableH_cfg),
    fromDom:    Math.round(usableH_dom),
    chosen:     Math.round(usableH),
    safety
  });

  // temporarily unclip while we measure block heights
  const endMeasure = beginMeasure(pageEl, contentEl);
  try {
    const blocks = collectBlockHeights(editor, contentEl);
    debugSnapshot({ pageEl, headerEl, footerEl, contentEl, blocks });

    // greedy pack
    const breakPositions = [];
    let acc = 0;
    for (const b of blocks) {
      if (acc + b.h > usableH) {
        if (!breakPositions.length || breakPositions[breakPositions.length - 1] !== b.pos) {
          breakPositions.push(b.pos);
        }
        acc = b.h;
      } else {
        acc += b.h;
      }
    }

    if (clearExisting) removeAllPageBreaks(editor);
    insertBreaksAt(editor, breakPositions);

    console.log('[auto-pagination] inserted breaks:', breakPositions.length);
  } finally {
    endMeasure();
  }
}
