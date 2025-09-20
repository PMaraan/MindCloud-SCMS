export default function putCaretAboveJustInsertedTable(ed, paraPos){
  requestAnimationFrame(() => {
    try {
      if (ed.isActive('table')) {
        const { $from } = ed.state.selection;
        ed.chain().focus().setTextSelection(paraPos).run();
      } else {
        ed.chain().focus().setTextSelection(paraPos).run();
      }
    } catch {}
  });
}
