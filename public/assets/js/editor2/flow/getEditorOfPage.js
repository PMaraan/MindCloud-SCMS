import MCEditors from '../core/MCEditors.js';
export default function getEditorOfPage(pageEl){
  for (const ed of MCEditors.all()) {
    if (pageEl && pageEl.contains(ed.options.element)) return ed;
  }
  return null;
}
