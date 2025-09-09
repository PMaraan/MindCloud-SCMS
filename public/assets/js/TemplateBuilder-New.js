
// TipTapTest.js
// Sidebar blocks that stack/drag/snap like TemplateBuilder,
// AND the original top bar now works again. Toolbar actions
// apply to TipTap OR to the focused sidebar block text.

(function () {
  const GRID = 20;
  const PAGE_PADDING_TOP = 10;
  const snap = (v) => Math.round(v / GRID) * GRID;

  const waitForEditor = () =>
    new Promise((resolve) => {
      if (window.__mc?.editor) return resolve(window.__mc.editor);
      const iv = setInterval(() => {
        if (window.__mc?.editor) {
          clearInterval(iv);
          resolve(window.__mc.editor);
        }
      }, 20);
    });

  // Track which sidebar block body is focused
  let currentBlockBody = null;
  let pickingColor = false; // guards focus while the native color dialog is open


  // --- Selection locker for contentEditable blocks ---
const selectionStore = new WeakMap();

function saveSelection(body) {
  const sel = window.getSelection();
  if (!sel || sel.rangeCount === 0) return;
  const range = sel.getRangeAt(0);
  if (!body.contains(range.commonAncestorContainer)) return;
  selectionStore.set(body, range.cloneRange());
}

function restoreSelection(body) {
  const range = selectionStore.get(body);
  if (!range) return false;
  const sel = window.getSelection();
  sel.removeAllRanges();
  sel.addRange(range);
  return true;
}

function withRestoredSelection(cb) {
  if (!currentBlockBody) return;
  restoreSelection(currentBlockBody);
  cb();
  // store the new location after mutation
  saveSelection(currentBlockBody);
}

function wrapSelectionWithSpan(styleText) {
  withRestoredSelection(() => {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return;
    const range = sel.getRangeAt(0);

    if (range.collapsed) {
      // insert a styled zero-width placeholder so typing uses the style
      const span = document.createElement('span');
      span.setAttribute('style', styleText);
      span.appendChild(document.createTextNode('\u200b')); // ZWSP
      range.insertNode(span);
      // move caret to end of span
      const newRange = document.createRange();
      newRange.setStart(span.firstChild, span.firstChild.length);
      newRange.collapse(true);
      sel.removeAllRanges();
      sel.addRange(newRange);
      return;
    }

    // Non-collapsed: replace selection with styled HTML
    const frag = range.cloneContents();
    const div = document.createElement('div');
    div.appendChild(frag);
    const html = `<span style="${styleText}">${div.innerHTML}</span>`;
    document.execCommand('insertHTML', false, html);
  });
}



  // ---------- Overlay host (lets TipTap remain editable) ----------
  function ensureOverlay(pageEl) {
    let overlay = pageEl.querySelector('.mc-block-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'mc-block-overlay';
      Object.assign(overlay.style, {
        position: 'absolute',
        inset: '0',
        pointerEvents: 'none',         // don’t block clicks by default
        paddingTop: `${PAGE_PADDING_TOP}px`,
      });
      if (getComputedStyle(pageEl).position === 'static') pageEl.style.position = 'relative';
      pageEl.appendChild(overlay);
    }
    return overlay;
  }
  function setOverlaysDragEnabled(enabled) {
    document.querySelectorAll('.mc-block-overlay').forEach((ov) => {
      ov.style.pointerEvents = enabled ? 'auto' : 'none';
    });
  }

  // ---------- Generic block frame ----------
  function frameBlock(el) {
    el.classList.add('mc-block');
    el.dataset.rows = '1';
    Object.assign(el.style, {
      position: 'absolute',
      left: '32px',
      width: 'calc(100% - 32px)',
      boxSizing: 'border-box',
      background: 'transparent',
      border: 'none',
      padding: '0 32px',
      pointerEvents: 'auto',
    });

    if (!el.querySelector('.drag-handle')) {
      const grip = document.createElement('div');
      grip.className = 'drag-handle';
      Object.assign(grip.style, {
        position: 'absolute',
        left: '6px',
        top: '0',
        bottom: '0',
        width: '18px',
        display: 'grid',
        placeItems: 'center',
        cursor: 'grab',
        color: '#9ca3af',
        userSelect: 'none',
      });
      grip.innerHTML = '⋮⋮';
      el.appendChild(grip);
    }

    if (!el.querySelector('.remove-btn')) {
      const btn = document.createElement('button');
      btn.className = 'remove-btn';
      btn.type = 'button';
      btn.innerHTML = '×';
      Object.assign(btn.style, {
        position: 'absolute',
        right: '6px',
        top: '0',
        width: '22px',
        height: '22px',
        borderRadius: '11px',
        border: '1px solid #e5e7eb',
        background: '#fff',
        cursor: 'pointer',
        lineHeight: '20px',
        textAlign: 'center',
      });
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const overlay = el.closest('.mc-block-overlay');
        el.remove();
        if (overlay) reflowStack(overlay);
      });
      el.appendChild(btn);
    }
  }

  // ---------- Block factories ----------
  function makeTextField() {
    const el = document.createElement('div');
    frameBlock(el);
    const body = document.createElement('div');
    body.className = 'element-body';
    body.contentEditable = 'true';
    Object.assign(body.style, {
      outline: 'none',
      whiteSpace: 'nowrap',
      borderBottom: '1px solid #9ca3af',
      minWidth: '240px',
      padding: '2px 0',
      font: 'inherit',
    });
    el.appendChild(body);
    el.style.height = `${GRID}px`;
    el.dataset.rows = '1';
    registerBlockBody(body);
    return el;
  }

  function makeLabel() {
    const el = document.createElement('div');
    frameBlock(el);
    const body = document.createElement('div');
    body.className = 'element-body';
    body.contentEditable = 'true';
    Object.assign(body.style, {
      outline: 'none',
      whiteSpace: 'nowrap',
      padding: '2px 0',
      fontWeight: '600',
      font: 'inherit',
    });
    body.textContent = 'Label text';
    el.appendChild(body);
    el.style.height = `${GRID}px`;
    el.dataset.rows = '1';
    registerBlockBody(body);
    return el;
  }

  function makeParagraph() {
    const el = document.createElement('div');
    frameBlock(el);
    const body = document.createElement('div');
    body.className = 'element-body';
    body.contentEditable = 'true';
    Object.assign(body.style, {
      outline: 'none',
      whiteSpace: 'pre-wrap',
      wordBreak: 'break-word',
      lineHeight: '1.5',
      padding: '2px 0',
      font: 'inherit',
    });
    body.textContent = 'Paragraph text';
    el.appendChild(body);

    el.style.height = `${GRID * 2}px`;
    el.dataset.rows = '2';

    const autosize = () => {
      const overlay = el.closest('.mc-block-overlay');
      const lines = Math.max(1, Math.ceil(body.scrollHeight / GRID));
      const rows = Math.max(2, lines);
      const h = rows * GRID;
      if (h !== parseInt(el.style.height || '0', 10)) {
        el.style.height = `${h}px`;
        el.dataset.rows = String(rows);
        if (overlay) pushDownFrom(el, overlay);
      }
    };
    body.addEventListener('input', () => requestAnimationFrame(autosize));
    requestAnimationFrame(autosize);

    registerBlockBody(body);
    return el;
  }

  function makeTextArea() {
    const el = document.createElement('div');
    frameBlock(el);
    const body = document.createElement('div');
    body.className = 'element-body';
    body.contentEditable = 'true';
    Object.assign(body.style, {
      outline: 'none',
      whiteSpace: 'pre-wrap',
      wordBreak: 'break-word',
      lineHeight: '1.5',
      display: 'block',
      padding: '8px 10px',
      border: '1px solid #111827',
      borderRadius: '6px',
      background: '#fff',
      font: 'inherit',
    });
    body.textContent = 'Text block';
    el.appendChild(body);

    el.style.height = `${GRID * 4}px`;
    el.dataset.rows = '4';

    const autosize = () => {
      const overlay = el.closest('.mc-block-overlay');
      const contentH = Math.max(body.scrollHeight, GRID);
      const rows = Math.max(3, Math.ceil(contentH / GRID));
      const h = rows * GRID;
      if (h !== parseInt(el.style.height || '0', 10)) {
        el.style.height = `${h}px`;
        el.dataset.rows = String(rows);
        if (overlay) pushDownFrom(el, overlay);
      }
    };
    body.addEventListener('input', () => requestAnimationFrame(autosize));
    requestAnimationFrame(autosize);

    registerBlockBody(body);
    return el;
  }

