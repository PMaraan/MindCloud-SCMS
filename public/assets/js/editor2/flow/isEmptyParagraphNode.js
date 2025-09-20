export default function isEmptyParagraphNode(n){
  if (!n || n.type !== 'paragraph') return false;
  const c = n.content || [];
  if (!c.length) return true;
  return c.length === 1 && c[0].type === 'text' && (!c[0].text || !c[0].text.trim());
}
