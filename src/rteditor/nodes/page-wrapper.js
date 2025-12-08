// /src/rteditor/nodes/page-wrapper.js
// Cleaned page-wrapper Node + NodeView for TipTap/ProseMirror
import { Node } from '@tiptap/core';

/**
 * PageWrapper Node
 * - name: pageWrapper
 * - content: block*
 * - nodeView: header / body (contentDOM) / footer
 *
 * Notes:
 * - header/footer edits are persisted into node attrs via setNodeMarkup.
 * - contentDOM is `body` so ProseMirror manages the inner blocks.
 * - sizing comes from editor-provided page config or a global window getter.
 */
export default Node.create({
  name: 'pageWrapper',

  addAttributes() {
    return {
      headerHTML: { default: '' },
      footerHTML: { default: '' },
    };
  },

  group: 'block',
  content: 'block*',
  selectable: false,
  draggable: false,

  parseHTML() {
    return [{ tag: 'div.rt-node-page' }];
  },

  renderHTML({ HTMLAttributes }) {
    return [
      'div',
      { ...HTMLAttributes, class: 'rt-node-page', 'data-type': 'page-wrapper' },
      ['div', { class: 'rt-node-page-header', 'data-page-part': 'header' }, 0],
      ['div', { class: 'rt-node-page-body', 'data-page-part': 'body' }, 1],
      ['div', { class: 'rt-node-page-footer', 'data-page-part': 'footer' }, 0],
    ];
  },

  addNodeView() {
    // factory: returns nodeView object for a node instance
    return ({ node, view, getPos }) => {
      const dom = document.createElement('div');
      dom.className = 'rt-node-page';
      dom.setAttribute('data-type', 'page-wrapper');

      // Header (editable, persisted to attrs)
      const header = document.createElement('div');
      header.className = 'rt-node-page-header';
      header.setAttribute('data-page-part', 'header');
      header.contentEditable = 'true';
      header.style.width = '100%';
      header.style.boxSizing = 'border-box';
      header.innerHTML = (node?.attrs?.headerHTML) || 'Header…';
      dom.appendChild(header);

      // Body is the contentDOM — ProseMirror will manage the node.content here
      const body = document.createElement('div');
      body.className = 'rt-node-page-body';
      body.setAttribute('data-page-part', 'body');
      dom.appendChild(body);

      // Footer (editable, persisted to attrs)
      const footer = document.createElement('div');
      footer.className = 'rt-node-page-footer';
      footer.setAttribute('data-page-part', 'footer');
      footer.contentEditable = 'true';
      footer.style.width = '100%';
      footer.style.boxSizing = 'border-box';
      footer.innerHTML = (node?.attrs?.footerHTML) || 'Footer…';
      dom.appendChild(footer);

      // Expose for debugging
      dom.__rt_header = header;
      dom.__rt_body = body;
      dom.__rt_footer = footer;

      // Debounce helper for attribute persistence
      const debounce = (fn, wait = 300) => {
        let t = null;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
      };

      // Persist header/footer edits into node attributes
      const saveAttrs = () => {
        try {
          const pos = (typeof getPos === 'function') ? getPos() : getPos;
          // If getPos returns -1 or null, skip (node might be detached)
          if (pos === null || typeof pos === 'number' && pos < 0) return;

          const newAttrs = Object.assign({}, node.attrs || {}, {
            headerHTML: header.innerHTML,
            footerHTML: footer.innerHTML,
          });

          const tr = view.state.tr.setNodeMarkup(pos, undefined, newAttrs);
          if (tr.docChanged) view.dispatch(tr.setMeta('addToHistory', true));
        } catch (e) {
          console.warn('[pageWrapper] saveAttrs failed', e);
        }
      };

      const debouncedSave = debounce(saveAttrs, 350);
      header.addEventListener('input', debouncedSave);
      footer.addEventListener('input', debouncedSave);

      // Prevent ProseMirror from stealing mousedown that would cancel caret placement
      header.addEventListener('mousedown', (e) => e.stopPropagation());
      footer.addEventListener('mousedown', (e) => e.stopPropagation());

      // --------- Page sizing: try to get page config from editor or global getter ----------
      try {
        const getCfg = (view && view.editor && view.editor.options && view.editor.options.pageContainerGetConfig)
          ? view.editor.options.pageContainerGetConfig
          : (typeof window.__RT_getPageConfig === 'function' ? window.__RT_getPageConfig : null);

        const cfg = (typeof getCfg === 'function') ? getCfg() : null;

        const mmToPx = (mm) => (parseFloat(mm) || 0) / 25.4 * 96;

        if (cfg && cfg.size) {
          const isLandscape = cfg.orientation === 'landscape';
          const wmm = isLandscape ? (cfg.size.hmm || cfg.size.h) : (cfg.size.wmm || cfg.size.w);
          const hmm = isLandscape ? (cfg.size.wmm || cfg.size.w) : (cfg.size.hmm || cfg.size.h);
          const pageW = Math.round(mmToPx(wmm));
          const pageH = Math.round(mmToPx(hmm));

          const padLeft  = cfg.paddingLeftMm ? Math.round(mmToPx(cfg.paddingLeftMm)) : 0;
          const padRight = cfg.paddingRightMm ? Math.round(mmToPx(cfg.paddingRightMm)) : 0;
          const padTop   = cfg.paddingTopMm ? Math.round(mmToPx(cfg.paddingTopMm)) : 0;
          const padBottom= cfg.paddingBottomMm ? Math.round(mmToPx(cfg.paddingBottomMm)) : 0;

          dom.style.boxSizing = 'border-box';
          dom.style.width = pageW + 'px';
          dom.style.height = pageH + 'px';
          dom.style.padding = `${padTop}px ${padRight}px ${padBottom}px ${padLeft}px`;
          dom.style.display = 'block';
          dom.style.margin = '16px auto';
          dom.style.background = 'white';
          dom.style.pageBreakAfter = 'always';
          dom.style.boxShadow = '0 2px 6px rgba(0,0,0,0.08)';
          dom.style.border = '1px solid rgba(0,0,0,0.06)';
        }
      } catch (e) {
        console.warn('[pageWrapper] sizing failed', e);
      }

      // Return NodeView object (contentDOM is body)
      return {
        dom,
        contentDOM: body,
        destroy() {
          // cleanup listeners if needed; currently none are saved to variables
        }
      };
    };
  },

  addCommands() {
    return {
      /**
       * wrapIntoPages:
       * - Converts top-level pageBreak nodes into pageWrapper nodes
       * - Returns true if mutation occurred, false otherwise
       */
      wrapIntoPages: () => ({ state, dispatch }) => {
        const { schema, doc } = state;
        const pageWrapperNodeType = schema.nodes.pageWrapper;
        const pageBreakNodeType = schema.nodes.pageBreak;
        if (!pageWrapperNodeType || !pageBreakNodeType) return false;

        // If the doc already contains pageWrapper nodes, skip conversion
        try {
          let foundWrapper = false;
          doc.descendants((n) => {
            if (n?.type?.name && (n.type.name === 'pageWrapper' || n.type.name === 'page-wrapper' || n.type.name === 'page_wrapper')) {
              foundWrapper = true;
              return false;
            }
            return true;
          });
          if (foundWrapper) return true;
        } catch (e) {
          // continue cautiously if traversal fails
        }

        // collect top-level pageBreak positions
        const breaks = [];
        doc.descendants((node, pos) => {
          if (node.type === pageBreakNodeType) breaks.push({ pos, size: node.nodeSize });
        });

        if (!breaks.length) return false;

        // Build segments between breaks
        const segments = [];
        let last = 0;
        for (let i = 0; i < breaks.length; i++) {
          const bp = breaks[i];
          segments.push({ from: last, to: bp.pos });
          last = bp.pos + bp.size;
        }
        if (last < doc.content.size) segments.push({ from: last, to: doc.content.size });

        const validSegments = segments.filter(s => (typeof s.from === 'number' && typeof s.to === 'number' && s.to > s.from));
        if (!validSegments.length) return false;

        let tr = state.tr;
        // Replace segments with pageWrapper nodes from end→start to keep positions valid
        for (let i = validSegments.length - 1; i >= 0; i--) {
          const { from, to } = validSegments[i];
          const slice = doc.slice(from, to);
          const wrapper = pageWrapperNodeType.create({}, slice.content);
          tr = tr.replaceRangeWith(from, to, wrapper);
        }

        if (tr.docChanged) {
          if (dispatch) dispatch(tr.setMeta('addToHistory', true));
          return true;
        }
        return false;
      }
    };
  }
});
