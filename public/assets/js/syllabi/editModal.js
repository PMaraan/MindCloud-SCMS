// /public/assets/js/syllabi/editModal.js
import { fetchPrograms, fetchCourses } from './dataLoaders.js';
import { preselectCollege, preselectCourse, getCurrentCollegeParam, fillSelect, lockSelectElement } from './utils.js';
import { getActiveTile } from './state.js';

console.debug('[editModal] module loaded');

/**
 * fillFromTile(tile)
 * - Copies dataset values from the currently selected syllabus tile into the edit modal form controls.
 * - tile comes from tiles.js via getActiveTile(); it exposes attributes like data-template-id, data-scope, etc.
 * - Has no return value; it mutates the DOM inputs so the modal shows the tile’s current details.
 */
function fillFromTile(tile) {
  if (!tile) return;
  const selectedProgramIds = JSON.parse(tile.dataset.programIds || '[]');

  const programSelect = document.getElementById('sy-e-program');
  if (programSelect) {
    Array.from(programSelect.options).forEach((opt) => {
      opt.selected = selectedProgramIds.includes(Number(opt.value));
    });
  }

  const get = (key, fallback = '') => tile.dataset[key] ?? fallback;

  document.getElementById('sy-e-id').value = get('syllabusId', '');
  document.getElementById('sy-e-title').value = get('title', '');
  document.getElementById('sy-e-college').value = get('collegeId', '');
  //document.getElementById('sy-e-program').value = get('programId', '');
  document.getElementById('sy-e-course').value = get('courseId', '');
  document.getElementById('sy-e-version').value = get('version', '');
  document.getElementById('sy-e-status').value = get('status', 'draft');
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
  const collegeSelect = document.getElementById('sy-e-college');
  const programSelect = document.getElementById('sy-e-program');
  const courseSelect  = document.getElementById('sy-e-course');
  if (!collegeSelect || !programSelect || !courseSelect) return;

  const collegeId   = tile?.dataset.collegeId || tile?.dataset.ownerDepartmentId || '';
  const programIds  = parseProgramIds(tile?.dataset.programIds || '[]');
  const primaryProg = tile?.dataset.programId || String(programIds[0] ?? '');
  const courseId    = tile?.dataset.courseId || '';

  if (!collegeId) {
    fillSelect(programSelect, [], programSelect.multiple ? null : '— Select program —');
    fillSelect(courseSelect, [], '— Select course —');
    return;
  }

  const programs = await fetchPrograms(collegeId);
  fillSelect(programSelect, programs, programSelect.multiple ? null : '— Select program —');
  if (programIds.length) {
    Array.from(programSelect.options).forEach((opt) => {
      opt.selected = programIds.includes(Number(opt.value));
    });
  } else if (primaryProg) {
    programSelect.value = primaryProg;
  }

  const coursePivot = primaryProg || programSelect.value || '';
  if (!coursePivot) {
    fillSelect(courseSelect, [], '— Select course —');
    return;
  }

  const courses = await fetchCourses(coursePivot);
  fillSelect(courseSelect, courses, '— Select course —');
  if (courseId) courseSelect.value = courseId;
}

/**
 * initEditModal()
 * - Called once from TemplateBuilder-Scaffold.js after DOMContentLoaded.
 * - Wires all interactions for the edit modal: fills inputs, locks college dropdowns for dean/chair, and keeps the form action scoped.
 * - No return; the side effects are event listeners and initial state sync.
 */
export default function initEditModal(modal) {
  if (!modal) return;
  const tile = getActiveTile();
  const collegeSelect = modal.querySelector('#sy-e-college');
  const hiddenCollegeInput = modal.querySelector('input[name="college_id"][type="hidden"]');
  const courseSelect = modal.querySelector('#sy-e-course');

  preselectCollege(collegeSelect, hiddenCollegeInput, tile?.dataset.lockedValue || null);
  preselectCourse(courseSelect, tile?.dataset.courseId || null);
  lockSelectElement(collegeSelect, hiddenCollegeInput);

  modal.addEventListener('show.bs.modal', () => {
    if (!collegeSelect || !hiddenInput) return;
    if (collegeSelect.dataset.locked === '1') {
      collegeSelect.value = collegeSelect.dataset.lockedValue || '';
    } else {
      hiddenInput.value = collegeSelect.value;
    }
  });

  console.debug('[editModal] initEditModal() running');
  const form = modal.querySelector('form');
  const deptWrap = document.getElementById('sy-e-college-wrap');
  const programWrap = document.getElementById('sy-e-program-wrap');
  const courseWrap = document.getElementById('sy-e-course-wrap');
  const deptSelect = document.getElementById('sy-e-college');
  const programSelect = document.getElementById('sy-e-program');

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
    if (!collegeId) collegeId = getCurrentCollegeParam();
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
    const shouldLock = form.dataset.lockCollege === '1';
    const defaultValue = form.dataset.defaultCollege || deptSelect.value;
    lockSelectElement(deptSelect, shouldLock, defaultValue || null, '(Your College)');
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
      const lockedValue = deptSelect.dataset.lockedValue || deptSelect.dataset.default || '';
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

    console.debug('[EditModal] fetchPrograms -> deptId=', depId);
    try {
      const programs = await fetchPrograms(depId);
      console.debug('[EditModal] programs payload:', programs);
      fillSelect(programSelect, programs, '— Select program —');
    } catch (err) {
      console.error('[EditModal] fetchPrograms error:', err);
      // keep UI usable by leaving program select empty
      fillSelect(programSelect, [], '— Select program —');
    }
    syncAction(getActiveTile());
  });

  modal.addEventListener('show.bs.modal', () => {
    if (form && !form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    const tile = getActiveTile();
    fillFromTile(tile);
    updateVisibility();
    applyLock();
    syncAction(tile);
  });

  modal.addEventListener('shown.bs.modal', async () => {
    const tile = getActiveTile();
    await populateDependentSelects(tile);
    updateVisibility();
    applyLock();
    syncAction(tile);
  });
}