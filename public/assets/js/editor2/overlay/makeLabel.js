import GRID from '../utils/GRID.js';
import frameBlock from './frameBlock.js';
import registerBlockBody from './registerBlockBody.js';
export default function makeLabel(){
  const el = document.createElement('div');
  frameBlock(el);
  el.classList.add('mc-label');
  const body = document.createElement('div');
  body.className = 'element-body'; body.contentEditable = 'true';
  Object.assign(body.style, { outline:'none', whiteSpace:'nowrap', padding:'2px 0', fontWeight:'600', font:'inherit', color:'inherit' });
  body.textContent = 'Label text';
  el.appendChild(body);
  el.style.height = `${GRID}px`;
  el.dataset.rows = '1';
  registerBlockBody(body);
  return el;
}
