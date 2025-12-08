// /public/assets/js/rteditor/nodes/page-wrapper.js
// Tiptap Node: pageWrapper
// - schema: block, content 'block+'
// - command: wrapIntoPages() -> convert top-level flow with pageBreak markers
// - NodeView: renders header / contentDOM / footer and sizes from getPageConfig()

import { Node } from '@tiptap/core';
import { Fragment } from 'prosemirror-model';

function mmToPx(mm) { return (parseFloat(mm) || 0) / 25.4 * 96; }

export default Node.create({
  name: 'pageWrapper',
  group: 'block',
  content: 'block+',
  isolating: true,
  draggable: false,

  parseHTML() {
    return [{ tag: 'div[data-type="page-wrapper"]' }];
  },

  renderHTML({ HTMLAttributes }) {
    return ['div', { 'data-type': 'page-wrapper', ...HTMLAttributes }, 0];
  },

  addCommands() {
    return {
      // Walk top-level nodes in state.doc and create a new doc composed of
      // pageWrapper nodes containing nodes until a pageBreak node is encountered.
      wrapIntoPages: () => ({ state, dispatch }) => {
        const { doc, schema, tr } = state;
        const pageNodeType = schema.nodes.pageWrapper;
        const pageBreakType = schema.nodes.pageBreak;
        if (!pageNodeType) return false;

        const pages = [];
        let buffer = [];

        // Helper flush
        const flushBuffer = () => {
          if (!buffer.length) return;
          const frag = Fragment.fromArray(buffer);
          pages.push(pageNodeType.create(null, frag));
          buffer = [];
        };

        doc.forEach((child) => {
          if (child.type === pageBreakType) {
            // End of current page
            flushBuffer();
          } else {
            buffer.push(child);
          }
        });
        flushBuffer();

        // Replace full doc content with the pages sequence (if we have >0)
        if (!pages.length) return false;

        const replacement = Fragment.fromArray(pages);
        tr.replaceWith(0, doc.content.size, replacement);

        if (dispatch) dispatch(tr.scrollIntoView());
        return true;
      }
    };
  },

  addNodeView() {
    // NodeView factory. Will be called for each pageWrapper node.
    return ({ node, view, getPos }) => {
      // Root wrapper for this page node
      const dom = document.createElement('div');
      dom.className = 'rt-node-page';
      dom.setAttribute('data-type', 'page-wrapper');

      // header clone (non-editable)
      const header = document.createElement('div');
      header.className = 'rt-node-page-header';
      header.setAttribute('data-page-part', 'header');
      header.setAttribute('contenteditable', 'false');

      // body host where ProseMirror will mount the node's content
      const body = document.createElement('div');
      body.className = 'rt-node-page-body';
      body.setAttribute('data-page-part', 'body');

      // footer clone
      const footer = document.createElement('div');
      footer.className = 'rt-node-page-footer';
      footer.setAttribute('data-page-part', 'footer');
      footer.setAttribute('contenteditable', 'false');

      dom.appendChild(header);
      dom.appendChild(body);
      dom.appendChild(footer);

      // contentDOM must be an element where ProseMirror will render this node's children
      const contentDOM = body;

      // Helper to sync master header/footer content
      function syncHeaderFooter() {
        try {
          const masterHeader = document.getElementById('rtHeader');
          const masterFooter = document.getElementById('rtFooter');
          header.innerHTML = masterHeader ? masterHeader.innerHTML : '';
          footer.innerHTML = masterFooter ? masterFooter.innerHTML : '';
        } catch (e) {
          // ignore
        }
      }

      // Helper to set page width/inner height using page config getter
      function applyPageSizing() {
        try {
          const getCfg = (view && view.state && view.state.config && view.state.config.pageContainerGetConfig)
                      || (typeof window.__RT_getPageConfig === 'function' && window.__RT_getPageConfig)
                      || (typeof window.getCurrentPageConfig === 'function' && window.getCurrentPageConfig)
                      || null;
          const cfg = (typeof getCfg === 'function') ? getCfg() : null;
          if (!cfg || !cfg.size) {
            // fallback: clear inline sizes
            dom.style.width = '';
            body.style.minHeight = '';
            return;
          }

          const isLandscape = cfg.orientation === 'landscape';
          const pageW_mm = isLandscape ? (cfg.size.hmm || cfg.size.h) : (cfg.size.wmm || cfg.size.w);
          const pageH_mm = isLandscape ? (cfg.size.wmm || cfg.size.w) : (cfg.size.hmm || cfg.size.h);

          const w = Math.round(mmToPx(pageW_mm || cfg.size.w || 210));
          const h = Math.round(mmToPx(pageH_mm || cfg.size.h || 297));
          // header/footer heights counted from cloned DOM (approx)
          const headerH = header.getBoundingClientRect().height || 0;
          const footerH = footer.getBoundingClientRect().height || 0;
          const cs = getComputedStyle(body);
          const pt = parseFloat(cs.paddingTop) || 0;
          const pb = parseFloat(cs.paddingBottom) || 0;
          const usable = Math.max(0, h - headerH - footerH - pt - pb);

          dom.style.width = `${w}px`;
          body.style.minHeight = `${usable}px`;
        } catch (e) { /* ignore */ }
      }

      // Keep header/footer in sync when masters change
      const moConfig = { childList: true, subtree: true, characterData: true };
      let headerObs = null, footerObs = null;
      try {
        const masterHeader = document.getElementById('rtHeader');
        const masterFooter = document.getElementById('rtFooter');
        if (masterHeader) {
          headerObs = new MutationObserver(syncHeaderFooter);
          headerObs.observe(masterHeader, moConfig);
        }
        if (masterFooter) {
          footerObs = new MutationObserver(syncHeaderFooter);
          footerObs.observe(masterFooter, moConfig);
        }
      } catch (e) { /* ignore */ }

      // initial sync + sizing
      syncHeaderFooter();
      // schedule sizing to run after layout
      setTimeout(() => applyPageSizing(), 20);

      // Expose a local cleanup used by destroy
      const cleanup = () => {
        try { if (headerObs) headerObs.disconnect(); } catch (e) {}
        try { if (footerObs) footerObs.disconnect(); } catch (e) {}
      };

      return {
        dom,
        contentDOM,
        update(updatedNode) {
          // If structure or attrs changed, we might want to re-size/sync
          // node type and content don't change here (pageWrapper holds block+)
          setTimeout(() => {
            syncHeaderFooter();
            applyPageSizing();
          }, 10);
          return true;
        },
        selectNode() { /* optional visual focus handling */ },
        deselectNode() { /* optional */ },
        destroy() {
          cleanup();
        }
      };
    };
  }
});
