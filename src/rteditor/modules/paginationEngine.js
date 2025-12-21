// /src/rteditor/modules/paginationEngine.js
import { runOnce } from "../auto-pagination.js";

/**
 * High-level entry used by pageBootstrap / editorInstance to paginate live or manually.
 * Re-exports the low-level runner for advanced callers who want direct access.
 */
export function runAutoPaginate(editor, opts = {}) {
  // opts expected to include:
  // contentEl, headerEl, footerEl, getPageConfig, clearExisting, forceResegment
  return runOnce(editor, opts);
}

// Also export the raw runner for tests/advanced usage
export { runOnce };