function makeTable() {
  const el = document.createElement('div');
  frameBlock(el);

  // --- TABLE ---
  const tbl = document.createElement('table');
  Object.assign(tbl.style, {
    width: '100%',
    borderCollapse: 'collapse',
    tableLayout: 'fixed',
  });

  // Helper to make a nicely spaced cell
  function makeTD() {
    const td = document.createElement('td');
    Object.assign(td.style, {
      border: '1px solid #d1d5db',
      padding: '10px 12px',   // comfy padding like the "top" table
      verticalAlign: 'top',
      overflowWrap: 'anywhere',
      minHeight: '32px',      // keep row height looking good
      lineHeight: '1.4',      // readable spacing
    });
    td.contentEditable = 'true';
    // Place caret on its own line; keeps the cell height from collapsing
    td.innerHTML = '<p><br></p>';
    return td;
  }

  const START_ROWS = 3;
  const START_COLS = 4;
  for (let r = 0; r < START_ROWS; r++) {
    const tr = document.createElement('tr');
    for (let c = 0; c < START_COLS; c++) {
      tr.appendChild(makeTD());
    }
    tbl.appendChild(tr);
  }
  el.appendChild(tbl);

  // --- INLINE TOOLBAR ---
  const bar = document.createElement('div');
  bar.className = 'mc-table-toolbar';
  bar.innerHTML = `
    <button data-act="row-above"   title="Insert row above">↥ Row</button>
    <button data-act="row-below"   title="Insert row below">↧ Row</button>
    <button data-act="col-left"    title="Insert column left">↤ Col</button>
    <button data-act="col-right"   title="Insert column right">↦ Col</button>
    <span class="sep"></span>
    <button data-act="del-row"     title="Delete row">✖ Row</button>
    <button data-act="del-col"     title="Delete column">✖ Col</button>
    <button data-act="del-cell-local" title="Delete cell (local)">✖ Cell</button>
    <span class="sep"></span>
    <button data-act="even-cols"   title="Distribute columns evenly">⇔</button>
    <button data-act="toggle-head" title="Toggle header row">H</button>
    <span class="sep"></span>
    <button data-act="split-cell"  title="Split cell…">Split…</button>
  `;
  el.appendChild(bar);

  // --- RESIZE GRIPS ---
  const gripsX = document.createElement('div'); // between columns
  const gripsY = document.createElement('div'); // between rows
  gripsX.className = 'mc-col-grips';
  gripsY.className = 'mc-row-grips';
  el.appendChild(gripsX);
  el.appendChild(gripsY);

  // ===== focus tracking for current cell =====
  let lastCell = null;
  const getActiveCell = () =>
    (document.activeElement && /^(TD|TH)$/.test(document.activeElement.tagName))
      ? document.activeElement
      : lastCell;
  tbl.addEventListener('mousedown', (e) => {
    const td = e.target.closest('td,th');
    if (td) lastCell = td;
  });
  tbl.addEventListener('focusin', (e) => {
    const td = e.target.closest('td,th');
    if (td) lastCell = td;
  });

  // ===== Helpers =====
  const getFocusedCell = () =>
    (document.activeElement && (document.activeElement.tagName === 'TD' || document.activeElement.tagName === 'TH'))
      ? document.activeElement
      : null;

  function visualColCount(tr) {
    return Array.from(tr.children).reduce((s, c) => s + (c.colSpan || 1), 0);
  }
  function ensureAtLeast1RowCol() {
    if (!tbl.rows.length) {
      const tr = tbl.insertRow();
      tr.appendChild(makeTD());
    }
    if (!tbl.rows[0].cells.length) {
      for (const row of tbl.rows) row.appendChild(makeTD());
    }
  }

  // ---------- GLOBAL GRID (COLGROUP) ----------
  function ensureColGroup() {
    let cg = tbl.querySelector('colgroup');
    if (!cg) {
      cg = document.createElement('colgroup');
      const vcols = tbl.rows[0] ? visualColCount(tbl.rows[0]) : 1;
      for (let i = 0; i < Math.max(1, vcols); i++) {
        const col = document.createElement('col');
        col.style.width = (100 / Math.max(1, vcols)) + '%';
        cg.appendChild(col);
      }
      tbl.insertBefore(cg, tbl.firstChild);
    }
    return cg;
  }
  function gridColCount() {
    return ensureColGroup().children.length;
  }
  function readColPercents() {
    const cg = ensureColGroup();
    const n = cg.children.length;
    const out = [];
    let total = 0;
    for (const c of cg.children) {
      const w = parseFloat(c.style.width || '0');
      out.push(isFinite(w) && w > 0 ? w : 100 / n);
      total += out[out.length - 1];
    }
    return out.map(w => w * (100 / total));
  }
  function writeColPercents(arr) {
    const cg = ensureColGroup();
    while (cg.firstChild) cg.removeChild(cg.firstChild);
    for (const w of arr) {
      const col = document.createElement('col');
      col.style.width = w + '%';
      cg.appendChild(col);
    }
  }
  function clamp(n, a, b){ return Math.max(a, Math.min(b, n)); }

  // Row map (grid positions of cells)
  function makeRowMap(tr) {
    const map = [];
    let cursor = 0;
    for (const cell of tr.children) {
      const span = Math.max(1, cell.colSpan || 1);
      map.push({ cell, start: cursor, span });
      cursor += span;
    }
    return map;
  }
  function findCoveringCell(tr, colIndex) {
    const map = makeRowMap(tr);
    return map.find(m => colIndex >= m.start && colIndex < m.start + m.span);
  }

  // ---------- Replace grid columns in-place (used by split) ----------
  function replaceCols(start, removeCount, addCount) {
    const widths = readColPercents();
    const removedWidth = widths.slice(start, start + removeCount).reduce((a, b) => a + b, 0);
    const per = removedWidth / addCount;
    const newWidths = [
      ...widths.slice(0, start),
      ...Array.from({ length: addCount }, () => per),
      ...widths.slice(start + removeCount),
    ];
    writeColPercents(newWidths);
  }

  // ---------- SPLIT (only clicked cell changes; others keep count) ----------
  function splitCell(td, cols, rows) {
    cols = Math.max(1, parseInt(cols || 1, 10));
    rows = Math.max(1, parseInt(rows || 1, 10));
    if (cols <= 1 && rows <= 1) return;

    ensureColGroup();
    const tr = td.parentElement;

    const rowMap = makeRowMap(tr);
    const me = rowMap.find(m => m.cell === td);
    if (!me) return;

    const currentSpan = Math.max(1, td.colSpan || 1);
    const desiredPieces = Math.max(1, cols);
    const deltaCols = desiredPieces - currentSpan;

    // 1) update global grid within my region (preserves total width)
    if (deltaCols !== 0) replaceCols(me.start, currentSpan, desiredPieces);

    // 2) replace ONLY this cell with N cells (each 1 grid column wide)
    const newCells = Array.from({ length: desiredPieces }, () => {
      const c = document.createElement(td.tagName.toLowerCase());
      Object.assign(c.style, {
        border: td.style.border || '1px solid #d1d5db',
        padding: td.style.padding || '10px 12px',
        verticalAlign: td.style.verticalAlign || 'top',
        overflowWrap: td.style.overflowWrap || 'anywhere',
        minHeight: td.style.minHeight || '32px',
        lineHeight: td.style.lineHeight || '1.4',
      });
      c.contentEditable = 'true';
      c.colSpan = 1;
      c.innerHTML = '<p><br></p>';
      return c;
    });
    td.replaceWith(...newCells);

    // 3) other rows: adjust only the single covering cell’s colSpan by delta
    if (deltaCols !== 0) {
      const tbody = tbl.tBodies[0] || tbl;
      for (const row of tbody.rows) {
        if (row === tr) continue;
        const covering = findCoveringCell(row, me.start);
        if (covering) covering.cell.colSpan = Math.max(1, (covering.cell.colSpan || 1) + deltaCols);
      }
    }

    rebuildGrips(true);
  }

  // ---------- DELETE CELL (local; only this row changes) ----------
  function deleteCellLocal(td) {
    ensureColGroup(); // keep the grid as-is
    const tr = td.parentElement;

    const span = Math.max(1, td.colSpan || 1);

    // pick a receiver in *this row only* (prefer left, else right)
    let receiver = td.previousElementSibling || td.nextElementSibling;

    if (!receiver) {
      const repl = makeTD();
      tr.replaceChild(repl, td);
      rebuildGrips(true);
      return;
    }

    receiver.colSpan = Math.max(1, (receiver.colSpan || 1) + span);
    td.remove();

    ensureAtLeast1RowCol();
    rebuildGrips(true);
  }

  function openSplitDialog(td) {
    const overlay = document.createElement('div');
    overlay.className = 'mc-modal';
    const dlg = document.createElement('div');
    dlg.className = 'mc-dialog';
    dlg.innerHTML = `
      <h3>Split cell</h3>
      <div class="row"><label>Columns</label><input type="number" id="mc-split-cols" min="1" value="2"></div>
      <div class="row"><label>Rows</label><input type="number" id="mc-split-rows" min="1" value="1"></div>
      <div class="actions">
        <button class="mc-btn" data-act="cancel">Cancel</button>
        < ="mc-btn primary" data-act="ok">Split</button>
      </div>
    `;
    overlay.appendChild(dlg);
    document.body.appendChild(overlay);
    dlg.querySelector('[data-act="cancel"]').onclick = () => overlay.remove();
    dlg.querySelector('[data-act="ok"]').onclick = () => {
      const cols = dlg.querySelector('#mc-split-cols').value;
      const rows = dlg.querySelector('#mc-split-rows').value;
      splitCell(getActiveCell() || tbl.rows[0].cells[0], cols, rows);
      overlay.remove();
    };
  }

  // ===== Row/Col ops =====
  function insertRow(where) {
    const cell = getFocusedCell();
    const rowIndex = cell ? cell.parentElement.rowIndex : tbl.rows.length - 1;
    const refIndex = where === 'above' ? rowIndex : rowIndex + 1;
    const cols = gridColCount() || visualColCount(tbl.rows[0]) || 1;
    const tr = tbl.insertRow(refIndex);
    for (let i = 0; i < cols; i++) tr.appendChild(makeTD());
    rebuildGrips(true);
  }
  function insertCol(where) {
    const cell = getFocusedCell();
    const colIndex = cell ? cell.cellIndex : (tbl.rows[0]?.cells.length - 1) || 0;
    const ref = where === 'left' ? colIndex : colIndex + 1;

    for (const row of tbl.rows) {
      const td = makeTD();
      row.insertBefore(td, row.children[ref] || null);
    }

    const perc = readColPercents();
    const splitFrom = clamp(ref - 1, 0, perc.length - 1);
    const half = perc[splitFrom] / 2;
    perc.splice(splitFrom, 1, half, half);
    writeColPercents(perc);

    rebuildGrips(true);
  }
  function deleteRow() {
    const cell = getFocusedCell();
    const idx = cell ? cell.parentElement.rowIndex : tbl.rows.length - 1;
    if (tbl.rows.length > 1) tbl.deleteRow(idx);
    ensureAtLeast1RowCol();
    rebuildGrips(true);
  }
  function deleteCol() {
    const cell = getFocusedCell();
    const idx = cell ? cell.cellIndex : (tbl.rows[0]?.cells.length - 1) || 0;
    if ((tbl.rows[0]?.cells.length || 0) > 1) {
      for (const row of tbl.rows) row.deleteCell(idx);
      const widths = readColPercents();
      if (widths.length > 1) {
        const merged = widths.slice();
        if (idx < merged.length - 1) { merged[idx] += merged[idx + 1]; merged.splice(idx + 1, 1); }
        else { merged[idx - 1] += merged[idx]; merged.splice(idx, 1); }
        writeColPercents(merged);
      }
    }
    ensureAtLeast1RowCol();
    rebuildGrips(true);
  }
  function evenColumns() {
    const cols = gridColCount();
    const pct = 100 / cols;
    writeColPercents(Array.from({ length: cols }, () => pct));
    for (const row of tbl.rows) for (const cell of row.cells) cell.style.width = '';
    rebuildGrips(true);
  }
  function toggleHeaderRow() {
    if (!tbl.tHead) {
      const thead = tbl.createTHead();
      thead.insertBefore(tbl.rows[0], null);
      for (const th of thead.rows[0].cells) {
        const cell = document.createElement('th');
        while (th.firstChild) cell.appendChild(th.firstChild);
        for (const a of th.getAttributeNames()) cell.setAttribute(a, th.getAttribute(a));
        cell.contentEditable = 'true';
        cell.style.fontWeight = '600';
        cell.style.background = '#f8fafc';
        th.replaceWith(cell);
      }
    } else {
      const headRow = tbl.tHead.rows[0];
      const bodyRow = tbl.tBodies[0].insertRow(0);
      for (const th of [...headRow.cells]) {
        const td = document.createElement('td');
        while (th.firstChild) td.appendChild(th.firstChild);
        for (const a of th.getAttributeNames()) td.setAttribute(a, th.getAttribute(a));
        td.contentEditable = 'true';
        td.innerHTML = '<p><br></p>';
        bodyRow.appendChild(td);
      }
      tbl.tHead.remove();
    }
    rebuildGrips(true);
  }

  // Toolbar actions
  bar.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-act]');
    if (!btn) return;
    const act = btn.dataset.act;
    if (act === 'row-above') insertRow('above');
    else if (act === 'row-below') insertRow('below');
    else if (act === 'col-left') insertCol('left');
    else if (act === 'col-right') insertCol('right');
    else if (act === 'del-row') deleteRow();
    else if (act === 'del-col') deleteCol();
    else if (act === 'del-cell-local') {
      const cell = getActiveCell();
      if (cell) deleteCellLocal(cell);
    }
    else if (act === 'even-cols') evenColumns();
    else if (act === 'toggle-head') toggleHeaderRow();
    else if (act === 'split-cell') {
      const cell = getActiveCell();
      if (cell) openSplitDialog(cell);
    }
  });

  // Keep toolbar visible when inside the table
  tbl.addEventListener('focusin', (e) => {
    if (e.target && (e.target.tagName === 'TD' || e.target.tagName === 'TH')) {
      currentBlockBody = e.target;
      el.classList.add('mc-table-active');
    }
  });
  tbl.addEventListener('focusout', (e) => {
    if (currentBlockBody === e.target) currentBlockBody = null;
    setTimeout(() => { if (!el.contains(document.activeElement)) el.classList.remove('mc-table-active'); }, 0);
  });

  // ===== Resizing grips / autosize =====
  function rebuildGrips(preserveWidth) {
    // autosize block height & push siblings
    const rowsForHeight = Math.max(2, Math.ceil(tbl.offsetHeight / GRID));
    el.style.height = `${rowsForHeight * GRID}px`;
    el.dataset.rows = String(rowsForHeight);
    const overlay = el.closest('.mc-block-overlay');
    if (overlay) pushDownFrom(el, overlay);

    if (!preserveWidth) evenColumns(); // normalize on first build

    gripsX.innerHTML = '';
    gripsY.innerHTML = '';

    const rect = tbl.getBoundingClientRect();
    const blockRect = el.getBoundingClientRect();

    // ---- Column grips (resize <colgroup>) ----
    const colsCount = gridColCount();
    if (colsCount > 1) {
      const percents = readColPercents();
      const cum = [];
      let acc = 0;
      for (let i = 0; i < colsCount - 1; i++) {
        acc += percents[i];
        cum.push((acc / 100) * rect.width);
      }
      for (let i = 0; i < cum.length; i++) {
        const x = cum[i];
        const g = document.createElement('div');
        g.className = 'mc-grip-x';
        g.style.left = `${rect.left - blockRect.left + x - 3}px`;
        g.style.top = `${rect.top - blockRect.top}px`;
        g.style.height = `${rect.height}px`;
        gripsX.appendChild(g);

        g.addEventListener('mousedown', (md) => {
          md.preventDefault();
          const startX = md.clientX;
          const startPerc = readColPercents();
          const minPx = 40;
          const minPct = (minPx / rect.width) * 100;

          function onMove(mm) {
            const dxPx = mm.clientX - startX;
            const dxPct = (dxPx / rect.width) * 100;
            const left0  = startPerc[i];
            const right0 = startPerc[i + 1];

            let left  = clamp(left0  + dxPct, minPct, 100 - minPct);
            let right = clamp(right0 - dxPct, minPct, 100 - minPct);

            const totalPair = left0 + right0;
            if (Math.abs((left + right) - totalPair) > 0.0001) {
              if (left === minPct) right = totalPair - left;
              else if (right === minPct) left = totalPair - right;
            }

            const next = startPerc.slice();
            next[i]     = left;
            next[i + 1] = right;
            writeColPercents(next);
            rebuildGrips(true);
          }
          function onUp() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
          }
          document.addEventListener('mousemove', onMove);
          document.addEventListener('mouseup', onUp);
        });
      }
    }

    // ---- Row grips (heights) ----
    const rows = tbl.rows.length;
    if (rows > 0) {
      for (let r = 0; r < rows - 1; r++) {
        const rr = tbl.rows[r].getBoundingClientRect();
        const y = rr.bottom - blockRect.top;
        const g = document.createElement('div');
        g.className = 'mc-grip-y';
        g.style.top = `${y - 3}px`;
        g.style.left = `${rect.left - blockRect.left}px`;
        g.style.width = `${rect.width}px`;
        gripsY.appendChild(g);

        g.addEventListener('mousedown', (md) => {
          md.preventDefault();
          const startY = md.clientY;
          const hTop0 = tbl.rows[r].getBoundingClientRect().height;
          const hBot0 = tbl.rows[r + 1].getBoundingClientRect().height;

          function onMove(mm) {
            const dy = mm.clientY - startY;
            const hTop = Math.max(28, hTop0 + dy);
            const hBot = Math.max(28, hBot0 - dy);
            for (const cell of tbl.rows[r].cells) { cell.style.height = hTop + 'px'; }
            for (const cell of tbl.rows[r + 1].cells) { cell.style.height = hBot + 'px'; }
            rebuildGrips(true);
          }
          function onUp() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
          }
          document.addEventListener('mousemove', onMove);
          document.addEventListener('mouseup', onUp);
        });
      }
    }
  }

  // initial size & grips
  requestAnimationFrame(() => {
    evenColumns();               // nice, even starting widths
    rebuildGrips(true);
  });
  tbl.addEventListener('input', () => requestAnimationFrame(() => rebuildGrips(true)));
  window.addEventListener('resize', () => requestAnimationFrame(() => rebuildGrips(true)));

  // Focus tracking so topbar styles cell text
  tbl.addEventListener('focusin', (e) => {
    if (e.target && (e.target.tagName === 'TD' || e.target.tagName === 'TH')) currentBlockBody = e.target;
  });
  tbl.addEventListener('focusout', (e) => {
    if (currentBlockBody === e.target) currentBlockBody = null;
  });

  return el;
}


  function makeSignatureRow() {
    const el = document.createElement('div');
    frameBlock(el);

    const row = document.createElement('div');
    Object.assign(row.style, {
      display: 'grid',
      gridTemplateColumns: 'repeat(4, 1fr)',
      gap: '12px',
      alignItems: 'start',
    });

    for (let i = 0; i < 4; i++) {
      const cell = document.createElement('div');
      Object.assign(cell.style, {
        border: '1px dashed #cbd5e1',
        borderRadius: '6px',
        padding: '8px',
        display: 'grid',
        gap: '6px',
      });

      // --- signature image upload box (unchanged) ---
      const imgWrap = document.createElement('div');
      Object.assign(imgWrap.style, {
        aspectRatio: '4/3',
        background: '#f8fafc',
        borderRadius: '4px',
        display: 'grid',
        placeItems: 'center',
        overflow: 'hidden',
      });

      const img = document.createElement('img');
      Object.assign(img.style, { display: 'none', width: '100%', height: '100%', objectFit: 'contain' });

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = 'Upload';
      Object.assign(btn.style, {
        border: '1px solid #e5e7eb',
        background: '#fff',
        padding: '4px 8px',
        borderRadius: '4px',
        cursor: 'pointer',
      });

      const inputFile = document.createElement('input');
      inputFile.type = 'file';
      inputFile.accept = 'image/*';
      inputFile.style.display = 'none';

      btn.onclick = () => inputFile.click();
      inputFile.onchange = () => {
        if (inputFile.files[0]) {
          const reader = new FileReader();
          reader.onload = () => { img.src = reader.result; img.style.display = 'block'; btn.style.display = 'none'; };
          reader.readAsDataURL(inputFile.files[0]);
        }
      };

      imgWrap.appendChild(img);
      imgWrap.appendChild(btn);
      cell.appendChild(imgWrap);

      const line = document.createElement('div');
      Object.assign(line.style, { borderBottom: '1px solid #9ca3af', marginTop: '4px' });
      cell.appendChild(line);

      // --- labels row: Name / Date / Role ---
      ['Name', 'Date', 'Role'].forEach((t) => {
        if (t === 'Date') {
          // STRICT date-only field
          const dateWrap = document.createElement('div');
          Object.assign(dateWrap.style, { display: 'flex', alignItems: 'center', gap: '6px' });

          const dateInput = document.createElement('input');
          dateInput.type = 'date';
          dateInput.placeholder = 'YYYY-MM-DD';
          dateInput.title = 'Enter date (YYYY-MM-DD)';
          Object.assign(dateInput.style, {
            width: '100%',
            padding: '4px 6px',
            border: '1px solid #cbd5e1',
            borderRadius: '6px',
            fontSize: '12px',
            color: '#111827',
            background: '#fff',
          });

          // focus tracking (don’t route toolbar actions here)
          dateInput.addEventListener('focusin', () => { currentBlockBody = null; });
          dateInput.addEventListener('focusout', () => { /* no-op */ });

          // Fallback hardening: block letters even on browsers that allow free typing
          dateInput.addEventListener('keydown', (e) => {
            const k = e.key;
            const ctrlCombo = e.ctrlKey || e.metaKey; // allow copy/paste, select-all, etc.
            const allowed =
              ctrlCombo ||
              ['Backspace','Delete','Tab','Enter','Escape','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'].includes(k) ||
              // digits and separators
              /^[0-9]$/.test(k) || k === '-' || k === '/';
            if (!allowed) e.preventDefault();
          });

          // Normalize input to YYYY-MM-DD; strip letters if pasted
          dateInput.addEventListener('input', () => {
            let v = dateInput.value.replace(/[^\d/-]/g, '');
            // If user typed with slashes, convert to dashes
            v = v.replaceAll('/', '-');
            dateInput.value = v;
          });

          // On blur, attempt to coerce e.g. 9-5-2025 or 09-05-25 into YYYY-MM-DD
          dateInput.addEventListener('blur', () => {
            const v = dateInput.value.trim();
            if (!v) return;
            // Already good? (YYYY-MM-DD)
            if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return;

            // Try to parse tolerant formats
            const parts = v.split('-').map(s => s.trim());
            if (parts.length === 3) {
              let [a,b,c] = parts;
              // Guess formats: YYYY-M-D or M-D-YYYY or D-M-YYYY
              if (a.length === 4) { // YYYY-M-D
                const yyyy = a;
                const mm = String(b).padStart(2, '0');
                const dd = String(c).padStart(2, '0');
                if (isValidYMD(yyyy, mm, dd)) dateInput.value = `${yyyy}-${mm}-${dd}`;
              } else if (c.length === 4) { // M-D-YYYY (US-ish)
                const yyyy = c;
                const mm = String(a).padStart(2, '0');
                const dd = String(b).padStart(2, '0');
                if (isValidYMD(yyyy, mm, dd)) dateInput.value = `${yyyy}-${mm}-${dd}`;
              }
            }
            // Final guard: if still invalid, clear
            if (!/^\d{4}-\d{2}-\d{2}$/.test(dateInput.value)) {
              dateInput.value = '';
            }
          });

          // simple validator
          function isValidYMD(y, m, d) {
            const yyyy = +y, mm = +m, dd = +d;
            if (!yyyy || mm < 1 || mm > 12 || dd < 1 || dd > 31) return false;
            const dt = new Date(`${y}-${m}-${d}T00:00:00`);
            return !Number.isNaN(dt.getTime()) &&
                  dt.getUTCFullYear() === yyyy &&
                  dt.getUTCMonth() + 1 === mm &&
                  dt.getUTCDate() === dd;
          }

          // Optional: set today
          // dateInput.valueAsDate = new Date();

          // small label to the right (muted)
          const hint = document.createElement('span');
          hint.textContent = 'Date';
          Object.assign(hint.style, { fontSize: '12px', color: '#64748b', whiteSpace: 'nowrap' });

          dateWrap.appendChild(dateInput);
          dateWrap.appendChild(hint);
          cell.appendChild(dateWrap);
        } else {
          // Name / Role remain contentEditable
          const lab = document.createElement('div');
          lab.textContent = t;
          lab.contentEditable = 'true';
          Object.assign(lab.style, { fontSize: '12px', color: '#64748b', whiteSpace: 'nowrap', outline: 'none' });
          lab.addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
          lab.addEventListener('focusin', () => { currentBlockBody = lab; });
          lab.addEventListener('focusout', () => { if (currentBlockBody === lab) currentBlockBody = null; });
          cell.appendChild(lab);
        }
      });

      row.appendChild(cell);
    }

    el.appendChild(row);

    requestAnimationFrame(() => {
      const rows = Math.max(2, Math.ceil(el.scrollHeight / GRID));
      el.style.height = `${rows * GRID}px`;
      el.dataset.rows = String(rows);
      const overlay = el.closest('.mc-block-overlay');
      if (overlay) pushDownFrom(el, overlay);
    });

    return el;
  }


  const FACTORY = {
    label: makeLabel,
    paragraph: makeParagraph,
    textField: makeTextField,
    text: makeTextField,
    textarea: makeTextArea,
    table: makeTable,
    signature: makeSignatureRow,
  };

  function registerBlockBody(bodyEl) {
    bodyEl.addEventListener('focusin', () => {
      currentBlockBody = bodyEl;
      // defer so the caret has landed
      setTimeout(() => saveSelection(bodyEl), 0);
    });
    bodyEl.addEventListener('mousedown', () => {
      currentBlockBody = bodyEl;
      // caret will update on mouseup
    });
    bodyEl.addEventListener('mouseup', () => saveSelection(bodyEl));
    bodyEl.addEventListener('keyup',  () => saveSelection(bodyEl));
    bodyEl.addEventListener('focusout', () => {
      if (currentBlockBody === bodyEl) currentBlockBody = null;
      selectionStore.delete(bodyEl);
      document.addEventListener('selectionchange', () => {
      if (currentBlockBody) saveSelection(currentBlockBody);
    });

    });
  }


  // ---------- Drag / stack logic ----------
  function pushDownFrom(source, overlay) {
    const blocks = Array.from(overlay.querySelectorAll('.mc-block'))
      .filter((b) => b !== source)
      .sort((a, b) => (parseInt(a.style.top || 0, 10) - parseInt(b.style.top || 0, 10)));
    const srcTop = parseInt(source.style.top || 0, 10);
    const srcBottom = srcTop + source.offsetHeight;
    let cursor = srcBottom;
    for (const blk of blocks) {
      let top = parseInt(blk.style.top || 0, 10);
      const h = blk.offsetHeight;
      const bottom = top + h;
      const overlaps = top < cursor && bottom > srcTop;
      if (overlaps) {
        top = snap(cursor);
        blk.style.top = `${top}px`;
        cursor = top + h;
      } else {
        cursor = Math.max(cursor, bottom);
      }
    }
  }
  function reflowStack(overlay) {
    const items = Array.from(overlay.querySelectorAll('.mc-block'))
      .sort((a, b) => (parseInt(a.style.top || 0, 10) - parseInt(b.style.top || 0, 10)));
    let cursor = PAGE_PADDING_TOP;
    for (const blk of items) {
      let top = parseInt(blk.style.top || 0, 10);
      if (top < cursor) {
        top = snap(cursor);
        blk.style.top = `${top}px`;
      }
      cursor = top + blk.offsetHeight;
    }
  }
  function makeDraggable(block, overlay) {
    const grip = block.querySelector('.drag-handle');
    if (!grip) return;
    let ghost;
    const startDrag = (e) => {
      e.preventDefault();
      const startRect = block.getBoundingClientRect();
      const ovRect = overlay.getBoundingClientRect();
      const offsetY = e.clientY - startRect.top;
      ghost = overlay.querySelector('.mc-ghost-line');
      if (!ghost) {
        ghost = document.createElement('div');
        ghost.className = 'mc-ghost-line';
        Object.assign(ghost.style, {
          position: 'absolute',
          left: '0',
          right: '0',
          height: '2px',
          background: 'rgba(123,15,20,.35)',
          pointerEvents: 'none',
        });
        overlay.appendChild(ghost);
      }
      const onMove = (mv) => {
        const proposed = mv.clientY - ovRect.top - offsetY;
        const snapped = snap(Math.max(PAGE_PADDING_TOP, proposed));
        ghost.style.top = `${snapped}px`;
        ghost.style.display = 'block';
      };
      const onUp = () => {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
        const top = parseInt(ghost.style.top || '0', 10) || PAGE_PADDING_TOP;
        ghost.style.display = 'none';
        block.style.top = `${top}px`;
        pushDownFrom(block, overlay);
      };
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    };
    grip.addEventListener('mousedown', startDrag);
  }

  function wireDropTargets() {
    const pages = document.querySelectorAll('.page');
    if (!pages.length) return;
    pages.forEach((page) => {
      const overlay = ensureOverlay(page);
      ['dragenter', 'dragover'].forEach((evt) => {
        overlay.addEventListener(evt, (ev) => {
          if (ev.dataTransfer?.types?.includes('application/x-mc')) ev.preventDefault();
        });
      });
      overlay.addEventListener('drop', (ev) => {
        ev.preventDefault();
        const raw = ev.dataTransfer.getData('application/x-mc');
        if (!raw) return;
        const { type } = JSON.parse(raw);
        const factory = FACTORY[type];
        if (!factory) return;
        const block = factory();
        const y = snap(ev.offsetY);
        block.style.top = `${Math.max(PAGE_PADDING_TOP, y)}px`;
        overlay.appendChild(block);
        makeDraggable(block, overlay);
        pushDownFrom(block, overlay);
        setOverlaysDragEnabled(false);
      });
    });
  }

  function wireSidebarDrag() {
    document.querySelectorAll('#mc-sidebar .sb-item').forEach((btn) => {
      if (!btn.hasAttribute('draggable')) btn.setAttribute('draggable', 'true');
      btn.addEventListener('dragstart', (e) => {
        const type = btn.dataset.type || '';
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('application/x-mc', JSON.stringify({ type }));
        setOverlaysDragEnabled(true);
      });
      btn.addEventListener('dragend', () => {
        setOverlaysDragEnabled(false);
      });
    });
  }

  function execOnBlockOrEditor(editor, fnForEditor, fallback /* string or function */) {
    if (currentBlockBody && currentBlockBody.isContentEditable !== false) {
      // put the caret back where the user left it
      restoreSelection(currentBlockBody);

      if (typeof fallback === 'function') {
        fallback();
      } else if (typeof fallback === 'string') {
        document.execCommand(fallback, false, null);
      }

      currentBlockBody.focus();
      saveSelection(currentBlockBody);
    } else {
      fnForEditor(editor);
    }
  }

 
    function wireTopbar(editor) {
      // When the TipTap editor gains focus, clear block selection
      const tiptapEl = document.querySelector('.tiptap');
      tiptapEl?.addEventListener('focusin', () => { currentBlockBody = null; });

      // ✅ get toolbar first
      const toolbar = document.getElementById('tt-toolbar');
      if (!toolbar) return;

      // ✅ keep focus in the current contentEditable BEFORE Bootstrap dropdown handles the click
      const keepFocus = (e) => {
        // limit to toolbar actions that can steal focus, esp. color menu
        const el = e.target.closest(
          '.dropdown-item[data-action="setColor"], .dropdown-item[data-action="pickColor"]'
        );
        if (!el) return;
        e.preventDefault();
        if (currentBlockBody) restoreSelection(currentBlockBody);
      };
      toolbar.addEventListener('pointerdown', keepFocus);
      toolbar.addEventListener('mousedown', keepFocus);

      // Keep caret in the block when pressing toolbar buttons (general case)
      toolbar.addEventListener('mousedown', (e) => {
        e.preventDefault();
        if (currentBlockBody) restoreSelection(currentBlockBody);
      });


      toolbar.addEventListener('click', (e) => {
        const el = e.target.closest('[data-action]');
        if (!el) return;
        const action = el.dataset.action;
        const level = +el.dataset.level || undefined;

        if (action === 'pickColor') {
          const hidden = document.getElementById('ctl-color-hidden');
          if (!hidden) return;

          if (currentBlockBody) saveSelection(currentBlockBody);
          pickingColor = true; // 🔒 lock while the native dialog is open

          hidden.click();
          return; // actual apply happens in the input/change handlers below
        }


      

            // === Line height ===
      if (action === 'setLineHeight') {
        let lh = el.dataset.lh || '';
        if (lh === 'custom') {
          const v = prompt('Enter line spacing (e.g., 1, 1.15, 1.5, 2, or CSS like "24px")', '1.5');
          if (v === null) return;          // cancelled
          lh = v.trim();
          if (!lh) return;                  // empty => do nothing
        }
        editor.chain().focus().setLineHeight(lh).run();
        return;
      }
      if (action === 'unsetLineHeight') {
        editor.chain().focus().unsetLineHeight().run();
        return;
      }

      switch (action) {
        case 'toggleBold':
          execOnBlockOrEditor(
            editor,
            (ed) => ed.chain().focus().toggleBold().run(),
            'bold'
          );
          break;
        case 'toggleItalic':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().toggleItalic().run(), 'italic');
          break;
        case 'toggleUnderline':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().toggleUnderline().run(), 'underline');
          break;
        case 'toggleStrike':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().toggleStrike().run(), () => document.execCommand('strikethrough'));
          break;

        case 'setParagraph':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().setParagraph().run(), () => document.execCommand('formatBlock', false, 'P'));
          break;
        case 'setHeading':
          execOnBlockOrEditor(
            editor,
            (ed) => ed.chain().focus().toggleHeading({ level }).run(),
            () => document.execCommand('formatBlock', false, 'H' + (level || 1))
          );
          break;

        case 'toggleBulletList':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().toggleBulletList().run(), 'insertUnorderedList');
          break;
        case 'toggleOrderedList':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().toggleOrderedList().run(), 'insertOrderedList');
          break;

        case 'toggleBlockquote':
          execOnBlockOrEditor(
            editor,
            (ed) => ed.chain().focus().toggleBlockquote().run(),
            () => document.execCommand('formatBlock', false, 'BLOCKQUOTE')
          );
          break;
        case 'toggleCodeBlock':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().toggleCodeBlock().run(), null);
          break;

        case 'alignLeft':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().setTextAlign('left').run(), 'justifyLeft');
          break;
        case 'alignCenter':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().setTextAlign('center').run(), 'justifyCenter');
          break;
        case 'alignRight':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().setTextAlign('right').run(), 'justifyRight');
          break;
        case 'alignJustify':
          execOnBlockOrEditor(editor, (ed) => ed.chain().focus().setTextAlign('justify').run(), 'justifyFull');
          break;

        case 'toggleSuperscript':
          execOnBlockOrEditor(
            editor,
            (ed) => ed.chain().focus().toggleSuperscript().run(),
            () => document.execCommand('superscript')
          );
          break;

        case 'toggleSubscript':
          execOnBlockOrEditor(
            editor,
            (ed) => ed.chain().focus().toggleSubscript().run(),
            () => document.execCommand('subscript')
          );
          break;

        case 'toggleTaskList':
          execOnBlockOrEditor(
            editor,
            (ed) => ed.chain().focus().toggleTaskList().run(),
            // blocks don’t have native checklist support, so fallback = unordered list
            'insertUnorderedList'
          );
          break;

        case 'setColor': {
          const value = el.dataset.value || null;

          if (currentBlockBody) {
            // snapshot before dropdown closes
            saveSelection(currentBlockBody);
            restoreSelection(currentBlockBody);
          }

          execOnBlockOrEditor(
            editor,
            (ed) => {
              if (value) ed.chain().focus().setColor(value).run();
              else       ed.chain().focus().unsetColor().run();
            },
            () => {
              if (value) wrapSelectionWithSpan(`color:${value}`);
              else       wrapSelectionWithSpan('color:inherit');
            }
          );

          if (currentBlockBody) {
            currentBlockBody.focus();
            saveSelection(currentBlockBody);
          }
          break;
        }


        
        case 'setHorizontalRule':
          editor.chain().focus().setHorizontalRule().run();
          break;

        case 'insertImage': {
          const url = prompt('Image URL');
          if (!url) return;
          execOnBlockOrEditor(
            editor,
            (ed) => (ed.chain().focus().setImage?.({ src: url }).run() || ed.chain().focus().insertContent(`<img src="${url}" alt="">`).run()),
            () => {
              // insert into block at caret
              document.execCommand('insertImage', false, url);
            }
          );
          break;
        }

        case 'setLink': {
          const prev = editor.getAttributes('link')?.href || '';
          const url = prompt('Enter URL', prev);
          if (url === null) return;
          if (currentBlockBody) {
            if (url === '') document.execCommand('unlink');
            else document.execCommand('createLink', false, url);
            currentBlockBody.focus();
          } else {
            if (url === '') editor.chain().focus().unsetLink().run();
            else editor.chain().focus().setLink({ href: url }).run();
          }
          break;
        }
        case 'unsetLink':
          if (currentBlockBody) {
            document.execCommand('unlink');
            currentBlockBody.focus();
          } else {
            editor.chain().focus().unsetLink().run();
          }
          break;

        case 'undo':
          if (currentBlockBody) document.execCommand('undo'); else editor.commands.undo();
          break;
        case 'redo':
          if (currentBlockBody) document.execCommand('redo'); else editor.commands.redo();
          break;

        default:
          // no-op for unknown buttons
          break;
      }
    });

      const hiddenColor = document.getElementById('ctl-color-hidden');
      hiddenColor?.addEventListener('input', () => {
        const value = hiddenColor.value; // e.g. "#ff0000"

        if (currentBlockBody) restoreSelection(currentBlockBody);

        execOnBlockOrEditor(
          editor,
          (ed) => (value ? ed.chain().focus().setColor(value).run()
                        : ed.chain().focus().unsetColor().run()),
          () => {
            if (value) wrapSelectionWithSpan(`color:${value}`);
            else       wrapSelectionWithSpan('color:inherit');
          }
        );

        if (currentBlockBody) {
          currentBlockBody.focus();
          saveSelection(currentBlockBody);
        }

        // 🔓 release after the browser finishes focus gymnastics
        setTimeout(() => { pickingColor = false; }, 0);
      });

      // Some browsers only fire 'change' after the dialog closes – also unlock there
      hiddenColor?.addEventListener('change', () => {
        setTimeout(() => { pickingColor = false; }, 0);
      });



    // Font / size / color controls if present
    const selFont  = document.getElementById('ctl-font');
    const selSize  = document.getElementById('ctl-size');
    const inpColor = document.getElementById('ctl-color');
    const clrColor = document.getElementById('ctl-color-clear');

