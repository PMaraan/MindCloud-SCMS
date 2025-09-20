import setOverlaysDragEnabled from '../overlay/setOverlaysDragEnabled.js';
export default function wireSidebarDrag(){
  document.querySelectorAll('#mc-sidebar .sb-item').forEach((btn) => {
    if (!btn.hasAttribute('draggable')) btn.setAttribute('draggable', 'true');
    btn.addEventListener('dragstart', (e) => {
      const type = btn.dataset.type || '';
      e.dataTransfer.effectAllowed = 'copy';
      e.dataTransfer.setData('application/x-mc', JSON.stringify({ type }));
      setOverlaysDragEnabled(true);
    });
    btn.addEventListener('dragend', () => setOverlaysDragEnabled(false));
  });
}
