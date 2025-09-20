import getNextPageEl from './getNextPageEl.js';
import createPage from '../pages/createPage.js';
import getEditorOfPage from './getEditorOfPage.js';

export default async function ensureNextPageEditor(currentPageEl){
  let nextPage = getNextPageEl(currentPageEl);
  if (!nextPage){
    await createPage();
    nextPage = getNextPageEl(currentPageEl) || document.querySelector('.page:last-of-type');
  }
  return getEditorOfPage(nextPage);
}
