// /public/assets/js/rteditor/auto-pagination.js
// Auto-pagination for TipTap content using manual PageBreak nodes.
// Safe to drop in. No other files need edits for this to work.
window.__RT_debugAutoPaginate = true; // set to false in production

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
// Make Legal snap earlier: bias ≈ 1.25× a block or 1.1× line-height.
// Cap to avoid jumping too far on very tall pages.
function computeSlackPx(blocks, startIdx, endIdx, contentEl, usableH) {
  const steps = [];
  for (let i = Math.max(0, startIdx); i <= Math.min(endIdx, blocks.length - 1); i++) {
    const b = blocks[i];
    steps.push(Math.max(0, (b.h || 0)));
  }
  const medStep = median(steps) || 0; // ~ one paragraph height
  const probe = contentEl.querySelector('.ProseMirror p') || contentEl.querySelector('.ProseMirror');
  const lh = probe ? (parseFloat(getComputedStyle(probe).lineHeight) || 0) : 0;

  // Stronger bias than before so tiny overflows (1–2px) snap one more block up.
  // Increase 1.25 to 1.30 for slightly earlier snaps; decrease if you cut a block too soon in exotic layouts.
  const base = Math.max(lh * 1.10, medStep * 1.25, 18); // 1.25 is the important bias

  // Gentle upper cap: don't exceed ~2 blocks or ~10–12% of the page window
  const cap  = Math.max(medStep * 2.0, lh * 2.0, (usableH || 0) * 0.12 || 0);

  return Math.round(Math.min(base, cap));
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
if (typeof window.__RT_dumpBlocks === 'undefined') {
  window.__RT_dumpBlocks = false; // set to true in console when needed in debugging
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

  // Re-entrancy guard + rapid-repeat guard
  if (runOnce._running) {
    if (DEBUG_FLAG) console.log('[auto-pagination] runOnce skipped: already running');
    return;
  }
  const NOW = Date.now();
  if (runOnce._lastRunAt && (NOW - runOnce._lastRunAt) < 250) {
    if (DEBUG_FLAG) console.log('[auto-pagination] runOnce skipped: last run too recent');
    return;
  }
  runOnce._running = true;
  runOnce._lastRunAt = NOW;

  // Helper: find scroll container
  function findScrollContainer(el) {
    let node = el;
    while (node) {
      if (node instanceof HTMLElement) {
        const cs = getComputedStyle(node);
        const overflowY = cs.overflowY || cs.overflow;
        if (node !== document.documentElement && node !== document.body && (overflowY === 'auto' || overflowY === 'scroll')) {
          return node;
        }
      }
      node = node.parentElement;
    }
    const main = document.querySelector('.main-content');
    if (main) return main;
    return window;
  }
  const scrollContainer = findScrollContainer(contentEl);

  // ---- attach light scroll watcher (if not already) ----
  if (scrollContainer && !scrollContainer.__rt_scrollWatcherAttached) {
    scrollContainer.__rt_scrollWatcherAttached = true;
    scrollContainer.__rt_userScrolling = false;
    scrollContainer.__rt_scrollTimer = null;
    const touchOrWheel = () => {
      scrollContainer.__rt_userScrolling = true;
      if (scrollContainer.__rt_scrollTimer) clearTimeout(scrollContainer.__rt_scrollTimer);
      scrollContainer.__rt_scrollTimer = setTimeout(() => {
        scrollContainer.__rt_userScrolling = false;
      }, 280);
    };
    scrollContainer.addEventListener('wheel', touchOrWheel, { passive: true });
    scrollContainer.addEventListener('touchmove', touchOrWheel, { passive: true });
    scrollContainer.addEventListener('scroll', touchOrWheel, { passive: true });
    window.addEventListener('wheel', touchOrWheel, { passive: true });
    window.addEventListener('touchmove', touchOrWheel, { passive: true });
  }

  // If user is actively scrolling, skip run.
  if (scrollContainer && scrollContainer.__rt_userScrolling) {
    if (DEBUG_FLAG) console.log('[auto-pagination] skipping because user is scrolling');
    runOnce._running = false;
    return;
  }

  // ---------- compute usable height ----------
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

  if (DEBUG_FLAG) {
    console.log('[auto-pagination] usableH(cfg/dom)=', {
      fromConfig: Math.round(usableH_cfg),
      fromDom:    Math.round(usableH_dom),
      chosen:     Math.round(usableH),
      safety
    });
  }

  // ---- Collect existing pageBreak positions (so we can compare and skip if unchanged) ----
  const state = editor.state;
  const existingBreaks = [];
  if (state) {
    state.doc.descendants((node, pos) => {
      if (node.type && node.type.name === 'pageBreak') existingBreaks.push(pos);
    });
  }

  console.log('[auto-pagination-debug] existingBreaks:', existingBreaks.length, 'lastDocSig:', editor && editor.__rt_lastPaginateAt);

  // Compute blocks and breakPositions (DOM-measured)
  const endMeasure = beginMeasure(pageEl, contentEl);
  let computedBreakPositions = [];
  try {
    const blocks = collectBlockHeights(editor, contentEl);
    if (!blocks.length) {
      // nothing to paginate
      if (DEBUG_FLAG) console.log('[auto-pagination] nothing to paginate (no blocks)');
      runOnce._running = false;
      endMeasure();
      return;
    }

    // Multi-page loop calculate breakPositions (copy of previous logic)
    const breakPositions = [];
    let start = 0;
    let cutGuard = 0;
    while (start < blocks.length && cutGuard < 200) {
      cutGuard++;
      const firstTop = effectiveTop(blocks[start].el);
      const limitY   = firstTop + usableH;
      if (DEBUG_FLAG) console.log('[auto-pagination] TRACE firstTop/limitY', { firstTop: Math.round(firstTop), limitY: Math.round(limitY) });

      let i = -1;
      for (let k = start; k < blocks.length; k++) {
        const b = blocks[k];
        const bot = effectiveBottom(b.el);
        if (window.__RT_dumpBlocks) {
          console.log(`[blk ${k}] effTop=${Math.round(effectiveTop(b.el))} effBtm=${Math.round(bot)} sample="${(b.el.textContent||'').trim().slice(0,1)}"`);
        }
        if (bot > limitY) { i = k; break; }
      }
      if (i === -1) break;

      const overflowBot = effectiveBottom(blocks[i].el);
      const overflowDelta = Math.round(overflowBot - limitY);
      const slackPx = computeSlackPx(blocks, start, i, contentEl, usableH);

      let j = i - 1;
      const target = limitY - slackPx;
      while (j >= start && effectiveBottom(blocks[j].el) > target) j--;

      let cutIndex;
      if (j < start && overflowDelta > slackPx) cutIndex = i;
      else cutIndex = Math.max(start + 1, j);

      const cutPos = posAtBlockStart(editor, blocks[cutIndex].el);
      if (DEBUG_FLAG) console.log('[auto-pagination] CUT', { index: cutIndex, pos: cutPos });

      if (typeof cutPos === 'number') breakPositions.push(cutPos);
      else break;
      start = cutIndex;
      if (start < blocks.length - 1 && effectiveBottom(blocks[start].el) - effectiveTop(blocks[start].el) > usableH) {
        start++;
      }
    }
    computedBreakPositions = breakPositions;
    console.log('[auto-pagination-debug] computedBreakPositions:', computedBreakPositions.length, computedBreakPositions.slice(0,10));

  } finally {
    endMeasure();
  }

  // ---------------------------
  // Compare and apply minimal diff (tolerant equality)
  // ---------------------------
  // Helper: near-equal positions (px/pos noise tolerance)
  const POS_TOLERANCE = 3; // positions within ±3 are treated equal
  function isNear(a, b) { return Math.abs((a|0) - (b|0)) <= POS_TOLERANCE; }

  // Quick equality: same length AND every entry matches within tolerance
  let equal = false;
  if (existingBreaks.length === computedBreakPositions.length) {
    equal = existingBreaks.every((v, i) => isNear(v, computedBreakPositions[i]));
  }

  if (equal) {
    if (DEBUG_FLAG) console.log('[auto-pagination] no-op: existing pageBreaks match computed positions — skipping edit');
    if (editor) editor.__rt_lastPaginateAt = Date.now();
    runOnce._running = false;
    return;
  }

  // Build sets of positions to insert and to delete (minimal patch)
  const toInsert = []; // positions to insert
  const toDelete = []; // existing positions to delete (exact doc positions)

  // Mark computed insertions: keep a computed entry if it doesn't match any existingBreaks within tolerance
  for (const cp of computedBreakPositions) {
    const match = existingBreaks.find((eb) => isNear(eb, cp));
    if (!match && cp != null) toInsert.push(cp);
  }

  // Mark deletions: keep an existing break only if it matches a computed position
  for (const eb of existingBreaks) {
    const match = computedBreakPositions.find((cp) => isNear(cp, eb));
    if (!match) toDelete.push(eb);
  }

  // If diff is empty (unexpected) skip
  if (!toInsert.length && !toDelete.length) {
    if (DEBUG_FLAG) console.log('[auto-pagination] no-op after diff (no inserts/deletes)');
    if (editor) editor.__rt_lastPaginateAt = Date.now();
    runOnce._running = false;
    return;
  }

  // Apply mutations minimally and safely
  try {
    if (editor) editor.__rt_applyingPageBreaks = true;

    // Save scroll + heights before mutate
    function getScrollTop(container) {
      if (container === window) return window.scrollY || window.pageYOffset || 0;
      return container.scrollTop || 0;
    }
    function setScrollTop(container, v) {
      if (container === window) window.scrollTo(0, v);
      else container.scrollTop = v;
    }
    const savedScrollTop = getScrollTop(scrollContainer || window);
    const beforeHeight = contentEl.scrollHeight || contentEl.getBoundingClientRect().height || 0;

    // Perform deletions first (from end to start to keep positions valid)
    if (toDelete.length) {
      // Build transaction deleting each pageBreak node we want removed.
      const { state, view } = editor;
      const tr = state.tr;
      // Find concrete ranges for each break to delete
      // We'll scan doc to locate pageBreak nodes and match their positions against toDelete tolerance
      state.doc.descendants((node, pos) => {
        if (node.type && node.type.name === 'pageBreak') {
          const found = toDelete.find(d => isNear(d, pos));
          if (found) {
            // We delete the pageBreak node plus optionally adjacent empty paragraph (same logic as removeAllPageBreaks)
            let from = pos;
            let to   = pos + node.nodeSize;

            const $pos = state.doc.resolve(pos);
            const parentNode = $pos.parent;
            const idx = $pos.index();

            const before = parentNode.child(idx - 1);
            if (isEmptyParagraph(before)) {
              let cursor = $pos.start() + 0;
              for (let i = 0; i < idx - 1; i++) cursor += parentNode.child(i).nodeSize;
              const paraFrom = cursor;
              const paraTo   = cursor + before.nodeSize;
              from = Math.min(from, paraFrom);
            }

            const after = parentNode.child(idx + 1);
            if (isEmptyParagraph(after)) {
              let cursor = $pos.start() + 0;
              for (let i = 0; i <= idx + 1; i++) cursor += parentNode.child(i).nodeSize;
              const paraTo = cursor;
              to = Math.max(to, paraTo);
            }

            tr.delete(from, to);
          }
        }
      });
      if (tr.docChanged) view.dispatch(tr);
    }

    // Insert missing breaks (in descending order)
    if (toInsert.length) {
      // Use existing helper: insertBreaksAt
      insertBreaksAt(editor, toInsert);
    }

    // After mutation, restore scroll by height delta (small settle)
    setTimeout(() => {
      try {
        const afterHeight = contentEl.scrollHeight || contentEl.getBoundingClientRect().height || 0;
        const heightDelta = (afterHeight - beforeHeight) || 0;
        const adjusted = Math.max(0, savedScrollTop + heightDelta);
        setScrollTop(scrollContainer || window, adjusted);
      } catch (e) { /* ignore */ }
    }, 0);

    if (DEBUG_FLAG) {
      console.log('[auto-pagination] applied diff:', { inserted: toInsert.length, deleted: toDelete.length });
    }

    if (editor) editor.__rt_lastPaginateAt = Date.now();
  } finally {
    // keep suppression for a slightly longer cooldown to avoid immediate re-triggers
    setTimeout(() => { if (editor) editor.__rt_applyingPageBreaks = false; }, 800);
    runOnce._running = false;
    runOnce._lastRunAt = Date.now();
  }
}
