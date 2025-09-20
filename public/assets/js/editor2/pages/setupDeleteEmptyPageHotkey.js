import MCEditors from '../core/MCEditors.js';
import getEditorOfPage from '../flow/getEditorOfPage.js';
import getPageOfEditor from '../flow/getPageOfEditor.js';
import getPrevPageEl from '../flow/getPrevPageEl.js';
import updatePageNumbers from './updatePageNumbers.js';

function hasAnyRealContent(ed){
  const json = ed.getJSON();
  const arr = json.content || [];
  if (!arr.length) return false;
  if (arr.length === 1 && arr[0].type === 'paragraph' && (!arr[0].content || !arr[0].content.length)) return false;
  return true;
}

export default function setupDeleteEmptyPageHotkey(){
  document.addEventListener('keydown', (ev) => {
    if (ev.key !== 'Backspace' || ev.defaultPrevented) return;
    const pm = ev.target?.closest?.('.ProseMirror'); if (!pm) return;

    const ed = window.__mc?.getActiveEditor?.(); if (!ed) return;
    const sel = ed.state?.selection;
    if (!sel?.empty || sel.from !== 1 || hasAnyRealContent(ed)) return;

    const pageEl = getPageOfEditor(ed);
    const prevEl = getPrevPageEl(pageEl);
    if (!pageEl || !prevEl) return;

    ev.preventDefault(); ev.stopPropagation();

    setTimeout(() => {
      try { ed.destroy(); } catch {}
      try { for (const [k, v] of MCEditors.map.entries()) { if (v === ed) { MCEditors.map.delete(k); break; } } } catch {}
      try { pageEl.remove(); } catch {}
      updatePageNumbers();
      const prevEd = getEditorOfPage(prevEl) || MCEditors.first();
      try { prevEd?.chain().focus('end', { scrollIntoView:true }).run(); } catch { try { prevEd?.commands?.focus?.('end'); } catch {} }
      window.__mc?.rewireDropTargets?.();
    }, 0);
  }, true);
}
