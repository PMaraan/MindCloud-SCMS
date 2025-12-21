// /src/rteditor/modules/livePaginationController.js

/**
 * Live visual pagination controller.
 * - Observes editor transactions
 * - Re-runs pagination (throttled)
 * - NEVER mutates the document
 * - DOM-only separators + guides
 */

import { runAutoPaginate } from "./paginationEngine.js";

export function attachLivePagination(editor, opts = {}) {
  const {
    throttleMs = 120,
    debug = false,
  } = opts;

  let scheduled = false;
  let destroyed = false;

  function schedule() {
    if (destroyed || scheduled) return;

    scheduled = true;
    requestAnimationFrame(() => {
      scheduled = false;

      try {
        runAutoPaginate(editor, {
          // Phase 1: continuous flow → paginate the ProseMirror root
          contentEl: editor.view && editor.view.dom ? editor.view.dom : null,
          clearExisting: true,
          forceResegment: true,
        });

        if (debug) {
          console.log("[RTEditor] live pagination pass");
        }
      } catch (err) {
        console.warn("[RTEditor] pagination failed", err);
      }
    });
  }

  // 1) React to editor transactions
  editor.on("transaction", () => {
    schedule();
  });

  // 2) React to DOM size changes (fonts, images, deletes)
  const resizeObserver = new ResizeObserver(() => {
    schedule();
  });

  resizeObserver.observe(editor.view.dom);

  // 3) Cleanup hook
  editor.__rt_destroyLivePagination = () => {
    destroyed = true;
    resizeObserver.disconnect();
  };

  // Initial run
  schedule();
}
