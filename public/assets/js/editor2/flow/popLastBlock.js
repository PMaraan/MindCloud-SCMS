import cloneJSON from '../utils/cloneJSON.js';
import ensureDocNotEmpty from './ensureDocNotEmpty.js';
export default function popLastBlock(ed){
  const json = cloneJSON(ed.getJSON());
  const arr  = json.content || [];
  if (!arr.length) return null;
  const last = arr.pop();
  ensureDocNotEmpty(json);
  ed.commands.setContent(json, false);
  return last;
}
