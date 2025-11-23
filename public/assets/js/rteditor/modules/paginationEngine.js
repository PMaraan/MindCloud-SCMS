// /public/assets/js/rteditor/modules/paginationEngine.js
import { runOnce as autoPaginate } from "../auto-pagination.js";

/**
 * Run auto paginate with friendly options. This is the entry used by
 * pageBootstrap to paginate live or manually.
 */
export function runAutoPaginate(editor, opts = {}) {
  // opts expected to include: pageEl, contentEl, headerEl, footerEl, getPageConfig, clearExisting, debug
  return autoPaginate(editor, opts);
}
