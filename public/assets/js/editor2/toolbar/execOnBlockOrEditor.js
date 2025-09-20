export default function execOnBlockOrEditor(editor, fnForEditor, fallback /* string or function */){
  const currentBlockBody = document.querySelector('.element-body:focus');
  if (currentBlockBody && currentBlockBody.isContentEditable !== false) {
    if (typeof fallback === 'function') fallback();
    else if (typeof fallback === 'string') document.execCommand(fallback, false, null);
    currentBlockBody.focus();
  } else {
    fnForEditor(editor);
  }
}
