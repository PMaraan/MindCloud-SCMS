import GRID from '../utils/GRID.js';
import frameBlock from './frameBlock.js';
import registerBlockBody from './registerBlockBody.js';
import pushDownFrom from './pushDownFrom.js';

export default function makeParagraph(){
  const el = document.createElement('div');
  frameBlock(el);
  el.classList.add('mc-paragraph');
  const body = document.createElement('div');
  body.className = 'element-body'; body.contentEditable = 'true';
  Object.assign(body.style, { outline:'none', whiteSpace:'pre-wrap', wordBreak:'break-word', lineHeight:'1.5', padding:'2px 0', font:'inherit', color:'inherit' });
  body.textContent = 'Paragraph text';
  el.appendChild(body);
  el.style.height = `${GRID * 2}px`;
  el.dataset.rows = '2';

  const autosize = () => {
    const overlay = el.closest('.mc-block-overlay');
    const lines = Math.max(1, Math.ceil(body.scrollHeight / GRID));
    const rows = Math.max(2, lines);
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
