// /public/assets/js/syllabi/state.js
/**
 * state.js
 * - Manages selected syllabus state for tiles.js and editModal.js.
 * - Exports setter/getter for selectedSyllabusId and a function to get the active tile element.
 */
let selectedSyllabusId = null;

export function setSelectedSyllabusId(id) {
  selectedSyllabusId = id ? String(id) : null;
  window.__sy_selectedId = selectedSyllabusId;
}

export function getSelectedSyllabusId() {
  return selectedSyllabusId;
}

export function getActiveTile() {
  const active = document.querySelector('.sy-tile.sy-card--active');
  if (active) return active;
  if (!selectedSyllabusId) return null;
  return document.querySelector(`.sy-tile[data-syllabus-id="${CSS.escape(selectedSyllabusId)}"]`);
}