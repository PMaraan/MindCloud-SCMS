// /public/assets/js/syllabi/state.js
let selectedSyllabusId = null;

export function setSelectedSyllabusId(id) {
  selectedSyllabusId = id ? String(id) : null;
  window.__sy_selectedId = selectedSyllabusId;
}

export function getSelectedSyllabusId() {
  return selectedSyllabusId;
}

export function getActiveTile() {
  const active = document.querySelector('.tb-tile.tb-card--active');
  if (active) return active;
  if (!selectedSyllabusId) return null;
  return document.querySelector(`.tb-tile[data-syllabus-id="${CSS.escape(selectedSyllabusId)}"]`);
}