import { fetchPrograms, fetchCourses } from './dataLoaders.js';
import { fillSelect, lockSelectElement, getCurrentCollegeParam } from './utils.js';

/**
 * initCreateModal()
 * - Bootstraps the "Create Template" modal once during DOMContentLoaded.
 * - Registers listeners so later user actions (scope/college/program changes, show/hide) keep selects and form action in sync.
 * - No return value; side-effects wire up DOM elements and event handlers.
 */
export default function initCreateModal() {
  const modal = document.getElementById('tbCreateModal');
  if (!modal) return;

  const form = modal.querySelector('form');
  const scopeRadios = form?.querySelectorAll('input[name="scope"]');
  const collegeWrap = document.getElementById('tb-college-wrap');
  const programWrap = document.getElementById('tb-program-wrap');
  const courseWrap = document.getElementById('tb-course-wrap');
  const collegeSelect = document.getElementById('tb-college');
  const programSelect = document.getElementById('tb-program');
  const courseSelect = document.getElementById('tb-course');

  /**
   * updateVisibility()
   * - Shows/hides college/program/course selects based on the chosen scope radio.
   * - Pulls scope from the modal form, then toggles wrappers and resets dependent selects.
   */
  const updateVisibility = () => {
    const scope = form?.querySelector('input[name="scope"]:checked')?.value || '';
    const needsCollege = ['college', 'program', 'course'].includes(scope);
    const needsProgram = ['program', 'course'].includes(scope);
    const needsCourse = scope === 'course';

    collegeWrap?.classList.toggle('d-none', !needsCollege);
    programWrap?.classList.toggle('d-none', !needsProgram);
    courseWrap?.classList.toggle('d-none', !needsCourse);

    if (!needsProgram) fillSelect(programSelect, [], '— Select program —');
    if (!needsCourse) fillSelect(courseSelect, [], '— Select course —');

    applyLocks();
  };

  /**
   * applyLocks()
   * - Re-applies readonly behaviour to college/program selects when roles require it.
   * - Reads the data-lock/data-default attributes rendered by PHP.
   */
  const applyLocks = () => {
    if (collegeSelect) {
      const shouldLock = collegeSelect.dataset.lock === '1';
      const value = collegeSelect.dataset.default || collegeSelect.value;
      lockSelectElement(collegeSelect, shouldLock, value);
    }
    if (programSelect) {
      const shouldLock = programSelect.dataset.lock === '1';
      const value = programSelect.dataset.default || programSelect.value;
      lockSelectElement(programSelect, shouldLock, value);
    }
  };

  /**
   * resolveCollegeForRedirect()
   * - Determines which college id to append to the form action for redirect.
   * - Prefers locked/default values, otherwise current selection.
   */
  const resolveCollegeForRedirect = () => {
    if (!collegeSelect) return '';
    if (collegeSelect.dataset.locked === '1') {
      return collegeSelect.dataset.lockedValue || collegeSelect.dataset.default || collegeSelect.value || '';
    }
    return collegeSelect.value || '';
  };

  /**
   * syncAction()
   * - Updates the form's action URL with the active college so post-submit redirects stay scoped.
   * - Called whenever relevant inputs change or when the modal opens/closes.
   */
  const syncAction = () => {
    if (!form) return;
    if (!form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    const base = form.dataset.baseAction;
    let collegeId = resolveCollegeForRedirect();
    if (!collegeId) collegeId = getCurrentCollegeParam();
    let action = base;
    if (collegeId) action += `&college=${encodeURIComponent(collegeId)}`;
    form.setAttribute('action', action);
  };

  /**
   * resetDependentSelects()
   * - Clears program/course dropdowns when the upstream college changes or modal closes.
   */
  const resetDependentSelects = () => {
    fillSelect(programSelect, [], '— Select program —');
    fillSelect(courseSelect, [], '— Select course —');
  };

  scopeRadios?.forEach((radio) => {
    radio.addEventListener('change', () => {
      updateVisibility();
      syncAction();
    });
  });

  collegeSelect?.addEventListener('change', async () => {
    if (collegeSelect.dataset.locked === '1') {
      collegeSelect.value = collegeSelect.dataset.lockedValue || collegeSelect.value;
      syncAction();
      return;
    }
    resetDependentSelects();
    const programs = await fetchPrograms(collegeSelect.value);
    fillSelect(programSelect, programs, '— Select program —');
    syncAction();
  });

  programSelect?.addEventListener('change', async () => {
    if (programSelect.dataset.locked === '1') {
      programSelect.value = programSelect.dataset.lockedValue || programSelect.value;
      syncAction();
      return;
    }
    fillSelect(courseSelect, [], '— Select course —');
    const courses = await fetchCourses(programSelect.value);
    fillSelect(courseSelect, courses, '— Select course —');
    syncAction();
  });

  modal.addEventListener('show.bs.modal', () => {
    if (form && !form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    updateVisibility();
    syncAction();
  });

  modal.addEventListener('hidden.bs.modal', () => {
    resetDependentSelects();
    syncAction();
  });
}