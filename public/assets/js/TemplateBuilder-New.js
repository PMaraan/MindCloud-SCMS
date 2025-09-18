// /public/assets/js/TemplateBuilder-New.js
// Sidebar blocks that stack/drag/snap like TemplateBuilder,
// and the top maroon toolbar also drives TipTap OR focused block text.
(function () {
  const GRID = 20;
  const PAGE_PADDING_TOP = 10;
  const snap = (v) => Math.round(v / GRID) * GRID;
  
  // --- flowing signature table (used when dropping "Signature Field" into TipTap)
// signatureTableHTML() – used when dropping "Signature Field" into TipTap
// --- flowing signature table (used when dropping "Signature Field" into TipTap)
// Now: just a plain 4x4 table with empty cells
// Plain 4×4 table, flagged as a "signature" table
// Plain 4×4 table, flagged as a "signature" table
// 4×4 signature table; top row = upload widgets
function signatureTableHTML() {
  const rows = 4, cols = 4;
  let html = '<table class="sig-table" data-sig="1"><tbody>';

  for (let r = 0; r < rows; r++) {
    html += '<tr>';
    for (let c = 0; c < cols; c++) {
      if (r === 0) {
        html += '<td><upload-box></upload-box></td>';
      } else if (r === 1) {
        html += '<td data-ph="Name"><p data-ph="Name">Name</p></td>';
      } else if (r === 2) {
        html += '<td data-ph="Date"><p><date-input></date-input></p></td>';
      } else {
        html += '<td data-ph="Role"><p data-ph="Role">Role</p></td>';
      }
    }
    html += '</tr>';
  }

  html += '</tbody></table><p></p>';
  return html;
}


  const waitForEditor = () =>
    new Promise((resolve) => {
      const get = () => window.__mc?.getActiveEditor?.();
      const ed = get();
      if (ed) return resolve(ed);
      const iv = setInterval(() => {
        const ed2 = get();
        if (ed2) {
          clearInterval(iv);
          resolve(ed2);
        }
      }, 20);
    });

  // Track which sidebar block body is focused
  let currentBlockBody = null;
  let pickingColor = false; // guards focus while native color dialog is open

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
    saveSelection(currentBlockBody);
  }
  function wrapSelectionWithSpan(styleText) {
    withRestoredSelection(() => {
      const sel = window.getSelection();
      if (!sel || sel.rangeCount === 0) return;
      const range = sel.getRangeAt(0);
      if (range.collapsed) {
        const span = document.createElement('span');
        span.setAttribute('style', styleText);
        span.appendChild(document.createTextNode('\u200b')); // ZWSP
        range.insertNode(span);
        const newRange = document.createRange();
        newRange.setStart(span.firstChild, span.firstChild.length);
        newRange.collapse(true);
        sel.removeAllRanges();
        sel.addRange(newRange);
        return;
      }
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
        pointerEvents: 'none', // don’t block clicks by default
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
  function registerBlockBody(bodyEl) {
    bodyEl.addEventListener('focusin', () => {
      currentBlockBody = bodyEl;
      setTimeout(() => saveSelection(bodyEl), 0);
    });
    bodyEl.addEventListener('mousedown', () => {
      currentBlockBody = bodyEl;
    });
    bodyEl.addEventListener('mouseup', () => saveSelection(bodyEl));
    bodyEl.addEventListener('keyup', () => saveSelection(bodyEl));
    bodyEl.addEventListener('focusout', () => {
      if (currentBlockBody === bodyEl) currentBlockBody = null;
      selectionStore.delete(bodyEl);
      document.addEventListener('selectionchange', () => {
        if (currentBlockBody) saveSelection(currentBlockBody);
      });
    });
  }

  function makeTextField() {
    const el = document.createElement('div');
    frameBlock(el);
    el.classList.add('mc-textfield');
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
      color: 'inherit',
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
    el.classList.add('mc-label');
    const body = document.createElement('div');
    body.className = 'element-body';
    body.contentEditable = 'true';
    Object.assign(body.style, {
      outline: 'none',
      whiteSpace: 'nowrap',
      padding: '2px 0',
      fontWeight: '600',
      font: 'inherit',
      color: 'inherit',
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
    el.classList.add('mc-paragraph');
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
      color: 'inherit',
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
      color: 'inherit',
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
      Object.assign(img.style, {
        display: 'none',
        width: '100%',
        height: '100%',
        objectFit: 'contain'
      });
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
          reader.onload = () => {
            img.src = reader.result;
            img.style.display = 'block';
            btn.style.display = 'none';
          };
          reader.readAsDataURL(inputFile.files[0]);
        }
      };
      imgWrap.appendChild(img);
      imgWrap.appendChild(btn);
      cell.appendChild(imgWrap);

      const line = document.createElement('div');
      Object.assign(line.style, { borderBottom: '1px solid #9ca3af', marginTop: '4px' });
      cell.appendChild(line);

      ['Name', 'Date', 'Role'].forEach((t) => {
        if (t === 'Date') {
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
          dateInput.addEventListener('focusin', () => { currentBlockBody = null; });
          dateInput.addEventListener('keydown', (e) => {
            const k = e.key;
            const ctrlCombo = e.ctrlKey || e.metaKey;
            const allowed = ctrlCombo
              || ['Backspace','Delete','Tab','Enter','Escape','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'].includes(k)
              || /^[0-9]$/.test(k) || k === '-' || k === '/';
            if (!allowed) e.preventDefault();
          });
          dateInput.addEventListener('input', () => {
            let v = dateInput.value.replace(/[^\d/-]/g, '');
            v = v.replaceAll('/', '-');
            dateInput.value = v;
          });
          dateInput.addEventListener('blur', () => {
            const v = dateInput.value.trim();
            if (!v) return;
            if (/^\d{4}-\d{2}-\d{2}$/.test(v)) return;
            const parts = v.split('-').map(s => s.trim());
            if (parts.length === 3) {
              let [a, b, c] = parts;
              if (a.length === 4) { // YYYY-M-D
                const yyyy = a;
                const mm = String(b).padStart(2, '0');
                const dd = String(c).padStart(2, '0');
                if (isValidYMD(yyyy, mm, dd)) dateInput.value = `${yyyy}-${mm}-${dd}`;
              } else if (c.length === 4) { // M-D-YYYY
                const yyyy = c;
                const mm = String(a).padStart(2, '0');
                const dd = String(b).padStart(2, '0');
                if (isValidYMD(yyyy, mm, dd)) dateInput.value = `${yyyy}-${mm}-${dd}`;
              }
            }
            if (!/^\d{4}-\d{2}-\d{2}$/.test(dateInput.value)) {
              dateInput.value = '';
            }
          });
          function isValidYMD(y, m, d) {
            const yyyy = +y, mm = +m, dd = +d;
            if (!yyyy || mm < 1 || mm > 12 || dd < 1 || dd > 31) return false;
            const dt = new Date(`${y}-${m}-${d}T00:00:00`);
            return !Number.isNaN(dt.getTime()) &&
                   dt.getUTCFullYear() === yyyy &&
                   dt.getUTCMonth() + 1 === mm &&
                   dt.getUTCDate() === dd;
          }
          const hint = document.createElement('span');
          hint.textContent = 'Date';
          Object.assign(hint.style, { fontSize: '12px', color: '#64748b', whiteSpace: 'nowrap' });
          dateWrap.appendChild(dateInput);
          dateWrap.appendChild(hint);
          cell.appendChild(dateWrap);
        } else {
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
    if (overlay.dataset.dropWired === '1') return; // already wired
    overlay.dataset.dropWired = '1';

    // Always allow drag-over so custom data types are exposed on drop
    ['dragenter', 'dragover'].forEach((evt) => {
      overlay.addEventListener(evt, (ev) => ev.preventDefault());
    });

    overlay.addEventListener('drop', (ev) => {
      ev.preventDefault();

      const raw = ev.dataTransfer?.getData('application/x-mc');
      if (!raw) return;

      let type = '';
      try { ({ type } = JSON.parse(raw) || {}); } catch { return; }
      if (!type) return;

      // 🔑 Resolve the TipTap editor that belongs to THIS page (not the "active" one).
      const ed = (() => {
        const pageEl = overlay.closest('.page');
        const all = window.__mc?.MCEditors?.all?.() || [];
        for (const e of all) {
          const el = e?.options?.element;
          if (pageEl && el && pageEl.contains(el)) return e;
        }
        return window.__mc?.getActiveEditor?.() || null; // fallback
      })();

      // (A) Flow-into-editor types — insert into TipTap
      if (ed) {
      if (type === 'table') {
        if (isSelectionInsideTable(ed)) {
          forceCaretOutsideTable(ed, ev); // uses drop pointer to choose above/below
        }

        // 1) add a hidden ZWSP paragraph ABOVE the table
        ed.chain().focus().insertContent('<p>\u200B</p>').run();

        const paraPos = Math.max(1, ed.state.selection.from - 1);

        // 2) insert the table
        ed.chain().focus()
          .insertTable({ rows: 3, cols: 4, withHeaderRow: false })
          .run();

        // 3) ensure caret sits in the line ABOVE the table
        putCaretAboveJustInsertedTable(ed, paraPos);

        setOverlaysDragEnabled(false);
        return;
      }
        // Simple sidebar items mapped to flowing HTML in TipTap
        if (type === 'label') {
          ed.chain().focus().insertContent('<p><strong>Label text</strong></p>').run();
          setOverlaysDragEnabled(false);
          return;
        }
        if (type === 'paragraph') {
          ed.chain().focus().insertContent('<p>Paragraph text</p>').run();
          setOverlaysDragEnabled(false);
          return;
        }
        if (type === 'textField') {
          ed.chain().focus().insertContent(
            '<p><span style="display:inline-block;min-width:240px;border-bottom:1px solid #9ca3af">&nbsp;</span></p>'
          ).run();
          setOverlaysDragEnabled(false);
          return;
        }
        if (type === 'textarea') {
          ed.chain().focus().insertContent(
            '<p style="display:block;border:1px solid #111827;border-radius:6px;padding:8px;min-height:120px;">Text block</p>'
          ).run();
          setOverlaysDragEnabled(false);
          return;
        }
        if (type === 'signature') {
          if (isSelectionInsideTable(ed)) {
            forceCaretOutsideTable(ed, ev);
          }

          const html = signatureTableHTML();
          ed.chain().focus().insertContent(html).run();

          // leave a caret after the signature table
          ed.chain().focus().insertContent('<p></p>').run();

          setOverlaysDragEnabled(false);
          return;
        }
      }

      // (B) Everything else: free-positioned overlay blocks on this page
      const factory = FACTORY?.[type];
      if (!factory) return;

      const block = factory();
      const y = Math.round(ev.offsetY / 20) * 20; // snap to GRID
      block.style.top = `${Math.max(10, y)}px`;   // 10 = PAGE_PADDING_TOP
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
      restoreSelection(currentBlockBody);
      if (typeof fallback === 'function') fallback();
      else if (typeof fallback === 'string') document.execCommand(fallback, false, null);
      currentBlockBody.focus();
      saveSelection(currentBlockBody);
    } else {
      fnForEditor(editor);
    }
  }

  function wireTopbar() {
    function getEd() {
      const el = document.activeElement;
      if (el) {
        const page = el.closest('.page');
        if (page) {
          for (const ed of (window.__mc?.MCEditors?.all?.() || [])) {
            if (page.contains(ed.options.element)) return ed;
          }
        }
      }
      return window.__mc?.getActiveEditor?.() || null;
    }

    const tiptapEl = document.querySelector('.tiptap');
    tiptapEl?.addEventListener('focusin', () => { currentBlockBody = null; });

    const toolbar = document.getElementById('tt-toolbar');
    if (!toolbar) return;

    const keepFocus = (e) => {
      const el = e.target.closest(
        '.dropdown-item[data-action="setColor"], .dropdown-item[data-action="pickColor"]'
      );
      if (!el) return;
      e.preventDefault();
      if (currentBlockBody) restoreSelection(currentBlockBody);
    };
    toolbar.addEventListener('pointerdown', keepFocus);
    toolbar.addEventListener('mousedown', keepFocus);

    toolbar.addEventListener('mousedown', (e) => {
      // allow native interaction for selects, dropdown toggles, and color inputs
      const isInteractive = e.target.closest('select, .dropdown-toggle, .dropdown-menu, input[type="color"]');
      if (isInteractive) return;

      // otherwise keep caret in the block/editor
      e.preventDefault();
      if (currentBlockBody) restoreSelection(currentBlockBody);
    });


    toolbar.addEventListener('click', (e) => {
      const el = e.target.closest('[data-action]');
      if (!el) return;
      const action = el.dataset.action;
      const level = +el.dataset.level || undefined;
      const ed = getEd();

      if (action === 'pickColor') {
        const hidden = document.getElementById('ctl-color-hidden');
        if (!hidden) return;
        if (currentBlockBody) saveSelection(currentBlockBody);
        pickingColor = true;
        hidden.click();
        return;
      }

      if (action === 'setLineHeight') {
        if (!ed) return;
        let lh = el.dataset.lh || '';
        if (lh === 'custom') {
          const v = prompt('Enter line spacing (e.g., 1, 1.15, 1.5, 2, or CSS like "24px")', '1.5');
          if (v === null) return;
          lh = v.trim();
          if (!lh) return;
        }
        ed.chain().focus().setLineHeight(lh).run();
        return;
      }
      if (action === 'unsetLineHeight') {
        if (!ed) return;
        ed.chain().focus().unsetLineHeight().run();
        return;
      }

      switch (action) {
        case 'toggleBold':
          if (!ed) return;
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleBold().run(), 'bold');
          break;
        case 'toggleItalic':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleItalic().run(), 'italic');
          break;
        case 'toggleUnderline':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleUnderline().run(), 'underline');
          break;
        case 'toggleStrike':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleStrike().run(), () => document.execCommand('strikethrough'));
          break;
        case 'setParagraph':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().setParagraph().run(), () => document.execCommand('formatBlock', false, 'P'));
          break;
        case 'setHeading':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleHeading({ level }).run(), () => document.execCommand('formatBlock', false, 'H' + (level || 1)));
          break;
        case 'toggleBulletList':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleBulletList().run(), 'insertUnorderedList');
          break;
        case 'toggleOrderedList':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleOrderedList().run(), 'insertOrderedList');
          break;
        case 'toggleBlockquote':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleBlockquote().run(), () => document.execCommand('formatBlock', false, 'BLOCKQUOTE'));
          break;
        case 'toggleCodeBlock':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleCodeBlock().run(), null);
          break;
        case 'alignLeft':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().setTextAlign('left').run(), 'justifyLeft');
          break;
        case 'alignCenter':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().setTextAlign('center').run(), 'justifyCenter');
          break;
        case 'alignRight':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().setTextAlign('right').run(), 'justifyRight');
          break;
        case 'alignJustify':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().setTextAlign('justify').run(), 'justifyFull');
          break;
        case 'toggleSuperscript':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleSuperscript().run(), () => document.execCommand('superscript'));
          break;
        case 'toggleSubscript':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleSubscript().run(), () => document.execCommand('subscript'));
          break;
        case 'toggleTaskList':
          execOnBlockOrEditor(ed, (eed) => eed.chain().focus().toggleTaskList().run(), 'insertUnorderedList');
          break;
        case 'setColor': {
          const value = el.dataset.value || null;
          if (currentBlockBody) {
            saveSelection(currentBlockBody);
            restoreSelection(currentBlockBody);
          }
          execOnBlockOrEditor(
            ed,
            (eed) => {
              if (value) eed.chain().focus().setColor(value).run();
              else eed.chain().focus().unsetColor().run();
            },
            () => { if (value) wrapSelectionWithSpan(`color:${value}`); else wrapSelectionWithSpan('color:inherit'); }
          );
          if (currentBlockBody) {
            currentBlockBody.focus();
            saveSelection(currentBlockBody);
          }
          break;
        }
        case 'setHorizontalRule':
          if (!ed) return;
          ed.chain().focus().setHorizontalRule().run();
          break;
        case 'insertTable': {
          const ed = getEd(); if (!ed) return;

          // Never allow nested tables: if inside a table, move out first
          if (isSelectionInsideTable(ed)) {
            forceCaretOutsideTable(ed, 'auto');
          }

          // 1) add a hidden ZWSP paragraph ABOVE the table
          ed.chain().focus().insertContent('<p>\u200B</p>').run();

          // selection is at end of that paragraph; step back 1 to be inside it
          const paraPosTop = Math.max(1, ed.state.selection.from - 1);

          // 2) insert the table
          ed.chain().focus()
            .insertTable({ rows: 3, cols: 4, withHeaderRow: false })
            .run();

          // 3) on next tick, put caret back in the paragraph ABOVE the table
          putCaretAboveJustInsertedTable(ed, paraPosTop);
          break;
        }


        case 'insertUploadBox':
        if (!ed) return;
        ed.chain().focus().insertUploadBox().run();
        break;



        case 'insertImage': {
          const url = prompt('Image URL');
          if (!url) return;
          execOnBlockOrEditor(
            ed,
            (eed) => (eed.chain().focus().setImage?.({ src: url }).run() || eed.chain().focus().insertContent(`<img src="${url}" alt="">`).run()),
            () => { document.execCommand('insertImage', false, url); }
          );
          break;
        }
        case 'setLink': {
          if (!ed) return;
          const prev = ed.getAttributes?.('link')?.href || '';
          const url = prompt('Enter URL', prev);
          if (url === null) return;
          if (currentBlockBody) {
            if (url === '') document.execCommand('unlink');
            else document.execCommand('createLink', false, url);
            currentBlockBody.focus();
          } else {
            if (url === '') ed.chain().focus().unsetLink().run();
            else ed.chain().focus().setLink({ href: url }).run();
          }
          break;
        }
        case 'unsetLink':
          if (currentBlockBody) { document.execCommand('unlink'); currentBlockBody.focus(); }
          else ed?.chain().focus().unsetLink().run();
          break;
        case 'undo':
          if (currentBlockBody) document.execCommand('undo');
          else ed?.commands.undo();
          break;
        case 'redo':
          if (currentBlockBody) document.execCommand('redo');
          else ed?.commands.redo();
          break;
        default:
          break;
      }
    });

    const hiddenColor = document.getElementById('ctl-color-hidden');
    hiddenColor?.addEventListener('input', () => {
      const value = hiddenColor.value;
      if (currentBlockBody) restoreSelection(currentBlockBody);
      const curEd = getEd();
      if (!curEd && !currentBlockBody) return;
      execOnBlockOrEditor(
        curEd,
        (eed) => (value ? eed.chain().focus().setColor(value).run() : eed.chain().focus().unsetColor().run()),
        () => { if (value) wrapSelectionWithSpan(`color:${value}`); else wrapSelectionWithSpan('color:inherit'); }
      );
      if (currentBlockBody) { currentBlockBody.focus(); saveSelection(currentBlockBody); }
      setTimeout(() => { pickingColor = false; }, 0);
    });
    hiddenColor?.addEventListener('change', () => { setTimeout(() => { pickingColor = false; }, 0); });

    const selFont = document.getElementById('ctl-font');
    const selSize = document.getElementById('ctl-size');

    function applyFontFamily(value) {
      if (currentBlockBody) {
        if (value) wrapSelectionWithSpan(`font-family:${value}`);
        else wrapSelectionWithSpan('font-family:inherit');
      } else {
        const ed = getEd();
        if (!ed) return;
        const c = ed.chain().focus();
        value ? c.setFontFamily?.(value).run() : c.unsetFontFamily?.().run();
      }
    }
    function applyFontSize(value) {
      if (currentBlockBody) {
        if (value) wrapSelectionWithSpan(`font-size:${value}`);
        else wrapSelectionWithSpan('font-size:inherit');
      } else {
        const ed = getEd();
        if (!ed) return;
        const c = ed.chain().focus();
        value ? c.setMark('textStyle', { fontSize: value }).run()
              : c.setMark('textStyle', { fontSize: null }).run();
      }
    }
    selFont?.addEventListener('change', () => applyFontFamily(selFont.value));
    selSize?.addEventListener('change', () => applyFontSize(selSize.value));
  } // end wireTopbar

