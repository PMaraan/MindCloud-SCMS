// /public/assets/js/syllabi/Syllabi-Scaffold.js
import { installCssEscapeFallback, showFlashMessage } from './utils.js';
import initTileInteractions from './tiles.js';
import initEditModal from './editModal.js';

installCssEscapeFallback();
window.showFlashMessage = showFlashMessage;

document.addEventListener('DOMContentLoaded', () => {
  initTileInteractions();
  const editModalEl = document.getElementById('syEditModal');
  if (editModalEl) {
    initEditModal(editModalEl);
  }
});
