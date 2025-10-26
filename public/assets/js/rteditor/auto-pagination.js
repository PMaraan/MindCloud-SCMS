// /public/assets/js/rteditor/auto-pagination.js
// Auto-pagination for TipTap content using manual PageBreak nodes.
// Safe to drop in. No other files need edits for this to work.

function beginMeasure(pageEl, contentEl) {
  const prev = [];
  if (pageEl)    { prev.push([pageEl,    pageEl.style.overflow]);    pageEl.style.overflow    = 'visible'; }
  if (contentEl) { prev.push([contentEl, contentEl.style.overflow]); contentEl.style.overflow = 'visible'; }
  return () => { for (const [el, v] of prev) el.style.overflow = v; };
}

function pmRoot(contentEl) {
  return contentEl?.querySelector('.ProseMirror') || null;
}

/** Return an array of top-level block DOM nodes under .ProseMirror */
function topLevelBlocks(contentEl) {
  const pm = pmRoot(contentEl);
  return pm ? Array.from(pm.children) : [];
}

/** Effective top/bottom including vertical margins (no collapsing guesswork) */
function effectiveTop(el) {
  const r = el.getBoundingClientRect();
  const { mt } = getMargins(el);
  return r.top - mt;
}
function effectiveBottom(el) {
  const r = el.getBoundingClientRect();
  const { mb } = getMargins(el);
  return r.bottom + mb;
}

