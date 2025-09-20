const selectionStore = new WeakMap();

export function saveSelection(body){
  const sel = window.getSelection();
  if (!sel || sel.rangeCount === 0) return;
  const range = sel.getRangeAt(0);
  if (!body.contains(range.commonAncestorContainer)) return;
  selectionStore.set(body, range.cloneRange());
}
export function restoreSelection(body){
  const range = selectionStore.get(body);
  if (!range) return false;
  const sel = window.getSelection();
  sel.removeAllRanges();
  sel.addRange(range);
  return true;
}
export default function registerBlockBody(bodyEl){
  let currentBlockBody = null;
  bodyEl.addEventListener('focusin', () => { currentBlockBody = bodyEl; setTimeout(() => saveSelection(bodyEl), 0); });
  bodyEl.addEventListener('mousedown', () => { currentBlockBody = bodyEl; });
  bodyEl.addEventListener('mouseup', () => saveSelection(bodyEl));
  bodyEl.addEventListener('keyup', () => saveSelection(bodyEl));
  bodyEl.addEventListener('focusout', () => {
    if (currentBlockBody === bodyEl) currentBlockBody = null;
    selectionStore.delete(bodyEl);
    document.addEventListener('selectionchange', () => { if (currentBlockBody) saveSelection(currentBlockBody); });
  });
}
