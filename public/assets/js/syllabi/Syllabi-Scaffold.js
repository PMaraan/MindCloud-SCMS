// /public/assets/js/syllabi/Syllabi-Scaffold.js
import { installCssEscapeFallback } from './utils.js';
import initTileInteractions from './tiles.js';
import initEditModal from './editModal.js';

installCssEscapeFallback();

document.addEventListener('DOMContentLoaded', () => {
  initTileInteractions();
  const editModalEl = document.getElementById('syEditModal');
  if (editModalEl) {
    initEditModal(editModalEl);
  }
});
