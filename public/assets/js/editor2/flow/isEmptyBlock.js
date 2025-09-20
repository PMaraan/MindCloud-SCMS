export default function isEmptyBlock(node){
  if (!node) return true;
  if (node.type !== 'paragraph') return false;
  const c = node.content || [];
  if (!c.length) return true;
  if (c.length === 1 && c[0].type === 'text' && (!c[0].text || !c[0].text.trim())) return true;
  return false;
}
