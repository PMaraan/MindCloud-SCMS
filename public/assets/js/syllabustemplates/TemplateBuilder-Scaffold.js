// /public/assets/js/syllabustemplates/TemplateBuilder-Scaffold.js
import { installCssEscapeFallback, showFlashMessage } from './utils.js';
import initTileInteractions from './tiles.js';
import initCreateModal from './createModal.js';
import initEditModal from './editModal.js';
import initDuplicateModal from './duplicateModal.js';
import initArchiveDelete from './archiveDelete.js';

installCssEscapeFallback();
window.showFlashMessage = showFlashMessage;

console.debug('[Scaffold] TemplateBuilder-Scaffold.js loaded — initEditModal typeof:', typeof initEditModal);

document.addEventListener('DOMContentLoaded', () => {
  console.debug('[Scaffold] DOMContentLoaded — calling init functions');
  initTileInteractions();
  console.debug('[Scaffold] initTileInteractions done');
  initCreateModal();
  console.debug('[Scaffold] initCreateModal done');
  // Check initEditModal exists before calling
  if (typeof initEditModal === 'function') {
    console.debug('[Scaffold] calling initEditModal()');
    initEditModal();
    console.debug('[Scaffold] initEditModal() returned');
  } else {
    console.error('[Scaffold] initEditModal is NOT a function:', initEditModal);
  }
  initDuplicateModal();
  initArchiveDelete();
});