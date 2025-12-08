// /src/rteditor/modules/hydration.js
export function readInitialDocFromScriptTag() {
  const tag = document.getElementById('rt-initial-json');
  if (!tag) return null;
  const raw = (tag.textContent || '').replace(/^\uFEFF/, '').trim();
  if (!raw) return null;

  try {
    const parsed = JSON.parse(raw);

    // If the server wrapped payload as { json: { ... } } prefer that shape
    const doc = (parsed && typeof parsed === 'object' && parsed.json && typeof parsed.json === 'object') ? parsed.json : parsed;

    // Flatten nested pageWrapper nodes (defensive)
    function flattenNode(node) {
      if (!node || typeof node !== 'object') return node;
      const t = (node.type || '').toString();

      // Only process nodes that have content arrays
      if (!Array.isArray(node.content) || node.content.length === 0) {
        return node;
      }

      // If this node itself is a pageWrapper-like node, we want to splice out any child pageWrapper wrappers
      const isWrapper = (t === 'pageWrapper' || t === 'page-wrapper' || t === 'page_wrapper');

      if (isWrapper) {
        const newContent = [];
        for (const child of node.content) {
          if (child && typeof child === 'object') {
            const ct = (child.type || '').toString();
            if (ct === 'pageWrapper' || ct === 'page-wrapper' || ct === 'page_wrapper') {
              // splice child's children in (recursively flattened)
              if (Array.isArray(child.content)) {
                for (const grand of child.content) {
                  newContent.push(flattenNode(grand));
                }
              }
            } else {
              newContent.push(flattenNode(child));
            }
          } else {
            newContent.push(child);
          }
        }
        return { ...node, content: newContent };
      }

      // Non-wrapper node: recurse into content
      const newC = node.content.map(c => flattenNode(c));
      return { ...node, content: newC };
    }

    const flattened = flattenNode(doc);
    return flattened;
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
