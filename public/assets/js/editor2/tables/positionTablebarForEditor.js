import ensureTTTablebar from './ensureTTTablebar.js';
import isSignatureTablePM from './isSignatureTablePM.js';
import currentCellElement from './currentCellElement.js';

export default function positionTablebarForEditor(ed){
  const bar = ensureTTTablebar();
  if (!ed || !ed.isActive?.('table')) { bar.style.display = 'none'; return; }
  if (isSignatureTablePM(ed)) { bar.style.display = 'none'; return; }

  const cell = currentCellElement(ed);
  const tbl  = cell?.closest('table');
  if (!cell || !tbl) { bar.style.display = 'none'; return; }

  try {
    const page = ed?.options?.element?.closest('.page');
    bar.dataset.editorKey = page?.id || page?.dataset?.page || '';
  } catch {}

  bar.style.display = 'flex';
  const pageEl = ed?.options?.element?.closest('.page');
  const pr     = pageEl?.getBoundingClientRect?.();
  const pad    = 12;
  const bw     = bar.offsetWidth  || 260;
  const bh     = bar.offsetHeight || 28;

  const tr = tbl.getBoundingClientRect();
  let top = Math.round(tr.top - bh - 6);
  if (pr) {
    const minTop = pr.top + pad;
    const maxTop = pr.bottom - pad - bh;
    if (top < minTop) top = Math.min(Math.round(tr.bottom + 6), maxTop);
    top = Math.max(minTop, Math.min(maxTop, top));
  } else { if (top < 8) top = Math.round(tr.bottom + 6); }

  let left = Math.round(tr.left + (tr.width - bw) / 2);
  if (pr) {
    const minLeft = pr.left + pad;
    const maxLeft = pr.right - pad - bw;
    left = Math.max(minLeft, Math.min(maxLeft, left));
  } else { left = Math.max(8, Math.min(window.innerWidth - bw - 8, left)); }

  bar.style.top  = `${top}px`;
  bar.style.left = `${left}px`;
}
