import measureEditor from './measureEditor.js';
import getFlowGuardPx from './getFlowGuardPx.js';
import getPageOfEditor from './getPageOfEditor.js';
import ensureNextPageEditor from './ensureNextPageEditor.js';
import popLastBlock from './popLastBlock.js';
import isEmptyBlock from './isEmptyBlock.js';
import hasAnyRealContent from './hasAnyRealContent.js';
import prependBlock from './prependBlock.js';

export default async function flowForward(ed){
  let guard = 20;
  while (guard-- > 0){
    const {limit, used} = measureEditor(ed);
    const G = getFlowGuardPx(ed);
    if (used <= limit - G) break;

    const pageEl = getPageOfEditor(ed);
    const nextEd = await ensureNextPageEditor(pageEl);
    if (!nextEd) break;

    const node = popLastBlock(ed);
    if (!node) break;

    if (isEmptyBlock(node)){
      if (!hasAnyRealContent(ed)) break;
      continue;
    }
    prependBlock(nextEd, node);
    ed = nextEd;
  }
}
