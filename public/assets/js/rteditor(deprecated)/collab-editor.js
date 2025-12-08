// /public/assets/js/rteditor/collab-editor.js
// Top-level barrel: re-export public API for the editor page.

export { default as initBasicEditor, getBasePath } from "./modules/editorInstance.js";
export { bindBasicToolbar } from "./modules/toolbarBinder.js";
export { runAutoPaginate } from "./modules/paginationEngine.js";
export { readInitialDocFromScriptTag, applyHydrationIfTrivial } from "./modules/hydration.js";
export { startEditorPage } from "./modules/pageBootstrap.js";

// When loaded directly as a module (script src=".../collab-editor.js"), auto-start
// if a global flag is present (optional behavior). We will not auto-run by default.
// To start from the view, use:
//   import { startEditorPage } from "/public/assets/js/rteditor/collab-editor.js";
//   startEditorPage({ debug: false });
