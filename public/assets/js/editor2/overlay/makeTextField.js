import GRID from '../utils/GRID.js';
import frameBlock from './frameBlock.js';
import registerBlockBody from './registerBlockBody.js';
export default function makeTextField(){
  const el = document.createElement('div');
  frameBlock(el);
  el.classList.add('mc-textfield');
  const body = document.createElement('div');
  body.className = 'element-body'; body.contentEditable = 'true';
  Object.assign(body.style, { outline:'none', whiteSpace:'nowrap', borderBottom:'1px solid #9ca3af', minWidth:'240px', padding:'2px 0', font:'inherit', color:'inherit' });
  el.appendChild(body);
  el.style.height = `${GRID}px`;
  el.dataset.rows = '1';
  registerBlockBody(body);
  return el;
}
