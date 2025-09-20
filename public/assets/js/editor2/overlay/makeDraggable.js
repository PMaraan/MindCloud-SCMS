import snap from '../utils/snap.js';
import PAGE_PADDING_TOP from '../utils/PAGE_PADDING_TOP.js';
import pushDownFrom from './pushDownFrom.js';

export default function makeDraggable(block, overlay){
  const grip = block.querySelector('.drag-handle');
  if (!grip) return;

  let ghost;
  const startDrag = (e) => {
    e.preventDefault();
    const startRect = block.getBoundingClientRect();
    const ovRect = overlay.getBoundingClientRect();
    const offsetY = e.clientY - startRect.top;

    ghost = overlay.querySelector('.mc-ghost-line');
    if (!ghost) {
      ghost = document.createElement('div');
      ghost.className = 'mc-ghost-line';
      Object.assign(ghost.style, { position:'absolute', left:'0', right:'0', height:'2px', background:'rgba(123,15,20,.35)', pointerEvents:'none' });
      overlay.appendChild(ghost);
    }

    const onMove = (mv) => {
      const proposed = mv.clientY - ovRect.top - offsetY;
      const snapped = snap(Math.max(PAGE_PADDING_TOP, proposed));
      ghost.style.top = `${snapped}px`;
      ghost.style.display = 'block';
    };
    const onUp = () => {
      document.removeEventListener('mousemove', onMove);
      document.removeEventListener('mouseup', onUp);
      const top = parseInt(ghost.style.top || '0', 10) || PAGE_PADDING_TOP;
      ghost.style.display = 'none';
      block.style.top = `${top}px`;
      pushDownFrom(block, overlay);
    };
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
  };
  grip.addEventListener('mousedown', startDrag);
}
