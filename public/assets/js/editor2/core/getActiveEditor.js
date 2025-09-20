import MCEditors from './MCEditors.js';
export default function getActiveEditor(){
  const el = document.activeElement;
  if (el) {
    const page = el.closest?.('.page');
    if (page) {
      for (const ed of MCEditors.all()) {
        if (page.contains(ed.options.element)) return ed;
      }
    }
  }
  return MCEditors.first();
}
