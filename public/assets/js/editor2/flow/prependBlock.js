import cloneJSON from '../utils/cloneJSON.js';
import nbspParagraph from './nbspParagraph.js';
import isTableNode from './isTableNode.js';
import isEmptyParagraphNode from './isEmptyParagraphNode.js';

export default function prependBlock(ed, node){
  const json = cloneJSON(ed.getJSON());
  const arr  = json.content || [];

  if (isTableNode(node)) {
    if (isEmptyParagraphNode(arr[0])) {
      arr[0] = nbspParagraph();
      json.content = [arr[0], node, ...arr.slice(1)];
    } else {
      json.content = [nbspParagraph(), node, ...arr];
    }
  } else {
    json.content = [node, ...arr];
  }
  ed.commands.setContent(json, false);
}
