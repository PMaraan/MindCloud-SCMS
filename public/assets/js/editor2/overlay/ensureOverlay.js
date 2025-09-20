import PAGE_PADDING_TOP from '../utils/PAGE_PADDING_TOP.js';
export default function ensureOverlay(pageEl){
  let overlay = pageEl.querySelector('.mc-block-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'mc-block-overlay';
    Object.assign(overlay.style, { position:'absolute', inset:'0', pointerEvents:'none', paddingTop: `${PAGE_PADDING_TOP}px` });
    if (getComputedStyle(pageEl).position === 'static') pageEl.style.position = 'relative';
    pageEl.appendChild(overlay);
  }
  return overlay;
}
