// Path: /src/rteditor/auto-pagination.js
// Auto-pagination for TipTap content using manual PageBreak nodes.
// Safe to drop in. No other files need edits for this to work.
window.__RT_debugAutoPaginate = true; // set to false in production

function ensurePageOverlay(editorEl) {
  let overlay = editorEl.querySelector('.rt-page-overlay');

  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'rt-page-overlay';
    editorEl.appendChild(overlay);
  }

  return overlay;
}

function beginMeasure(pageEl, contentEl) {
  const prev = [];
  if (pageEl)    { prev.push([pageEl,    pageEl.style.overflow]);    pageEl.style.overflow    = 'visible'; }
  if (contentEl) { prev.push([contentEl, contentEl.style.overflow]); contentEl.style.overflow = 'visible'; }
  return () => { for (const [el, v] of prev) el.style.overflow = v; };
}

function pmRoot(contentEl, editor) {
  // Absolute source of truth in TipTap
  if (editor?.view?.dom instanceof HTMLElement) {
    return editor.view.dom;
  }

  // Defensive fallback (should not be needed)
  return document.querySelector('.ProseMirror');
}

// ---------- page separator helpers (DOM-only, ephemeral) ----------

function clearPageSeparators(pm) {
  if (!pm) return;
  pm.querySelectorAll('.rt-page-separator').forEach(el => el.remove());
}

