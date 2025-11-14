import { installCssEscapeFallback, showFlashMessage } from './utils.js';
import initTileInteractions from './tiles.js';
import initCreateModal from './createModal.js';
import initEditModal from './editModal.js';
import initDuplicateModal from './duplicateModal.js';
import initArchiveDelete from './archiveDelete.js';

installCssEscapeFallback();
window.showFlashMessage = showFlashMessage;

document.addEventListener('DOMContentLoaded', () => {
  initTileInteractions();
  initCreateModal();
  initEditModal();
  initDuplicateModal();
  initArchiveDelete();
});