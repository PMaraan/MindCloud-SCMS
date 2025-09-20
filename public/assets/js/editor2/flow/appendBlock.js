import cloneJSON from '../utils/cloneJSON.js';
import nbspParagraph from './nbspParagraph.js';
import isTableNode from './isTableNode.js';
import isEmptyParagraphNode from './isEmptyParagraphNode.js';

export default function appendBlock(ed, node){
  const json = cloneJSON(ed.getJSON());
  const arr  = json.content || [];
  const last = arr[arr.length - 1];

  if (isTableNode(node)) {
    if (isEmptyParagraphNode(last)) {
      arr[arr.length - 1] = nbspParagraph();
      json.content = [...arr, node];
    } else {
      json.content = [...arr, nbspParagraph(), node];
    }
  } else {
    json.content = [...arr, node];
  }
  ed.commands.setContent(json, false);
}
