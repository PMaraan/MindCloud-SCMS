// /public/assets/js/syllabustemplates/state.js
let selectedTileId = null;

/**
 * setSelectedTileId(id)
 * - Persists the currently highlighted template tile ID in module scope (and a debug hook on window).
 * - Called by tiles.js whenever the user clicks or navigates to a new tile.
 * - id comes from the tile’s data-template-id attribute; stored as string or null.
 */
export function setSelectedTileId(id) {
  selectedTileId = id ? String(id) : null;
  window.__tb_selectedId = selectedTileId;
}

/**
 * getSelectedTileId()
 * - Retrieves the last stored tile ID for modules that need it (e.g., TemplateBuilder-Scaffold.js on load).
 * - Returns a string or null; does not touch the DOM.
 */
export function getSelectedTileId() {
  return selectedTileId;
}

/**
 * getActiveTile()
 * - Resolves the actual DOM element representing the active tile.
 * - First checks for the .tb-card--active class; falls back to the stored ID if the class isn’t present yet.
 * - Used by edit/duplicate/archive flows to pull dataset values for modals.
 */
export function getActiveTile() {
  const active = document.querySelector('.tb-tile.tb-card--active');
  if (active) return active;
  if (!selectedTileId) return null;
  return document.querySelector(`.tb-tile[data-template-id="${CSS.escape(String(selectedTileId))}"]`);
}