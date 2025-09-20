export default function getNextPageEl(pageEl){
  return pageEl?.nextElementSibling?.classList?.contains('page') ? pageEl.nextElementSibling : null;
}
