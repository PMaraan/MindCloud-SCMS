export default function hasAnyRealContent(ed){
  const json = ed.getJSON();
  const arr = json.content || [];
  if (!arr.length) return false;
  if (arr.length === 1 && arr[0].type === 'paragraph' && (!arr[0].content || !arr[0].content.length)) return false;
  return true;
}
