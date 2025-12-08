// /src/rteditor/extensions/auto-page-break.js
// MVP: placeholder for future auto-pagination logic.
// Exporting a disabled extension keeps imports clean.
import { Extension } from "@tiptap/core";

/**
 * AutoPageBreak
 * Inserts a pageBreak node when the rendered content exceeds the available page content height.
 * Relies on:
 *   - #rtPageContent element (the content box inside your page)
 *   - A "pageBreak" node registered in the schema
 */
const AutoPageBreak = Extension.create({
  name: 'autoPageBreak',

  addOptions() {
    return {
      // throttle checks (ms)
      checkDelay: 100,
      // minimum distance from bottom to trigger break (px)
      bottomPadding: 24,
    };
  },

  onCreate() {
    this._raf = null;
    this._lastCheck = 0;
  },

  onTransaction() {
    // schedule a check after the DOM updates
    if (this._raf) cancelAnimationFrame(this._raf);
    this._raf = requestAnimationFrame(() => this._checkAndInsert());
  },

  onDestroy() {
    if (this._raf) cancelAnimationFrame(this._raf);
  },

  _editorEl() {
    return this.editor?.view?.dom?.closest?.('#rtPageContent') || null;
  },

  _checkAndInsert() {
    const now = Date.now();
    if (now - this._lastCheck < this.options.checkDelay) return;
    this._lastCheck = now;

    const contentBox = document.getElementById('rtPageContent');
    if (!contentBox) return;

    // available height is the page content box’s inner height
    const maxH = contentBox.clientHeight;
    // rendered content height
    const scH = contentBox.scrollHeight;

    // if no overflow → nothing to do
    if (scH <= maxH + this.options.bottomPadding) return;

    const view = this.editor.view;
    const pageRect = contentBox.getBoundingClientRect();
    const probeY = pageRect.bottom - this.options.bottomPadding; // near the bottom edge
    const probeX = pageRect.left + 16;

    // Find a doc position close to the bottom of the visible page
    const posInfo = view.posAtCoords({ left: probeX, top: probeY });
    if (!posInfo || typeof posInfo.pos !== 'number') return;

    // Climb to block boundary to avoid splitting marks awkwardly
    const { state, dispatch } = view;
    const $pos = state.doc.resolve(posInfo.pos);
    let cutPos = $pos.start($pos.depth);
    if (!Number.isFinite(cutPos) || cutPos < 1) cutPos = posInfo.pos;

    // insert a pageBreak node at cutPos (before current block)
    const type = state.schema.nodes.pageBreak;
    if (!type) return;

    const tr = state.tr.insert(cutPos, type.create());
    if (!tr.docChanged) return;
    dispatch(tr.scrollIntoView());
  },
});

export default AutoPageBreak;
