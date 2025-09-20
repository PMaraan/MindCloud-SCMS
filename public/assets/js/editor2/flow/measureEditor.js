import getPageOfEditor from './getPageOfEditor.js';
export default function measureEditor(ed){
  const pageEl = getPageOfEditor(ed);
  const box    = pageEl?.querySelector('[data-editor]');
  const prose  = box?.querySelector('.ProseMirror') || box;
  const footer = pageEl?.querySelector('.page-footer');
  if (!box || !prose) return { limit: 0, used: 0 };
  const boxTop     = box.getBoundingClientRect().top;
  const bottomEdge = footer ? footer.getBoundingClientRect().top : box.getBoundingClientRect().bottom;
  const limit = Math.max(0, bottomEdge - boxTop);
  const used  = prose.scrollHeight;
  return { limit, used };
}