/** Find a PM position at the *start* of this DOM node (safe insertion spot) */
function posAtBlockStart(editor, el) {
  try {
    const pos = editor.view.posAtDOM(el, 0);
    return (typeof pos === 'number') ? pos : null;
  } catch { return null; }
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
function isEmptyParagraph(node) {
  return node && node.type && node.type.name === 'paragraph' && node.content.size === 0;
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
  try {
    // For top-level blocks in the ProseMirror DOM, this already returns the
    // position *at the start of the node*. That’s the safest insertion spot.
    const pos = view.posAtDOM(el, 0);
    return (typeof pos === 'number') ? pos : null;
  } catch {
    return null;
  }
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
  const { state, view } = editor;
  const toDelete = [];

  state.doc.nodesBetween(0, state.doc.content.size, (node, pos, parent, index) => {
    if (node.type.name !== 'pageBreak') return;

    // plan to delete the break itself
    let from = pos;
    let to   = pos + node.nodeSize;

    // also delete a single empty paragraph *immediately before* the break
    const $pos = state.doc.resolve(pos);
    const parentNode = $pos.parent;
    const idx = $pos.index();

    const before = parentNode.child(idx - 1);
    if (isEmptyParagraph(before)) {
      // compute the document positions for that paragraph
      let cursor = $pos.start() + 0;
      for (let i = 0; i < idx - 1; i++) cursor += parentNode.child(i).nodeSize;
      const paraFrom = cursor;
      const paraTo   = cursor + before.nodeSize;
      from = Math.min(from, paraFrom);
    }

    // also delete a single empty paragraph *immediately after* the break
    const after = parentNode.child(idx + 1);
    if (isEmptyParagraph(after)) {
      let cursor = $pos.start() + 0;
      for (let i = 0; i <= idx + 1; i++) cursor += parentNode.child(i).nodeSize;
      const paraTo = cursor;
      to = Math.max(to, paraTo);
    }

    toDelete.push({ from, to });
  });

  if (!toDelete.length) return;

  const tr = state.tr;
  // delete from end to start
  toDelete.sort((a, b) => b.from - a.from).forEach(({ from, to }) => tr.delete(from, to));
  view.dispatch(tr);
}

// Enable detailed block trace by running in console:
//   window.__RT_dumpBlocks = true;  // then click the wand
if (typeof window.__RT_dumpBlocks === 'undefined') {
  window.__RT_dumpBlocks = true;
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
  safety = 14,            // ↑ default safety raised for slight early bias
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
  // take the safer, smaller budget and subtract a tiny safety cushion
  let usableH = Math.max(0, Math.min(usableH_cfg ?? Infinity, usableH_dom ?? Infinity) - safety);

  // EXTRA: shave ~0.6 line to counter rounding/overspill on tall pages (e.g., Legal)
  const probe = contentEl.querySelector('.ProseMirror p') || contentEl.querySelector('.ProseMirror');
  if (probe) {
    const lh = parseFloat(getComputedStyle(probe).lineHeight) || 0;
    const dynamicBias = Math.round(lh * 0.6); // ~60% of a line; adjust 0.5–0.7 if needed
    usableH = Math.max(0, usableH - dynamicBias);
  }

  // unconditional sanity log so you SEE it
  console.log('[auto-pagination] usableH(cfg/dom)=', {
    fromConfig: Math.round(usableH_cfg),
    fromDom:    Math.round(usableH_dom),
    chosen:     Math.round(usableH),
    safety
  });
  // NEW: explicitly log page window
  console.log('[auto-pagination] pageWindow', { usableH: Math.round(usableH) });

  // 0) If requested, clear breaks FIRST so positions we compute are correct
  if (clearExisting) removeAllPageBreaks(editor);

  // temporarily unclip while we measure block heights
  const endMeasure = beginMeasure(pageEl, contentEl);
  try {
    const blocks = topLevelBlocks(contentEl);

    if (!blocks.length) {
      console.log('[auto-pagination] no blocks to paginate.');
      return;
    }

    // ---------- choose cut index (limit + bottom-slack snap) ----------
    const firstTop = effectiveTop(blocks[0]);
    const limitY   = firstTop + usableH;

    if (window.__RT_dumpBlocks) {
      console.log('[auto-pagination] TRACE firstTop/limitY', {
        firstTop: Math.round(firstTop),
        limitY:   Math.round(limitY),
      });
      blocks.slice(0, 80).forEach((el, i) => {
        const r = el.getBoundingClientRect();
        const m = getMargins(el);
        const effTop = Math.round(r.top - m.mt);
        const effBtm = Math.round(r.bottom + m.mb);
        const sample = (el.textContent || '').replace(/\s+/g,' ').trim().slice(0, 20);
        console.log(`[blk ${i}] effTop=${effTop} effBtm=${effBtm} sample="${sample}"`);
      });
    }

    const isEmptyBlockEl = (el) => {
      if (!el) return true;
      const txt = (el.textContent || '').replace(/\s+/g, '').trim();
      if (txt.length) return false;
      if (el.querySelector && el.querySelector('img,table,hr,ul,ol,pre,code,blockquote')) return false;
      return true;
    };

    // 1) first overflowing block by effective bottom
    let i = -1;
    for (let k = 0; k < blocks.length; k++) {
      if (effectiveBottom(blocks[k]) > limitY) { i = k; break; }
    }
    if (i === -1) {
      console.log('[auto-pagination] no overflow (no cut needed).');
      console.log('[auto-pagination] inserted breaks:', 0);
      return;
    }

    // 2) compute line metrics and thresholds
    const probe  = contentEl.querySelector('.ProseMirror p') || contentEl.querySelector('.ProseMirror');
    const lineH  = parseFloat(getComputedStyle(probe).lineHeight) || 0;
    // how much overflow we consider “small” (we’ll try to snap back)
    const snapPx = Math.max(10, Math.round(lineH * 1.0));
    // how much slack we want to leave at the bottom (so Word-like look)
    const slackPx = Math.max(8, Math.round(lineH * 1.0));

    const overflowDelta = effectiveBottom(blocks[i]) - limitY;

    // Start from the first overflow candidate
    let cutIndex = i;

    // 3) If the overflow is small, try to snap back to the last block
    //    that still leaves *at least* slackPx of space at the bottom.
    if (overflowDelta <= snapPx) {
      let j = i - 1;
      while (j >= 0) {
        const prevBottom = effectiveBottom(blocks[j]);
        if (prevBottom <= (limitY - slackPx)) {
          // We found a block that ends high enough to respect the bottom slack.
          cutIndex = j; // cut BEFORE the block (so that next starts new page)
          break;
        }
        j--;
      }
      // If we didn't find any with slack, fall back to cutting before the first overflow
      if (j < 0) {
        cutIndex = i; // default
      }
    }

    // 4) Never cut on a purely empty spacer; move back to the previous non-empty
    while (cutIndex > 0 && isEmptyBlockEl(blocks[cutIndex])) {
      cutIndex--;
    }

    // 5) Map to document position and insert the break
    const cutPos = posAtBlockStart(editor, blocks[cutIndex]);
    if (cutPos != null) {
      insertBreaksAt(editor, [cutPos]);

      const b   = blocks[cutIndex];
      const txt = (b.textContent || '').trim().slice(0, 40);
      const r   = b.getBoundingClientRect();
      const m   = getMargins(b);
      console.log('[auto-pagination] CUT @ index', cutIndex, {
        pos: cutPos,
        sample: txt,
        effBottom: Math.round(r.bottom + m.mb),
        limitY: Math.round(limitY),
        overflowDelta: Math.round(overflowDelta),
        snapPx: Math.round(snapPx),
        slackPx: Math.round(slackPx),
      });
      console.log('[auto-pagination] inserted breaks:', 1);
    } else {
      console.log('[auto-pagination] could not find posAtBlockStart for cut index', cutIndex);
      console.log('[auto-pagination] inserted breaks:', 0);
    }
  } finally {
    endMeasure();
  }
}
