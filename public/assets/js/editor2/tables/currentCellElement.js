export default function currentCellElement(ed){
  try {
    const { view, state } = ed;
    const pos = state.selection.from;
    const domAt = view.domAtPos(pos);
    const start = domAt?.node || view.dom;
    return (start.nodeType === 1 ? start : start.parentElement)?.closest('td,th') || null;
  } catch { return null; }
}
