<?php
// /public/assets/js/syllabi/createModal.js
header('Content-Type: application/javascript');
function ver($file) {
  $abs = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/js/syllabi/' . $file;
  return @filemtime($abs) ?: time();
}
?>
import { fetchPrograms, fetchCourses } from './dataLoaders.php?v=<?=ver('dataLoaders.php')?>';
import { preselectCollege, preselectCourse, getCurrentCollegeParam, fillSelect, lockSelectElement } from './utils.js?v=<?=ver('utils.js')?>';
import { getActiveTile } from './state.js?v=<?=ver('state.js')?>';
console.debug('[createModal] module loaded');

/**
 * fillFromTile(tile)
 * - Copies dataset values from the currently selected syllabus tile into the create modal form controls.
 * - tile comes from tiles.js via getActiveTile(); it exposes attributes like data-template-id, data-scope, etc.
 * - Has no return value; it mutates the DOM inputs so the modal shows the tile’s current details.
 */
function fillFromTile(tile) {
  if (!tile) return;
  const selectedProgramIds = JSON.parse(tile.dataset.programIds || '[]');

  const programSelect = document.getElementById('sy-program');
  if (programSelect) {
    Array.from(programSelect.options).forEach((opt) => {
      opt.selected = selectedProgramIds.includes(Number(opt.value));
    });
  }

  const get = (key, fallback = '') => tile.dataset[key] ?? fallback;

  document.getElementById('sy-title').value = get('title', '');
  document.getElementById('sy-college').value = get('collegeId', '');
  document.getElementById('sy-program').value = get('programId', '');
  document.getElementById('sy-course').value = get('courseId', '');
  //document.getElementById('sy-version').value = get('version', '');
  //document.getElementById('sy-status').value = get('status', 'draft');
}

/**
 * parseProgramIds(raw)
 * - Converts various formats of program ID lists into an array of positive integers.
 * - Used to normalize the programIds dataset value from the tile for consistent processing.
 * - Returns an array of program IDs, or an empty array if none are valid.
 */
function parseProgramIds(raw) {
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed)) {
      return parsed.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0);
    }
  } catch (_) {}
  return String(raw)
    .split(',')
    .map((id) => Number(id.trim()))
    .filter((id) => Number.isFinite(id) && id > 0);
}

/**
 * populateDependentSelects(tile)
 * - Refreshes the program/course dropdowns in the modal based on the tile’s current college/program.
 * - tile is the same source as in fillFromTile(); used here to preselect existing relationships when the modal opens.
 * - Uses fetchPrograms/fetchCourses to pull JSON data; the results are injected into the selects via fillSelect().
 */
async function populateDependentSelects(tile) {
  const collegeSelect = document.getElementById('sy-college');
  const programSelect = document.getElementById('sy-program');
  const courseSelect  = document.getElementById('sy-course');
  if (!collegeSelect || !programSelect || !courseSelect) return;

  const currentCollege = tile?.dataset.collegeId || tile?.dataset.ownerDepartmentId || collegeSelect?.value || '';
  const selectedProgramIds = parseProgramIds(tile?.dataset.programIds || '[]');
  const currentCourse  = tile?.dataset.courseId || '';

  if (!currentCollege) {
    fillSelect(programSelect, [], programSelect.multiple ? null : '— Select program —');
    fillSelect(courseSelect, [], '— Select course —');
    return;
  }

  const programs = await fetchPrograms(currentCollege);
  fillSelect(programSelect, programs, programSelect.multiple ? null : '— Select program —');
  if (selectedProgramIds.length) {
    Array.from(programSelect.options).forEach((opt) => {
      opt.selected = selectedProgramIds.includes(Number(opt.value));
    });
  }

  const courses = await fetchCourses(currentCollege);
  fillSelect(courseSelect, courses, '— Select course —');
  if (currentCourse) courseSelect.value = currentCourse;
}

/**
 * initCreateModal()
 * - Called once from TemplateBuilder-Scaffold.js after DOMContentLoaded.
 * - Wires all interactions for the create modal: fills inputs, locks college dropdowns for dean/chair, and keeps the form action scoped.
 * - No return; the side effects are event listeners and initial state sync.
 */
