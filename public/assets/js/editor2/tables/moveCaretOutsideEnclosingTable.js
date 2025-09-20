import currentCellElement from './currentCellElement.js';
export default function moveCaretOutsideEnclosingTable(ed, evOrPref){
  try {
    if (!ed?.isActive?.('table')) return false;
    const { $from } = ed.state.selection;
    let tableDepth = -1;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') { tableDepth = d; break; }
    }
    if (tableDepth < 0) return false;

    const cellEl = currentCellElement(ed);
    const tblEl  = cellEl?.closest('table');
    const rect   = tblEl?.getBoundingClientRect?.();
    const midY   = rect ? (rect.top + rect.height / 2) : null;

    let y = null;
    if (evOrPref && typeof evOrPref === 'object' && 'clientY' in evOrPref) y = evOrPref.clientY;
    else {
      const sel = window.getSelection?.();
      if (sel && sel.rangeCount) {
        const r = sel.getRangeAt(0);
        const rr = r.getClientRects?.()[0] || r.getBoundingClientRect?.();
        if (rr) y = rr.top + rr.height / 2;
      }
    }

    let pref = (typeof evOrPref === 'string') ? evOrPref : 'auto';
    if (pref !== 'before' && pref !== 'after') pref = 'auto';

    let dir = 'after';
    if (pref === 'before') dir = 'before';
    else if (pref === 'after') dir = 'after';
    else if (midY != null && y != null) dir = y < midY ? 'before' : 'after';

    const pos = dir === 'before' ? $from.before(tableDepth) : $from.after(tableDepth);
    ed.chain().setTextSelection(pos).run();
    return dir;
  } catch { return false; }
}