// Decide above/below and move caret outside the enclosing TipTap table.
// Returns 'before' | 'after' | false (if not inside a table).
function moveCaretOutsideEnclosingTable(ed, evOrPref) {
  try {
    if (!ed?.isActive?.('table')) return false;

    // Find the table node depth at the current selection
    const { $from } = ed.state.selection;
    let tableDepth = -1;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') { tableDepth = d; break; }
    }
    if (tableDepth < 0) return false;

    // DOM + geometry to decide above/below
    const cellEl = currentCellElement?.(ed);
    const tblEl  = cellEl?.closest('table');
    const rect   = tblEl?.getBoundingClientRect?.();
    const midY   = rect ? (rect.top + rect.height / 2) : null;

    // Pointer/caret Y
    let y = null;
    if (evOrPref && typeof evOrPref === 'object' && 'clientY' in evOrPref) {
      y = evOrPref.clientY; // drop event
    } else {
      const sel = window.getSelection?.();
      if (sel && sel.rangeCount) {
        const r = sel.getRangeAt(0);
        const rr = r.getClientRects?.()[0] || r.getBoundingClientRect?.();
        if (rr) y = rr.top + rr.height / 2;
      }
    }

    // Preference may be 'before'/'after' or 'auto'
    let pref = (typeof evOrPref === 'string') ? evOrPref : 'auto';
    if (pref !== 'before' && pref !== 'after') pref = 'auto';

    let dir = 'after';
    if (pref === 'before') dir = 'before';
    else if (pref === 'after') dir = 'after';
    else if (midY != null && y != null) dir = y < midY ? 'before' : 'after';

    // Compute positions at that depth and move caret
    const pos = dir === 'before' ? $from.before(tableDepth) : $from.after(tableDepth);
    ed.chain().setTextSelection(pos).run();
    return dir;
  } catch { return false; }
}

