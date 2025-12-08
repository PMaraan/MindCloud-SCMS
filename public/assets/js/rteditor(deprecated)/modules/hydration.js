// /public/assets/js/rteditor/modules/hydration.js
export function readInitialDocFromScriptTag() {
  const tag = document.getElementById('rt-initial-json');
  if (!tag) return null;
  const raw = (tag.textContent || '').replace(/^\uFEFF/, '').trim();
  if (!raw) return null;
  try {
    const parsed = JSON.parse(raw);
    return (parsed && typeof parsed === 'object' && parsed.json && typeof parsed.json === 'object') ? parsed.json : parsed;
  } catch (e) {
    console.warn('[RTEditor] hydration skipped:', e);
    return null;
  }
}

export function applyHydrationIfTrivial(editor, serverDoc) {
  if (!serverDoc) return false;
  try {
    const current = editor.getJSON && editor.getJSON();
    function isTrivialDoc(doc) {
      if (!doc || !Array.isArray(doc.content)) return true;
      if (doc.content.length === 0) return true;
      if (doc.content.length === 1) {
        const first = doc.content[0];
        if (first && first.type === 'paragraph') {
          const fc = first.content;
          if (!fc || (Array.isArray(fc) && fc.length === 0)) return true;
        }
      }
      return false;
    }
    if (isTrivialDoc(current)) {
      editor.commands.setContent(serverDoc);
      return true;
    }
  } catch (e) {
    console.warn('[RTEditor] hydration failed:', e);
  }
  return false;
}
