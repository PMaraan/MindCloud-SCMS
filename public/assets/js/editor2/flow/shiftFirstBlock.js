import cloneJSON from '../utils/cloneJSON.js';
import ensureDocNotEmpty from './ensureDocNotEmpty.js';
export default function shiftFirstBlock(ed){
  const json = cloneJSON(ed.getJSON());
  const arr  = json.content || [];
  if (!arr.length) return null;
  const first = arr.shift();
  ensureDocNotEmpty(json);
  ed.commands.setContent(json, false);
  return first;
}
