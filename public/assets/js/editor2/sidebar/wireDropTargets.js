import ensureOverlay from '../overlay/ensureOverlay.js';
import setOverlaysDragEnabled from '../overlay/setOverlaysDragEnabled.js';
import FACTORY from '../overlay/FACTORY.js';
import isSelectionInsideTable from '../tables/isSelectionInsideTable.js';
import forceCaretOutsideTable from '../tables/forceCaretOutsideTable.js';
import putCaretAboveJustInsertedTable from '../tables/putCaretAboveJustInsertedTable.js';
import signatureTableHTML from './signatureTableHTML.js';

export default function wireDropTargets(){
  const pages = document.querySelectorAll('.page');
  if (!pages.length) return;

  pages.forEach((page) => {
    const overlay = ensureOverlay(page);
    if (overlay.dataset.dropWired === '1') return;
    overlay.dataset.dropWired = '1';

    ['dragenter', 'dragover'].forEach((evt) => overlay.addEventListener(evt, (ev) => ev.preventDefault()));

    overlay.addEventListener('drop', (ev) => {
      ev.preventDefault();
      const raw = ev.dataTransfer?.getData('application/x-mc');
      if (!raw) return;

      let type = '';
      try { ({ type } = JSON.parse(raw) || {}); } catch { return; }
      if (!type) return;

      const ed = (() => {
        const pageEl = overlay.closest('.page');
        const all = window.__mc?.MCEditors?.all?.() || [];
        for (const e of all) { const el = e?.options?.element; if (pageEl && el && pageEl.contains(el)) return e; }
        return window.__mc?.getActiveEditor?.() || null;
      })();

      if (ed) {
        if (type === 'table') {
          if (isSelectionInsideTable(ed)) forceCaretOutsideTable(ed, ev);
          const edForInsert = window.__mc?.ensureRoomForTable?.(240) || ed;
          edForInsert.chain().focus().insertContent('<p>\u200B</p>').run();
          const paraPos = Math.max(1, edForInsert.state.selection.from - 1);
          edForInsert.chain().focus().insertTable({ rows:3, cols:4, withHeaderRow:false }).run();
          putCaretAboveJustInsertedTable(edForInsert, paraPos);
          setOverlaysDragEnabled(false);
          return;
        }
        if (type === 'label')     { ed.chain().focus().insertContent('<p><strong>Label text</strong></p>').run(); setOverlaysDragEnabled(false); return; }
        if (type === 'paragraph') { ed.chain().focus().insertContent('<p>Paragraph text</p>').run(); setOverlaysDragEnabled(false); return; }
        if (type === 'textField') { ed.chain().focus().insertContent('<p><span style="display:inline-block;min-width:240px;border-bottom:1px solid #9ca3af">&nbsp;</span></p>').run(); setOverlaysDragEnabled(false); return; }
        if (type === 'textarea')  { ed.chain().focus().insertContent('<p style="display:block;border:1px solid #111827;border-radius:6px;padding:8px;min-height:120px;">Text block</p>').run(); setOverlaysDragEnabled(false); return; }
        if (type === 'signature') {
          if (isSelectionInsideTable(ed)) forceCaretOutsideTable(ed, ev);
          const html = signatureTableHTML();
          ed.chain().focus().insertContent(html).run();
          ed.chain().focus().insertContent('<p></p>').run();
          setOverlaysDragEnabled(false);
          return;
        }
      }

      const factory = FACTORY?.[type];
      if (!factory) return;
      const block = factory();
      const y = Math.round(ev.offsetY / 20) * 20;
      block.style.top = `${Math.max(10, y)}px`;
      overlay.appendChild(block);
      (window.__mc?.makeDraggable || (()=>{}))(block, overlay);
      (window.__mc?.pushDownFrom || (()=>{}))(block, overlay);
      setOverlaysDragEnabled(false);
    });
  });
}
