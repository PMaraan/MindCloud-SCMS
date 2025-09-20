import snap from '../utils/snap.js';
import PAGE_PADDING_TOP from '../utils/PAGE_PADDING_TOP.js';
export default function reflowStack(overlay){
  const items = Array.from(overlay.querySelectorAll('.mc-block'))
    .sort((a, b) => (parseInt(a.style.top || 0, 10) - parseInt(b.style.top || 0, 10)));

  let cursor = PAGE_PADDING_TOP;
  for (const blk of items) {
    let top = parseInt(blk.style.top || 0, 10);
    if (top < cursor) {
      top = snap(cursor);
      blk.style.top = `${top}px`;
    }
    cursor = top + blk.offsetHeight;
  }
}
