import GRID from '../utils/GRID.js';
import frameBlock from './frameBlock.js';
import registerBlockBody from './registerBlockBody.js';
import pushDownFrom from './pushDownFrom.js';

export default function makeTextArea(){
  const el = document.createElement('div');
  frameBlock(el);
  const body = document.createElement('div');
  body.className = 'element-body'; body.contentEditable = 'true';
  Object.assign(body.style, {
    outline:'none', whiteSpace:'pre-wrap', wordBreak:'break-word', lineHeight:'1.5', display:'block',
    padding:'8px 10px', border:'1px solid #111827', borderRadius:'6px', background:'#fff', font:'inherit', color:'inherit'
  });
  body.textContent = 'Text block';
  el.appendChild(body);
  el.style.height = `${GRID * 4}px`;
  el.dataset.rows = '4';

  const autosize = () => {
    const overlay = el.closest('.mc-block-overlay');
    const contentH = Math.max(body.scrollHeight, GRID);
    const rows = Math.max(3, Math.ceil(contentH / GRID));
    const h = rows * GRID;
    if (h !== parseInt(el.style.height || '0', 10)) {
      el.style.height = `${h}px`;
      el.dataset.rows = String(rows);
      if (overlay) pushDownFrom(el, overlay);
    }
  };
  body.addEventListener('input', () => requestAnimationFrame(autosize));
  requestAnimationFrame(autosize);
  registerBlockBody(body);
  return el;
}
