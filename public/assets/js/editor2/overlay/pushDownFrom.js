import snap from '../utils/snap.js';
export default function pushDownFrom(source, overlay){
  const blocks = Array.from(overlay.querySelectorAll('.mc-block'))
    .filter((b) => b !== source)
    .sort((a, b) => (parseInt(a.style.top || 0, 10) - parseInt(b.style.top || 0, 10)));

  const srcTop = parseInt(source.style.top || 0, 10);
  const srcBottom = srcTop + source.offsetHeight;
  let cursor = srcBottom;

  for (const blk of blocks) {
    let top = parseInt(blk.style.top || 0, 10);
    const h = blk.offsetHeight;
    const bottom = top + h;
    const overlaps = top < cursor && bottom > srcTop;
    if (overlaps) {
      top = snap(cursor);
      blk.style.top = `${top}px`;
      cursor = top + h;
    } else {
      cursor = Math.max(cursor, bottom);
    }
  }
}
