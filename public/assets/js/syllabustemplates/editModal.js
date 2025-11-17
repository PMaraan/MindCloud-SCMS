// /public/assets/js/syllabustemplates/editModal.js
import { fetchPrograms, fetchCourses } from './dataLoaders.js';
import { fillSelect, getCurrentCollegeParam, lockSelectElement } from './utils.js';
import { getActiveTile } from './state.js';

console.debug('[editModal] module loaded');

/**
 * fillFromTile(tile)
 * - Copies dataset values from the currently selected template tile into the edit modal form controls.
 * - tile comes from tiles.js via getActiveTile(); it exposes attributes like data-template-id, data-scope, etc.
 * - Has no return value; it mutates the DOM inputs so the modal shows the tile’s current details.
 */
function fillFromTile(tile) {
  if (!tile) return;

  const get = (key, fallback = '') => tile.dataset[key] ?? fallback;

  document.getElementById('tb-e-id').value = get('templateId', '');
  document.getElementById('tb-e-title').value = get('title', '');
  document.getElementById('tb-e-version').value = get('version', '');
  document.getElementById('tb-e-status').value = get('status', 'draft');

  const scope = (get('scope', 'global') || 'global').toLowerCase();
  const scopeMap = {
    global: 'tb-e-scope-global',
    college: 'tb-e-scope-college',
    program: 'tb-e-scope-program',
    course: 'tb-e-scope-course'
  };
  const scopeId = scopeMap[scope] || scopeMap.global;
  const scopeRadio = document.getElementById(scopeId);
  if (scopeRadio) scopeRadio.checked = true;

  document.getElementById('tb-e-college').value = get('ownerDepartmentId', '');
  document.getElementById('tb-e-program').value = get('programId', '');
  document.getElementById('tb-e-course').value = get('courseId', '');
}

/**
 * populateDependentSelects(tile)
 * - Refreshes the program/course dropdowns in the modal based on the tile’s current college/program.
 * - tile is the same source as in fillFromTile(); used here to preselect existing relationships when the modal opens.
 * - Uses fetchPrograms/fetchCourses to pull JSON data; the results are injected into the selects via fillSelect().
 */
async function populateDependentSelects(tile) {
  const deptSelect = document.getElementById('tb-e-college');
  const programSelect = document.getElementById('tb-e-program');
  const courseSelect = document.getElementById('tb-e-course');

  const currentDept = tile?.dataset.ownerDepartmentId || '';
  const currentProgram = tile?.dataset.programId || '';
  const currentCourse = tile?.dataset.courseId || '';

  if (currentDept) {
    const programs = await fetchPrograms(currentDept);
    fillSelect(programSelect, programs, '— Select program —');
    if (currentProgram) {
      programSelect.value = currentProgram;
      const courses = await fetchCourses(currentProgram);
      fillSelect(courseSelect, courses, '— Select course —');
      if (currentCourse) courseSelect.value = currentCourse;
    } else {
      fillSelect(courseSelect, [], '— Select course —');
    }
  } else {
    fillSelect(programSelect, [], '— Select program —');
    fillSelect(courseSelect, [], '— Select course —');
  }
}

/**
 * initEditModal()
 * - Called once from TemplateBuilder-Scaffold.js after DOMContentLoaded.
 * - Wires all interactions for the edit modal: fills inputs, locks college dropdowns for dean/chair, and keeps the form action scoped.
 * - No return; the side effects are event listeners and initial state sync.
 */
