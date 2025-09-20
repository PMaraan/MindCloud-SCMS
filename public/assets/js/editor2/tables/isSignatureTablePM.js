export default function isSignatureTablePM(ed){
  try {
    const { $from } = ed.state.selection;
    for (let d = $from.depth; d >= 0; d--) {
      const n = $from.node(d);
      if (n?.type?.name === 'table') {
        const cls = String(n.attrs?.class || '');
        const sig = n.attrs?.['data-sig'];
        return /\bsig-table\b/.test(cls) || sig === '1';
      }
    }
  } catch {}
  return false;
}