// === Anti nested-table helpers ===
function isSelectionInsideTable(ed) {
  try {
    const { $from } = ed.state.selection;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') return true;
    }
  } catch {}
  return false;
}

function forceCaretOutsideTable(ed, evOrPref = 'auto') {
  const dir = moveCaretOutsideEnclosingTable(ed, evOrPref);
  // If we’re after the table, add a blank paragraph so inserts don’t weld to it
if (dir === 'after') ed.chain().insertContent('<p>\u200B</p>').run();
  return dir;
}


// Back-compat wrapper used by some call sites
function moveCaretAfterEnclosingTable(ed) {
  return moveCaretOutsideEnclosingTable(ed, 'after') !== false;
}

// --- Ensure caret ends up ABOVE a newly inserted table ---
function putCaretAboveJustInsertedTable(ed, paraPos) {
  // Defer one frame so this runs after ProseMirror's own selection
  requestAnimationFrame(() => {
    try {
      if (ed.isActive('table')) moveCaretOutsideEnclosingTable(ed, 'before');
      ed.chain().focus().setTextSelection(paraPos).run();
    } catch {}
  });
}




// --- TipTap in-editor table toolbar (global, outside pages) ---
function ensureTTTablebar() {
  let bar = document.body.querySelector('.tt-tablebar');
  if (!bar) {
    bar = document.createElement('div');
    bar.className = 'tt-tablebar';
    bar.innerHTML = `
      <button class="btn" data-act="row-above"   title="Insert row above">↥ Row</button>
      <button class="btn" data-act="row-below"   title="Insert row below">↧ Row</button>
      <button class="btn" data-act="col-left"    title="Insert col left">↤ Col</button>
      <button class="btn" data-act="col-right"   title="Insert col right">↦ Col</button>
      <span class="sep"></span>
      <button class="btn" data-act="del-row"     title="Delete row">✖ Row</button>
      <button class="btn" data-act="del-col"     title="Delete column">✖ Col</button>
      <span class="sep"></span>
      <button class="btn" data-act="merge"       title="Merge selected cells">Merge</button>
      <button class="btn" data-act="split"       title="Split cell">Split</button>
      <span class="sep"></span>
      <button class="btn" data-act="toggle-head" title="Toggle header row">H</button>
      <span class="sep"></span>
      <button class="btn" data-act="del-table"   title="Delete table">🗑</button>
      <div class="tt-bar-hint" aria-live="polite" style="display:none"></div>
    `;
    document.body.appendChild(bar);
  }

  // Guard so clicking the bar doesn't count as “lose focus → hide”
  if (!bar._mcGuarded) {
    bar._mcGuarded = true;
    const set = (v) => {
      window.__mc = window.__mc || {};
      window.__mc._ttBarInteracting = !!v;
    };
    const clearSoon = () => setTimeout(() => set(false), 0);

    bar.addEventListener('pointerdown', () => set(true));
    bar.addEventListener('pointerup', clearSoon);
    bar.addEventListener('pointercancel', clearSoon);
    bar.addEventListener('pointerleave', clearSoon);
    bar.addEventListener('mouseenter', () => set(true));
    bar.addEventListener('mouseleave', clearSoon);
  }

  return bar;
}


