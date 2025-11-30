<?php
// /public/assets/js/syllabi/Syllabi-Scaffold.js
header('Content-Type: application/javascript');
function ver($file) {
  $abs = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/js/syllabi/' . $file;
  return @filemtime($abs) ?: time();
}
?>
import { installCssEscapeFallback, showFlashMessage } from './utils.js?v=<?=ver('utils.js')?>';
import { setSelectedSyllabusId, getSelectedSyllabusId, getActiveTile } from './state.js?v=<?=ver('state.js')?>';
import initTileInteractions from './tiles.php?v=<?=ver('tiles.php')?>';
import initEditModal from './editModal.php?v=<?=ver('editModal.php')?>';
import initArchiveDelete from './archiveDelete.php?v=<?=ver('archiveDelete.php')?>';

installCssEscapeFallback();
window.showFlashMessage = showFlashMessage;

document.addEventListener('DOMContentLoaded', () => {
  initTileInteractions();
  const editModalEl = document.getElementById('syEditModal');
  if (editModalEl) initEditModal(editModalEl);
  initArchiveDelete();
});