function renderPageSeparators(editor, contentEl, pageCuts) {
  if (!editor || !contentEl || !Array.isArray(pageCuts)) return;

  const editorEl = editor.view?.dom?.parentElement;
  const proseEl  = editor.view?.dom;

  if (!editorEl || !proseEl) return;

  const overlay = ensurePageOverlay(editorEl);

  // Pure DOM: recreate every run
  overlay.innerHTML = '';

  const editorRect = editorEl.getBoundingClientRect();

  // Render separators (map PM doc pos → DOM Y)
  pageCuts.forEach((cutPos) => {
    let coords;
    try {
      coords = editor.view.coordsAtPos(cutPos);
    } catch {
      return;
    }

    // coords.top is viewport-based
    const y =
      coords.top - editorRect.top;

    const sep = document.createElement('div');
    sep.className = 'rt-page-separator';
    sep.style.top = `${Math.round(y)}px`;
    overlay.appendChild(sep);
  });
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
// Dynamic debug check — read the window flag at runtime so console toggles take effect without reload.
function DEBUG_FLAG() { return !!window.__RT_debugAutoPaginate; }

function debugSnapshot({ pageEl, headerEl, footerEl, contentEl, blocks }) {
  if (!DEBUG_FLAG()) return;

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
  const pm = pmRoot(contentEl, editor);
  if (!view || !pm) return [];

  const blocks = [];
  // Only top-level block children: good enough for pagination without over-measuring
  const kids = Array.from(pm.children);
  for (const el of kids) {
    // Skip any existing page-wrapper DOM nodes (they represent full pages already)
    // and skip page-break marker elements themselves.
    if (el.classList && (el.classList.contains('rt-node-page') || el.getAttribute('data-type') === 'page-wrapper')) {
      if (DEBUG_FLAG()) console.log('[auto-pagination] skipping page-wrapper DOM element in collectBlockHeights');
      continue;
    }
    if (el.hasAttribute && el.hasAttribute('data-page-break')) {
      // Skip explicit page-break markers inserted previously.
      continue;
    }
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
  safety = 14,
} = {}) {
  // Require editor and contentEl; pageEl may be null for nodeView page mode.
  if (!editor || !contentEl) {
    if (typeof console !== 'undefined') console.warn('[auto-pagination] runOnce aborted: missing editor or contentEl');
    return;
  }

  // --- quick guard: if document already contains pageWrapper nodes, skip insertion phase ---
  try {
    const st = editor.state;
    let hasPageWrapper = false;
    if (st && st.doc) {
      st.doc.descendants((n) => {
        if (n && n.type && (n.type.name === 'pageWrapper' || n.type.name === 'page_wrapper' || n.type.name === 'page-wrapper')) {
          hasPageWrapper = true;
          return false;
        }
        return true;
      });
    }
    if (hasPageWrapper) {
      // PageWrapper mode uses ProseMirror transforms, not DOM block pagination
      runOnce._running = false;
      return;
    }
  } catch (e) {
    // continue cautiously
  }

   if (runOnce._running) {
    if (DEBUG_FLAG()) console.log('[auto-pagination] runOnce skipped: already running');
    return;
  }
  const NOW = Date.now();
  if (runOnce._lastRunAt && (NOW - runOnce._lastRunAt) < 250) {
    if (DEBUG_FLAG()) console.log('[auto-pagination] runOnce skipped: last run too recent');
    return;
  }
  runOnce._running = true;
  runOnce._lastRunAt = NOW;

  // find scroll container (unchanged)
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

  // attach light scroll watcher (unchanged)
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

  if (scrollContainer && scrollContainer.__rt_userScrolling) {
    if (DEBUG_FLAG) console.log('[auto-pagination] skipping because user is scrolling');
    runOnce._running = false;
    return;
  }

  // ---------- compute usable height (unchanged) ----------
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

  // ---- Word-accurate usable height ----
  // Treat margins as header/footer, NOT padding

  const cfg = (typeof getPageConfig === 'function') ? getPageConfig() : null;

  // Cache page config for visual pagination (separators)
  if (editor && cfg) {
    editor.__rt_lastPageConfig = cfg;
  }

    // Page height (A4 fallback if config is missing or late)
  let pageH_px = 0;

  if (cfg?.size) {
    const isLandscape = cfg.orientation === 'landscape';
    const pageH_mm = isLandscape
      ? (cfg.size.wmm || cfg.size.w)
      : (cfg.size.hmm || cfg.size.h);

    pageH_px = (parseFloat(pageH_mm) || 0) / 25.4 * 96;
  }

  // HARD FALLBACK: A4 portrait (Word default)
  if (!pageH_px || pageH_px < 100) {
    pageH_px = 297 / 25.4 * 96; // A4 height in px
    if (DEBUG_FLAG()) {
      console.warn('[auto-pagination] page size missing — falling back to A4');
    }
  }

  // Margins = header/footer heights
  const margins = cfg?.margins || {};
  const marginTop_px =
    cssLenToPx(margins.top ?? '25.4mm');
  const marginBottom_px =
    cssLenToPx(margins.bottom ?? '25.4mm');

  // Final usable height
 let usableH = pageH_px - marginTop_px - marginBottom_px - safety;

  // Guard: never allow zero/negative usable height
  if (!usableH || usableH < 100) {
    usableH = Math.max(100, pageH_px * 0.8);
    if (DEBUG_FLAG()) {
      console.warn('[auto-pagination] usableH invalid — clamped', Math.round(usableH));
    }
  }

  if (DEBUG_FLAG()) {
    console.log('[auto-pagination] usableH (Word model)=', {
      pageH_px: Math.round(pageH_px),
      marginTop_px: Math.round(marginTop_px),
      marginBottom_px: Math.round(marginBottom_px),
      usableH: Math.round(usableH),
      safety
    });
  }

  if (DEBUG_FLAG()) {
    console.log('[auto-pagination] usableH=', {
      fromConfig: Math.round(usableH_cfg),
      chosen:     Math.round(usableH),
      safety
    });
  }

  const endMeasure = beginMeasure(pageEl, contentEl);
  let computedBreakPositions = [];
  try {
    const blocks = collectBlockHeights(editor, contentEl);
    if (!blocks.length) {
      if (DEBUG_FLAG) console.log('[auto-pagination] nothing to paginate (no blocks)');
      runOnce._running = false;
      endMeasure();
      return;
    }

    // compute breakPositions loop <-- unchanged algorithm, but we keep the result as `computedBreakPositions`
    const breakPositions = [];
    let start = 0;
    let cutGuard = 0;
    while (start < blocks.length && cutGuard < 200) {
      cutGuard++;
      const firstTop = effectiveTop(blocks[start].el);
      const limitY   = firstTop + usableH;
      let i = -1;
      for (let k = start; k < blocks.length; k++) {
        const b = blocks[k];
        const bot = effectiveBottom(b.el);
        if (bot > limitY) { i = k; break; }
      }
      if (i === -1) break;

      const overflowBot = effectiveBottom(blocks[i].el);
      const overflowDelta = Math.round(overflowBot - limitY);
      const slackPx = computeSlackPx(blocks, start, i, contentEl, usableH);

      let j = i - 1;
      const target = limitY - slackPx;
      while (j >= start) {
        const blk = blocks[j];
        if (!blk) { j--; continue; }
        try {
          if (effectiveBottom(blk.el) > target) { j--; continue; }
        } catch (e) {
          j--;
          continue;
        }
        break;
      }

      let cutIndex;
      if (j < start && overflowDelta > slackPx) {
        cutIndex = i;
      } else {
        cutIndex = Math.max(start + 1, j);
      }
      if (cutIndex < 0) cutIndex = 0;
      if (cutIndex >= blocks.length) cutIndex = blocks.length - 1;

      const cutBlock = blocks[cutIndex];
      if (!cutBlock || !cutBlock.el) {
        if (DEBUG_FLAG) console.warn('[auto-pagination] aborted: invalid cut block at index', cutIndex);
        break;
      }

      const cutPos = posAtBlockStart(editor, cutBlock.el);
      if (DEBUG_FLAG) console.log('[auto-pagination] CUT', { index: cutIndex, pos: cutPos });

      if (typeof cutPos === 'number') breakPositions.push(cutPos);
      else break;
      start = cutIndex;
      if (start < blocks.length - 1 && effectiveBottom(blocks[start].el) - effectiveTop(blocks[start].el) > usableH) {
        start++;
      }
    }
    // normalise positions: integer, sorted, unique
    computedBreakPositions = Array.from(new Set(breakPositions.map(p => Math.max(0, Math.floor(p))))).sort((a,b)=>a-b);
  } finally {
    endMeasure();
  }

  // Phase 1: Continuous-flow mode
  // We compute break positions ONLY for visual pagination.
  // No document mutations are allowed.

  if (DEBUG_FLAG()) {
    console.log('[auto-pagination] computed virtual page cuts:', computedBreakPositions);
  }

  if (editor) {
    editor.__rt_lastPaginateAt = Date.now();
  }

  // ---- Phase 1B: render visual page separators (DOM-only) ----
  if (computedBreakPositions.length) {
    renderPageSeparators(editor, contentEl, computedBreakPositions);
  } else {
    // Clear any stale separators
    const editorEl = editor?.view?.dom?.parentElement;
    const overlay = editorEl?.querySelector('.rt-page-overlay');
    if (overlay) {
      overlay.querySelectorAll('.rt-page-separator').forEach(el => el.remove());
    }
  }

  runOnce._running = false;
  return computedBreakPositions;

}
