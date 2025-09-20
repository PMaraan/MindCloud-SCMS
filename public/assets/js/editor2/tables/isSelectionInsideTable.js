export default function isSelectionInsideTable(ed){
  try {
    const { $from } = ed.state.selection;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') return true;
    }
  } catch {}
  return false;
}
