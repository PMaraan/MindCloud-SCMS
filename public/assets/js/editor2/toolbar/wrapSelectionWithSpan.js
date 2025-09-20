export default function wrapSelectionWithSpan(styleText){
  const sel = window.getSelection();
  if (!sel || sel.rangeCount === 0) return;
  const range = sel.getRangeAt(0);
  if (range.collapsed) {
    const span = document.createElement('span');
    span.setAttribute('style', styleText);
    span.appendChild(document.createTextNode('\u200b'));
    range.insertNode(span);
    const newRange = document.createRange();
    newRange.setStart(span.firstChild, span.firstChild.length);
    newRange.collapse(true);
    sel.removeAllRanges();
    sel.addRange(newRange);
    return;
  }
  const frag = range.cloneContents();
  const div = document.createElement('div');
  div.appendChild(frag);
  const html = `<span style="${styleText}">${div.innerHTML}</span>`;
  document.execCommand('insertHTML', false, html);
}
