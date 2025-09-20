import positionTablebarForEditor from './positionTablebarForEditor.js';
import ensureTTTablebar from './ensureTTTablebar.js';
import bindTablebarActions from './bindTablebarActions.js';

export default function wireTipTapTableUI(){
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

    ed.options.element.addEventListener('mousedown', () => { try { ed.view?.focus(); } catch {} });

    const sync = () => positionTablebarForEditor(ed);
    window.addEventListener('resize', sync);
    window.addEventListener('scroll', sync, true);

    bindTablebarActions();
    positionTablebarForEditor(ed);
  });
}