export default function initCreateModal(modal) {
  if (!modal) return;

  const collegeSelect = modal.querySelector('#sy-college');
  const programSelect = modal.querySelector('#sy-program');
  const courseSelect  = modal.querySelector('#sy-course');

  const defaultCollegeValue =
    collegeSelect?.dataset.lockedValue ||
    collegeSelect?.dataset.defaultValue ||
    getCurrentCollegeParam() ||
    '';

  preselectCollege(collegeSelect, null, defaultCollegeValue);
  lockSelectElement(collegeSelect, null);

  console.debug('[createModal] initCreateModal() running');
  const form = modal.querySelector('form');
  const deptSelect = collegeSelect;

  /**
   * syncAction(tile)
   * - Keeps the form’s POST target aligned with the currently selected college (ensures redirect stays scoped).
   * - tile is the active template card supplying fallback college info when the select is empty.
   */
  const syncAction = (tile) => {
    if (!form) return;
    if (!form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    const base = form.dataset.baseAction;
    let collegeId = deptSelect?.value || '';
    if (!collegeId && tile) collegeId = tile.dataset.ownerDepartmentId || '';
    if (!collegeId) collegeId = defaultCollegeValue || getCurrentCollegeParam();
    let action = base;
    if (collegeId) action += `&college=${encodeURIComponent(collegeId)}`;
    form.setAttribute('action', action);
  };

  /**
   * applyLock()
   * - Applies readonly/lock state to the college select based on the PHP-rendered data attributes.
   * - lockSelectElement() ensures the user can’t change the college when their role is restricted.
   */
  const applyLock = () => {
    if (!form || !deptSelect) return;
    const shouldLock = deptSelect.dataset.locked === '1';
    const defaultValue = deptSelect.dataset.lockedValue || deptSelect.dataset.defaultValue || deptSelect.value;
    lockSelectElement(deptSelect, null, shouldLock, defaultValue || null);
  };

  /**
   * resetCourseSelect()
   * - Clears the course dropdown; used when program changes or visibility toggles off.
   */
  const resetCourseSelect = () => {
    fillSelect(courseSelect, [], '— Select course —');
  };

  /**
   * resetProgramSelect()
   * - Clears the program dropdown and cascades to reset the course list.
   */
  const resetProgramSelect = () => {
    fillSelect(programSelect, [], '— Select program —');
    resetCourseSelect();
  };

  /**
   * Event Listeners
   * - deptSelect: on change, fetch programs unless locked, then sync action
   * - programSelect: on change, fetch courses unless locked, then sync action
   * - modal show: fill inputs from tile, update visibility, apply lock, sync action
   * - modal shown: populate dependent selects, update visibility, apply lock, sync action
   */
  deptSelect?.addEventListener('change', async (event) => {
    if (deptSelect.dataset.locked === '1') {
      const lockedValue = deptSelect.dataset.lockedValue || deptSelect.dataset.defaultValue || '';
      if (lockedValue) deptSelect.value = lockedValue;
      syncAction(getActiveTile());
      return;
    }
    const depId = event.target.value || '';
    resetProgramSelect();
    if (!depId) {
      syncAction(getActiveTile());
      return;
    }

    console.debug('[CreateModal] fetchPrograms -> deptId=', depId);
    try {
      const programs = await fetchPrograms(depId);
      console.debug('[CreateModal] programs payload:', programs);
      fillSelect(programSelect, programs, '— Select program —');

      const courses = await fetchCourses(depId);
      fillSelect(courseSelect, courses, '— Select course —');
      syncAction(getActiveTile());
    } catch (err) {
      console.error('[CreateModal] fetchPrograms error:', err);
      // keep UI usable by leaving program select empty
      fillSelect(programSelect, [], '— Select program —');
    }
    syncAction(getActiveTile());
  });

  modal.addEventListener('show.bs.modal', () => {
    if (form && !form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    fillFromTile(null);
    applyLock();
    if (deptSelect && !deptSelect.value && defaultCollegeValue) {
      deptSelect.value = defaultCollegeValue;
    }
    syncAction(getActiveTile());
  });

  modal.addEventListener('shown.bs.modal', async () => {
    const tile = getActiveTile();
    await populateDependentSelects(tile);
    applyLock();
    syncAction(tile);
  });
}