export default function initEditModal() {
  console.debug('[editModal] initEditModal() running');
  const modal = document.getElementById('tbEditModal');
  if (!modal) return;

  const form = modal.querySelector('form');
  const scopeRadios = form?.querySelectorAll('input[name="scope"]');
  const deptWrap = document.getElementById('tb-e-college-wrap');
  const programWrap = document.getElementById('tb-e-program-wrap');
  const courseWrap = document.getElementById('tb-e-course-wrap');
  const deptSelect = document.getElementById('tb-e-college');
  const programSelect = document.getElementById('tb-e-program');
  const courseSelect = document.getElementById('tb-e-course');

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
   * updateVisibility()
   * - Shows or hides the college/program/course selectors depending on the chosen scope radio button.
   * - Also toggles the required attribute and resets downstream selects when the scope changes.
   */
  const updateVisibility = () => {
    if (!form) return;
    const scope = form.querySelector('input[name="scope"]:checked')?.value || 'global';
    const showCollege = ['college', 'program', 'course'].includes(scope);
    const showProgram = ['program', 'course'].includes(scope);
    const showCourse = scope === 'course';

    deptWrap?.classList.toggle('d-none', !showCollege);
    programWrap?.classList.toggle('d-none', !showProgram);
    courseWrap?.classList.toggle('d-none', !showCourse);

    if (deptSelect) deptSelect.required = showCollege;
    if (programSelect) programSelect.required = showProgram;
    if (courseSelect) courseSelect.required = showCourse;

    if (!showProgram) fillSelect(programSelect, [], '— Select program —');
    if (!showCourse) fillSelect(courseSelect, [], '— Select course —');
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
   * - scopeRadios: on change, update visibility and apply lock
   * - deptSelect: on change, fetch programs unless locked, then sync action
   * - programSelect: on change, fetch courses unless locked, then sync action
   * - modal show: fill inputs from tile, update visibility, apply lock, sync action
   * - modal shown: populate dependent selects, update visibility, apply lock, sync action
   */
  // Scope change: update visibility/lock and (when needed) populate programs & courses.
  scopeRadios?.forEach((radio) => {
    radio.addEventListener('change', async () => {
      updateVisibility();
      applyLock();

      try {
        const scope = form.querySelector('input[name="scope"]:checked')?.value || 'global';
        const needsPrograms = scope === 'program' || scope === 'course';

        // If we don't need programs, clear downstream selects and return
        if (!needsPrograms) {
          // When switching away from program/course scope, clear program/course selects
          fillSelect(programSelect, [], '— Select program —');
          fillSelect(courseSelect, [], '— Select course —');
          syncAction(getActiveTile());
          return;
        }

        // Resolve department id (respect locked state and current select value)
        let deptId = '';
        if (deptSelect) {
          // if locked, prefer lockedValue (already applied by applyLock())
          if (deptSelect.dataset.locked === '1') {
            deptId = deptSelect.dataset.lockedValue || deptSelect.dataset.default || deptSelect.value || '';
          } else {
            deptId = deptSelect.value || '';
          }
        }

        // If no dept id in select, try falling back to active tile or current ?college param
        if (!deptId) {
          const tile = getActiveTile();
          if (tile && tile.dataset.ownerDepartmentId) deptId = tile.dataset.ownerDepartmentId;
        }
        if (!deptId) {
          const qs = getCurrentCollegeParam ? getCurrentCollegeParam() : null;
          if (qs) deptId = qs;
        }

        // If still no deptId, bail but keep UI sane (user must pick a college)
        if (!deptId) {
          console.debug('[EditModal] scope change to program/course but no college chosen yet — programs left empty');
          fillSelect(programSelect, [], '— Select program —');
          fillSelect(courseSelect, [], '— Select course —');
          syncAction(getActiveTile());
          return;
        }

        // Fetch programs and populate
        console.debug('[EditModal] scope change -> fetching programs for deptId=', deptId);
        const programs = await fetchPrograms(deptId);
        console.debug('[EditModal] scope change -> programs payload:', programs);
        fillSelect(programSelect, programs, '— Select program —');

        // If the active tile has a program preselected, try to apply it
        const tile = getActiveTile();
        const preProgram = tile?.dataset.programId || '';
        if (preProgram) {
          // robust select by value if present; otherwise set value if option was injected by fetch result
          programSelect.value = preProgram;
          // If a program is selected now, fetch courses for it
          const selectedPid = programSelect.value || '';
          if (selectedPid) {
            const courses = await fetchCourses(selectedPid);
            fillSelect(courseSelect, courses, '— Select course —');
            // preselect tile's course if present
            const preCourse = tile?.dataset.courseId || '';
            if (preCourse) courseSelect.value = preCourse;
          } else {
            fillSelect(courseSelect, [], '— Select course —');
          }
        } else {
          // no pre-program; clear courses
          programSelect.value = '';
          fillSelect(courseSelect, [], '— Select course —');
        }

        syncAction(getActiveTile());
      } catch (err) {
        console.error('[EditModal] scope change populate error:', err);
        // keep UI usable by leaving selects empty
        fillSelect(programSelect, [], '— Select program —');
        fillSelect(courseSelect, [], '— Select course —');
        syncAction(getActiveTile());
      }
    });
  });

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

  programSelect?.addEventListener('change', async (event) => {
    if (programSelect.dataset.locked === '1') {
      const lockedValue = programSelect.dataset.lockedValue || programSelect.dataset.default || '';
      if (lockedValue) programSelect.value = lockedValue;
      syncAction(getActiveTile());
      return;
    }
    const programId = event.target.value || '';
    resetCourseSelect();
    if (!programId) {
      syncAction(getActiveTile());
      return;
    }
    const courses = await fetchCourses(programId);
    fillSelect(courseSelect, courses, '— Select course —');
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