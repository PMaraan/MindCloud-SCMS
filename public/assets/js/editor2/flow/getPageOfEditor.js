export default function getPageOfEditor(ed){
  return ed?.options?.element?.closest?.('.page') || null;
}
