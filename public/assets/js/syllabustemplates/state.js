let selectedTileId = null;

export function setSelectedTileId(id) {
  selectedTileId = id ? String(id) : null;
  window.__tb_selectedId = selectedTileId;
}

export function getSelectedTileId() {
  return selectedTileId;
}

export function getActiveTile() {
  const active = document.querySelector('.tb-tile.tb-card--active');
  if (active) return active;
  if (!selectedTileId) return null;
  return document.querySelector(`.tb-tile[data-template-id="${CSS.escape(String(selectedTileId))}"]`);
}