function applyFontFamily(value) {
  if (currentBlockBody) {
    if (value) wrapSelectionWithSpan(`font-family:${value}`);
    else wrapSelectionWithSpan(`font-family:inherit`);
  } else {
    const c = editor.chain().focus();
    value ? c.setFontFamily?.(value).run()
          : c.unsetFontFamily?.().run();
  }
}

    function applyFontSize(value) {
      if (currentBlockBody) {
        if (value) wrapSelectionWithSpan(`font-size:${value}`);
        else wrapSelectionWithSpan(`font-size:inherit`);
      } else {
        const c = editor.chain().focus();
        value ? c.setMark('textStyle', { fontSize: value }).run()
              : c.setMark('textStyle', { fontSize: null }).run();
      }
    }

    function applyColor(value) {
      if (currentBlockBody) {
        if (value) {
          withRestoredSelection(() => document.execCommand('foreColor', false, value));
        } else {
          // Clear color by wrapping with inherit (fallback)
          wrapSelectionWithSpan('color:inherit');
        }
      } else {
        const c = editor.chain().focus();
        value ? c.setColor?.(value).run()
              : c.unsetColor?.().run();
      }
    }
    selFont?.addEventListener('change',  () => applyFontFamily(selFont.value));
    selSize?.addEventListener('change',  () => applyFontSize(selSize.value));
    inpColor?.addEventListener('input',  () => applyColor(inpColor.value));
    clrColor?.addEventListener('click',  () => applyColor(null));
  }
  // ========= END TOP BAR WIRING =========

  function wireDropTargets() {
    const pages = document.querySelectorAll('.page');
    if (!pages.length) return;
    pages.forEach((page) => {
      const overlay = ensureOverlay(page);
      ['dragenter', 'dragover'].forEach((evt) => {
        overlay.addEventListener(evt, (ev) => {
          if (ev.dataTransfer?.types?.includes('application/x-mc')) ev.preventDefault();
        });
      });
      overlay.addEventListener('drop', (ev) => {
        ev.preventDefault();
        const raw = ev.dataTransfer.getData('application/x-mc');
        if (!raw) return;
        const { type } = JSON.parse(raw);
        const factory = FACTORY[type];
        if (!factory) return;
        const block = factory();
        const y = snap(ev.offsetY);
        block.style.top = `${Math.max(PAGE_PADDING_TOP, y)}px`;
        overlay.appendChild(block);
        makeDraggable(block, overlay);
        pushDownFrom(block, overlay);
        setOverlaysDragEnabled(false);
      });
    });
  }

  function wireSidebarDrag() {
    document.querySelectorAll('#mc-sidebar .sb-item').forEach((btn) => {
      if (!btn.hasAttribute('draggable')) btn.setAttribute('draggable', 'true');
      btn.addEventListener('dragstart', (e) => {
        const type = btn.dataset.type || '';
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('application/x-mc', JSON.stringify({ type }));
        setOverlaysDragEnabled(true);
      });
      btn.addEventListener('dragend', () => {
        setOverlaysDragEnabled(false);
      });
    });
  }

      // ---------- Boot ----------
      waitForEditor().then((editor) => {
        wireSidebarDrag();
        wireDropTargets();
        wireTopbar(editor); // <— restore toolbar + make it affect sidebar text too

      // expose a rewire hook so new pages can be made drop targets
  window.__mc = window.__mc || {};
  window.__mc.rewireDropTargets = () => {
    // re-run the internal drop-target wiring for any pages that don't have an overlay yet
    document.querySelectorAll('.page').forEach((page) => {
      if (!page.querySelector('.mc-block-overlay')) {
        // create overlay identical to initial wiring
        const overlay = document.createElement('div');
        overlay.className = 'mc-block-overlay';
        Object.assign(overlay.style, {
          position: 'absolute',
          inset: '0',
          pointerEvents: 'none',
          paddingTop: '10px',
        });
        if (getComputedStyle(page).position === 'static') page.style.position = 'relative';
        page.appendChild(overlay);
      }
    });

    // let TipTapTest’s original listeners attach (they’re bound on `.mc-block-overlay`)
    const evt = new Event('mc:rewire');
    document.dispatchEvent(evt);
  };



  });
})();

