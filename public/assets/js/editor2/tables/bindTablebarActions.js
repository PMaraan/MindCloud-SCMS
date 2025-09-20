import ensureTTTablebar from './ensureTTTablebar.js';
import positionTablebarForEditor from './positionTablebarForEditor.js';
import flashBarHint from './flashBarHint.js';

function resolveEditorForBar(){
  try {
    const bar = ensureTTTablebar();
    const key = bar.dataset.editorKey || '';
    const MC  = window.__mc?.MCEditors;
    if (key && MC && typeof MC.get === 'function') {
      const edByKey = MC.get(key);
      if (edByKey) return edByKey;
    }
  } catch {}
  return window.__mc?.getActiveEditor?.() || null;
}

export default function bindTablebarActions(){
  const bar = ensureTTTablebar();
  if (bar._mcBound) return;
  bar._mcBound = true;

  const keepPMFocus = (ev) => {
    ev.preventDefault();
    window.__mc && (window.__mc._ttBarInteracting = true);
    try { resolveEditorForBar()?.view?.focus(); } catch {}
  };
  bar.addEventListener('mousedown', keepPMFocus);
  bar.addEventListener('pointerdown', keepPMFocus);

  bar.onclick = (e) => {
    const btn = e.target.closest('button[data-act]');
    if (!btn) return;
    if (window.__mc) window.__mc._ttBarInteracting = true;

    const ed = resolveEditorForBar();
    if (!ed) return;
    try { ed.view?.focus(); } catch {}

    const c = ed.chain().focus();
    switch (btn.dataset.act) {
      case 'row-above': c.addRowBefore().run(); break;
      case 'row-below': c.addRowAfter().run(); break;
      case 'col-left':  c.addColumnBefore().run(); break;
      case 'col-right': c.addColumnAfter().run(); break;
      case 'del-row':   c.deleteRow().run(); break;
      case 'del-col':   c.deleteColumn().run(); break;
      case 'merge':
        if (!ed.can().mergeCells()) { flashBarHint('Hold Alt and drag to select multiple cells, then click Merge'); return; }
        c.mergeCells().run(); break;
      case 'split':
        if (!ed.can().splitCell()) { flashBarHint('Split only works on merged cells — merge first, then split'); return; }
        c.splitCell().run(); break;
      case 'toggle-head': c.toggleHeaderRow().run(); break;
      case 'del-table': c.deleteTable().run(); ensureTTTablebar().style.display='none'; break;
    }

    requestAnimationFrame(() => { positionTablebarForEditor(ed); });
    setTimeout(() => { if (window.__mc) window.__mc._ttBarInteracting = false; }, 0);
  };
}
