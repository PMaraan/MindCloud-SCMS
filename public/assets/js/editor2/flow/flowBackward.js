import measureEditor from './measureEditor.js';
import getFlowGuardPx from './getFlowGuardPx.js';
import getPageOfEditor from './getPageOfEditor.js';
import getNextPageEl from './getNextPageEl.js';
import getEditorOfPage from './getEditorOfPage.js';
import hasAnyRealContent from './hasAnyRealContent.js';
import shiftFirstBlock from './shiftFirstBlock.js';
import appendBlock from './appendBlock.js';
import popLastBlock from './popLastBlock.js';
import prependBlock from './prependBlock.js';

export default async function flowBackward(ed){
  let guard = 20;
  while (guard-- > 0){
    const {limit, used} = measureEditor(ed);
    const G = getFlowGuardPx(ed);
    if (used >= limit - G) break;

    const curPage = getPageOfEditor(ed);
    const nextPage = getNextPageEl(curPage);
    if (!nextPage) break;
    const nextEd = getEditorOfPage(nextPage);
    if (!nextEd || !hasAnyRealContent(nextEd)) break;

    const node = shiftFirstBlock(nextEd);
    if (!node) break;

    appendBlock(ed, node);

    const m = measureEditor(ed);
    if (m.used > m.limit - getFlowGuardPx(ed)){
      popLastBlock(ed);
      prependBlock(nextEd, node);
      break;
    }
  }
}