function isInTipTapTable(ed) {
  try { return !!ed?.isActive?.('table'); } catch { return false; }
}

function currentCellElement(ed) {
  try {
    const { view, state } = ed;
    const pos = state.selection.from;
    const domAt = view.domAtPos(pos);
    const start = domAt?.node || view.dom;
    return (start.nodeType === 1 ? start : start.parentElement)?.closest('td,th') || null;
  } catch { return null; }
}

function isSignatureTablePM(ed) {
  try {
    const { $from } = ed.state.selection;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') {
        const cls = String(n.attrs?.class || '');
        const sig = n.attrs?.['data-sig'];
        return /\bsig-table\b/.test(cls) || sig === '1';
      }
    }
  } catch {}
  return false;
}


/* Position the floating table toolbar for the given TipTap editor.
   Hides it for signature tables (data-sig="1" or .sig-table). */
function positionTablebarForEditor(ed) {
  const bar = ensureTTTablebar();
  if (!ed || !isInTipTapTable(ed)) { bar.style.display = 'none'; return; }

  // Hide on signature tables (locked 4×4)
  if (isSignatureTablePM(ed)) { bar.style.display = 'none'; return; }

  const cell = currentCellElement(ed);
  const tbl  = cell?.closest('table');
  if (!cell || !tbl) { bar.style.display = 'none'; return; }

  // Make sure toolbar actions hit THIS editor
  try {
    const page = ed?.options?.element?.closest('.page');
    bar.dataset.editorKey = page?.id || page?.dataset?.page || '';
  } catch {}

  bar.style.display = 'flex';

  // Clamp to the page box (not the window)
  const pageEl = ed?.options?.element?.closest('.page');
  const pr     = pageEl?.getBoundingClientRect?.();
  const pad    = 12;                          // margin inside the page
  const bw     = bar.offsetWidth  || 260;
  const bh     = bar.offsetHeight || 28;

  // Use the WHOLE TABLE rect so the bar doesn't jump per column
  const tr = tbl.getBoundingClientRect();

  // Y: prefer above the table; if no room, put it below. Always keep inside page.
  let top = Math.round(tr.top - bh - 6);
  if (pr) {
    const minTop = pr.top + pad;
    const maxTop = pr.bottom - pad - bh;
    if (top < minTop) top = Math.min(Math.round(tr.bottom + 6), maxTop);
    top = Math.max(minTop, Math.min(maxTop, top));
  } else {
    if (top < 8) top = Math.round(tr.bottom + 6);
  }

  // X: center over the TABLE (not the current cell) and clamp to page
  let left = Math.round(tr.left + (tr.width - bw) / 2);
  if (pr) {
    const minLeft = pr.left + pad;
    const maxLeft = pr.right - pad - bw;
    left = Math.max(minLeft, Math.min(maxLeft, left));
  } else {
    left = Math.max(8, Math.min(window.innerWidth - bw - 8, left));
  }

  bar.style.top  = `${top}px`;
  bar.style.left = `${left}px`;
}



