export default function getPrevPageEl(pageEl){
  return pageEl?.previousElementSibling?.classList?.contains('page') ? pageEl.previousElementSibling : null;
}
