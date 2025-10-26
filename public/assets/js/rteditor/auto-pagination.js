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

function median(nums) {
  if (!nums.length) return 0;
  const a = nums.slice().sort((x, y) => x - y);
  const mid = Math.floor(a.length / 2);
  return a.length % 2 ? a[mid] : (a[mid - 1] + a[mid]) / 2;
}

/** Estimate a good per-page slack in px so we snap to the previous block */
function computeSlackPx(blocks, startIdx, endIdx, contentEl) {
  const steps = [];
  for (let i = Math.max(0, startIdx); i <= Math.min(endIdx, blocks.length - 1); i++) {
    const b = blocks[i];
    steps.push(Math.max(0, (b.h || 0)));
  }
  const medStep = median(steps);                // ~ one paragraph height (line + spacing)
  const probe = contentEl.querySelector('.ProseMirror p') || contentEl.querySelector('.ProseMirror');
  const lh = probe ? parseFloat(getComputedStyle(probe).lineHeight) || 0 : 0;

  // Heuristic: prefer ~0.8 of a block, but never below ~0.9 of a line.
  const slack = Math.max(lh * 0.9, medStep * 0.8, 14); // floor guard
  return Math.round(slack);
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
  getPageConfig,
  clearExisting = true,
  safety = 14,
} = {}) {
  if (!editor || !pageEl || !contentEl) return;

  // --- compute usable height from CONFIG + DOM (take the smaller) ---
  let usableH_cfg = 0;
  try {
    const cfg = (typeof getPageConfig === 'function') ? getPageConfig() : null;
    if (cfg && cfg.size) {
      const isLandscape = cfg.orientation === 'landscape';
      const pageH_mm = isLandscape ? (cfg.size.wmm || cfg.size.w) : (cfg.size.hmm || cfg.size.h);
      const pageH_px = (parseFloat(pageH_mm) || 0) / 25.4 * 96;
      const headerH  = headerEl ? headerEl.getBoundingClientRect().height : 0;
      const footerH  = footerEl ? footerEl.getBoundingClientRect().height : 0;
      const cs = getComputedStyle(contentEl);
      const pt = parseFloat(cs.paddingTop)    || 0;
      const pb = parseFloat(cs.paddingBottom) || 0;
      usableH_cfg = pageH_px - headerH - footerH - pt - pb;
    }
  } catch {}

  const cs2 = getComputedStyle(contentEl);
  const pt2 = parseFloat(cs2.paddingTop) || 0;
  const pb2 = parseFloat(cs2.paddingBottom) || 0;
  const usableH_dom = contentEl.clientHeight - pt2 - pb2;

  let usableH = Math.max(0, Math.min(usableH_cfg || Infinity, usableH_dom || Infinity) - safety);

  console.log('[auto-pagination] usableH(cfg/dom)=', {
    fromConfig: Math.round(usableH_cfg),
    fromDom:    Math.round(usableH_dom),
    chosen:     Math.round(usableH),
    safety
  });

  if (clearExisting) removeAllPageBreaks(editor);

  const endMeasure = beginMeasure(pageEl, contentEl);
  try {
    const blocks = collectBlockHeights(editor, contentEl);
    if (!blocks.length) return;

    // Multi-page loop: keep cutting until the rest fits.
    const breakPositions = [];
    let start = 0;
    let cutGuard = 0;

    while (start < blocks.length && cutGuard < 200) {
      cutGuard++;

      // Window bounds for this page
      const firstTop = effectiveTop(blocks[start].el);
      const limitY   = firstTop + usableH;
      console.log('[auto-pagination] TRACE firstTop/limitY', { firstTop: Math.round(firstTop), limitY: Math.round(limitY) });

      // Find first overflow index i
      let i = -1;
      for (let k = start; k < blocks.length; k++) {
        const b = blocks[k];
        const top = effectiveTop(b.el);
        const bot = effectiveBottom(b.el);
        if (window.__RT_dumpBlocks) {
          console.log(`[blk ${k}] effTop=${Math.round(top)} effBtm=${Math.round(bot)} sample="${(b.el.textContent||'').trim().slice(0,1)}"`);
        }
        if (bot > limitY) { i = k; break; }
      }

      // If nothing overflows, we’re done.
      if (i === -1) break;

      const overflowBot = effectiveBottom(blocks[i].el);
      const overflowDelta = Math.round(overflowBot - limitY);

      // Dynamic slack for this page
      const slackPx = computeSlackPx(blocks, start, i, contentEl);

      // Snap back to the last block that stays within (limitY - slackPx)
      let j = i - 1;
      const target = limitY - slackPx;
      while (j >= start && effectiveBottom(blocks[j].el) > target) j--;

      // If we couldn’t find a safe “snap” and overflow is huge, cut at i
      let cutIndex;
      if (j < start && overflowDelta > slackPx) {
        cutIndex = i; // fall back: first overflowing block
      } else {
        // Normal case: cut BEFORE j (so page 2 starts at blocks[j])
        cutIndex = Math.max(start + 1, j); // ensure progress
      }

      const cutPos = posAtBlockStart(editor, blocks[cutIndex].el);
      console.log('[auto-pagination] CUT', {
        index: cutIndex,
        pos: cutPos,
        sample: (blocks[cutIndex].el.textContent || '').trim().slice(0,1),
        effBottom: Math.round(effectiveBottom(blocks[cutIndex].el)),
        limitY: Math.round(limitY),
        overflowDelta,
        slackPx
      });

      if (typeof cutPos === 'number') {
        breakPositions.push(cutPos);
      } else {
        // If we fail to map, bail to avoid infinite loop.
        break;
      }

      // Next page starts at the cut block
      start = cutIndex;

      // Safety: if the very next block would produce a zero-height window due to odd layout, bump start.
      if (start < blocks.length - 1 && effectiveBottom(blocks[start].el) - effectiveTop(blocks[start].el) > usableH) {
        start++;
      }
    }

    insertBreaksAt(editor, breakPositions);
    console.log('[auto-pagination] inserted breaks:', breakPositions.length);
  } finally {
    endMeasure();
  }
}