/* Bind the floating table toolbar once (global).
   On click it resolves the correct TipTap editor (TT = TipTap)
   using bar.dataset.editorKey set by positionTablebarForEditor(). */
function bindTablebarActions() {
  const bar = ensureTTTablebar();
  if (bar._mcBound) return;   // bind only once globally
  bar._mcBound = true;

  // Keep PM focus so CellSelection survives while clicking the toolbar
  const keepPMFocus = (ev) => {
    ev.preventDefault();
    window.__mc && (window.__mc._ttBarInteracting = true);
    try { resolveEditorForBar()?.view?.focus(); } catch {}
  };
  bar.addEventListener('mousedown', keepPMFocus);
  bar.addEventListener('pointerdown', keepPMFocus);

  function flashBarHint(msg){
  const bar = ensureTTTablebar();
  const hint = bar.querySelector('.tt-bar-hint');
  if (!hint) return;
  hint.textContent = msg;
  hint.style.display = 'block';
  clearTimeout(hint._t);
  hint._t = setTimeout(() => { hint.style.display = 'none'; }, 2200);
}


  function resolveEditorForBar() {
    try {
      const key = bar.dataset.editorKey || '';
      const MC = window.__mc?.MCEditors;
      if (key && MC && typeof MC.get === 'function') {
        const edByKey = MC.get(key);
        if (edByKey) return edByKey;
      }
    } catch {}
    return window.__mc?.getActiveEditor?.() || null;
  }

bar.onclick = (e) => {
  const btn = e.target.closest('button[data-act]');
  if (!btn) return;

  if (window.__mc) window.__mc._ttBarInteracting = true;

  const ed = resolveEditorForBar();
  if (!ed) return;

  try { ed.view?.focus(); } catch {}

  const c = ed.chain().focus();

  switch (btn.dataset.act) {
    case 'row-above':   c.addRowBefore().run(); break;
    case 'row-below':   c.addRowAfter().run(); break;
    case 'col-left':    c.addColumnBefore().run(); break;
    case 'col-right':   c.addColumnAfter().run(); break;
    case 'del-row':     c.deleteRow().run(); break;
    case 'del-col':     c.deleteColumn().run(); break;

    case 'merge': {
      // If not a valid multi-cell selection, show hint and bail
      if (!ed.can().mergeCells()) {
        flashBarHint('Hold Alt and drag to select multiple cells, then click Merge');
        return;
      }
      c.mergeCells().run();
      break;
    }

    case 'split': {
      // If current cell can’t be split (not merged), show hint and bail
      if (!ed.can().splitCell()) {
        flashBarHint('Split only works on merged cells — merge first, then split');
        return;
      }
      c.splitCell().run();
      break;
    }

    case 'toggle-head': c.toggleHeaderRow().run(); break;
    case 'del-table':   c.deleteTable().run(); ensureTTTablebar().style.display='none'; break;
  }

  requestAnimationFrame(() => { positionTablebarForEditor(ed); });
  setTimeout(() => { if (window.__mc) window.__mc._ttBarInteracting = false; }, 0);
};

}


