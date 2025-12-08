// /src/rteditor/extensions/pageContainerExtension.js
import { Plugin } from "prosemirror-state";

/**
 * Robust relocation of .rt-node-page NodeView DOMs into #pageRoot.
 */
function relocateNodeViews(view) {
  if (!view || !view.dom) return;
  const pageRoot = document.getElementById('pageRoot');
  if (!pageRoot) return;

  // Find node pages anywhere under the ProseMirror root
  const pmRoot = view.dom;
  const nodePages = Array.from(pmRoot.querySelectorAll('.rt-node-page'));

  if (!nodePages.length) {
    if (window.__RT_debugAutoPaginate) console.log('[PageContainerExt] no nodePages found to relocate');
    return;
  }

  // Filter out those already direct children of pageRoot (they're already relocated)
  const toMove = nodePages.filter(n => n.parentElement !== pageRoot);

  if (!toMove.length) {
    if (window.__RT_debugAutoPaginate) console.log('[PageContainerExt] nodePages already relocated (no-op)');
    return;
  }

  // Move them preserving document order.
  // We will append in the order they appear in the pmRoot query (document order).
  const frag = document.createDocumentFragment();
  toMove.forEach(n => {
    try { frag.appendChild(n); } catch (e) { /* ignore */ }
  });

  pageRoot.appendChild(frag);

  if (window.__RT_debugAutoPaginate) {
    console.log('[PageContainerExt] relocated nodePages -> #pageRoot', toMove.length);
  }
}

export default function PageContainerExtension() {
  return new Plugin({
    view(editorView) {
      // initial attempt
      try { relocateNodeViews(editorView); } catch (e) { /* ignore */ }

      // Keep an observer to pick up added NodeViews
      const mo = new MutationObserver((mutations) => {
        let added = false;
        for (const m of mutations) {
          if (m.addedNodes && m.addedNodes.length) { added = true; break; }
        }
        if (added) {
          try { relocateNodeViews(editorView); } catch (e) { /* ignore */ }
        }
      });
      try { mo.observe(editorView.dom, { childList: true, subtree: true }); } catch (e) { /* ignore */ }

      return {
        update(view) {
          try { relocateNodeViews(view); } catch (e) { /* ignore */ }
        },
        destroy() {
          try { mo.disconnect(); } catch (e) { /* ignore */ }
        }
      };
    }
  });
}
