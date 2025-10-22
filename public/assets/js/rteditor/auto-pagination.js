// /public/assets/js/rteditor/auto-pagination.js
// One-shot "Suggest & Insert Page Breaks" based on measured DOM block heights.
// Relies on your existing PageBreak node/command (editor.commands.insertPageBreak)

function px(val) {
  return typeof val === 'number' ? val : parseFloat(String(val || 0));
}

function parseCSSLength(str, pagePx, dpi = 96) {
  if (!str) return 0;
  const s = String(str).trim();
  if (s.endsWith('px')) return parseFloat(s);
  if (s.endsWith('mm')) return (parseFloat(s) / 25.4) * dpi;
  if (s.endsWith('cm')) return (parseFloat(s) * 10 / 25.4) * dpi;
  if (s.endsWith('in')) return parseFloat(s) * dpi;
  if (s.endsWith('pt')) return (parseFloat(s) / 72) * dpi;
  // unitless (fallback): treat as px
  return parseFloat(s);
}

/**
 * Collect top-level block DOM nodes inside the TipTap content,
 * preserving document order, and map them back to ProseMirror positions.
 */
function collectBlocks(view) {
  const root = view.dom; // ProseMirror root inside #editor
  const blocks = [];
  // We iterate children at depth 0 in the DOM; this matches PM block rendering.
  // If a block contains nested content (e.g., list), we still treat it as one chunk.
  for (const el of root.children) {
    if (!(el instanceof HTMLElement)) continue;
    // skip page-break decorations (they are real nodes in your schema with data-page-break)
    if (el.hasAttribute('data-page-break')) {
      const pos = view.posAtDOM(el, 0);
      blocks.push({ el, pos, isBreak: true, height: el.getBoundingClientRect().height });
      continue;
    }
    // map to a position before the node
    let pos;
    try { pos = view.posAtDOM(el, 0); } catch { continue; }
    const rect = el.getBoundingClientRect();
    blocks.push({ el, pos, isBreak: false, height: rect.height });
  }
  return blocks;
}

/**
 * Compute where page breaks *should* go, given available content height.
 */
function computeBreakPositions(view, contentHeightPx) {
  const blocks = collectBlocks(view);
  const breaks = [];
  let used = 0;

  for (let i = 0; i < blocks.length; i++) {
    const b = blocks[i];
    if (b.isBreak) {
      // manual break resets counter
      used = 0;
      continue;
    }
    const h = px(b.height);
    if (used + h > contentHeightPx && i > 0) {
      // insert a break *before* this block
      breaks.push(b.pos);
      used = h; // start next page with this block height
    } else {
      used += h;
    }
  }
  return breaks;
}

/**
 * Remove existing PageBreak nodes (optional) to avoid duplicates.
 */
function removeExistingBreaks(editor) {
  const { state, view } = editor;
  const { tr } = state;
  let changed = false;
  state.doc.descendants((node, pos) => {
    if (node.type.name === 'pageBreak') {
      tr.delete(pos, pos + node.nodeSize);
      changed = true;
    }
  });
  if (changed) view.dispatch(tr);
}

/**
 * Insert breaks at given ProseMirror positions.
 * Inserts from the end so positions don’t shift.
 */
function insertBreaksAt(editor, positions) {
  const sorted = [...positions].sort((a, b) => b - a);
  const chain = editor.chain().focus();
  sorted.forEach(pos => chain.insertContentAt(pos, { type: 'pageBreak' }));
  return chain.run();
}

/**
 * Public API:
 *  - runOnce(editor, { pageEl, contentEl, headerEl, footerEl, getPageConfig, clearExisting })
 */
export function runOnce(editor, cfg) {
  const { view } = editor;
  const { pageEl, contentEl, headerEl, footerEl, getPageConfig, clearExisting = true } = cfg;

  if (!pageEl || !contentEl || !getPageConfig) {
    console.warn('[auto-pagination] Missing required elements or getPageConfig().');
    return false;
  }

  const pageRect   = pageEl.getBoundingClientRect();
  const headerRect = headerEl?.getBoundingClientRect();
  const footerRect = footerEl?.getBoundingClientRect();

  const pageH  = px(pageRect.height);
  const headH  = px(headerRect?.height || 0);
  const footH  = px(footerRect?.height || 0);

  const cs = getComputedStyle(contentEl);
  const pt = parseCSSLength(cs.paddingTop);
  const pb = parseCSSLength(cs.paddingBottom);

  const available = Math.max(0, pageH - headH - footH - pt - pb);

  if (clearExisting) removeExistingBreaks(editor);

  const positions = computeBreakPositions(view, available);

  if (!positions.length) {
    // nothing to do (or everything fits on one page)
    return true;
  }
  return insertBreaksAt(editor, positions);
}