let _ttBarInteracting = false;


/* Wire the floating toolbar to all TipTap editors.
   Repositions on selection changes / updates / focus / window events. */
function wireTipTapTableUI() {
  const all = (window.__mc?.MCEditors?.all?.() || []);
  all.forEach((ed) => {
    if (ed._mcTableUIBound) return;
    ed._mcTableUIBound = true;

    ed.on('selectionUpdate', () => positionTablebarForEditor(ed));
    ed.on('update',           () => positionTablebarForEditor(ed));
    ed.on('focus',            () => positionTablebarForEditor(ed));
    ed.on('blur', () => {
      if (window.__mc && window.__mc._ttBarInteracting) return;
      ensureTTTablebar().style.display = 'none';
    });

    // Keep PM focus when interacting inside the editor wrapper
    ed.options.element.addEventListener('mousedown', () => {
      try { ed.view?.focus(); } catch {}
    });

    const sync = () => positionTablebarForEditor(ed);
    window.addEventListener('resize', sync);
    window.addEventListener('scroll', sync, true);

    // Bind the toolbar globally (no editor param)
    bindTablebarActions();

    // Initial placement (in case cursor is already in a table)
    positionTablebarForEditor(ed);
  });
}

  // ---------- Boot ----------
  // ---------- Boot ----------
  waitForEditor().then(() => {
    wireSidebarDrag();
    wireDropTargets();
    wireTopbar();
    wireTipTapTableUI();

    document.addEventListener('mc:rewire', () => {
      wireTipTapTableUI();
    });

    document.addEventListener('mouseup', () => wireTipTapTableUI(), true);
  });

    document.addEventListener('DOMContentLoaded', () => {
    wireSidebarDrag();
    wireDropTargets();
  });


  // expose a rewire hook so new pages can be made drop targets
  window.__mc = window.__mc || {};
  window.__mc.rewireDropTargets = () => {
    document.querySelectorAll('.page').forEach((page) => {
      ensureOverlay(page);
    });
    // ⬇️ Make sure new overlays actually get drag/drop listeners
    wireDropTargets();

    const evt = new Event('mc:rewire');
    document.dispatchEvent(evt);
  };

})(); 