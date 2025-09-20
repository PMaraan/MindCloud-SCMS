export default function setOverlaysDragEnabled(enabled){
  document.querySelectorAll('.mc-block-overlay').forEach((ov) => { ov.style.pointerEvents = enabled ? 'auto' : 'none'; });
}
