import { fetchPrograms, fetchCourses } from './dataLoaders.js';
import { fillSelect, ensureOptionAndSelect, lockSelectElement, robustSelect, getCurrentCollegeParam } from './utils.js';
import { getActiveTile } from './state.js';

/**
 * getScope()
 * - Returns the currently checked scope radio within the duplicate modal.
 * - Used by visibility/locking helpers to decide which dependent controls should appear.
 */
function getScope() {
  const ids = ['tb-d-scope-global', 'tb-d-scope-college', 'tb-d-scope-program', 'tb-d-scope-course'];
  for (const id of ids) {
    const radio = document.getElementById(id);
    if (radio?.checked) return radio.value;
  }
  return '';
}

/**
 * needsCollege(scope)
 * - Utility checker to determine whether the provided scope requires college-level context.
 * - Called by visibility controls and data loaders; expects a scope string.
 */
function needsCollege(scope) {
  return ['college', 'program', 'course'].includes(scope);
}

/**
 * updateVisibilityAndRequirements()
 * - Shows/hides the college/program/course wrappers and adjusts required flags.
 * - Triggered when the scope changes or after initial modal setup.
 */
function updateVisibilityAndRequirements() {
  const scope = getScope();
  const collegeWrap = document.getElementById('tb-d-college-wrap');
  const programWrap = document.getElementById('tb-d-program-wrap');
  const courseWrap = document.getElementById('tb-d-course-wrap');
  const collegeSelect = document.getElementById('tb-d-college');
  const programSelect = document.getElementById('tb-d-program');
  const courseSelect = document.getElementById('tb-d-course');

  const showCollege = needsCollege(scope);
  const showProgram = scope === 'program' || scope === 'course';
  const showCourse = scope === 'course';

  collegeWrap?.classList.toggle('d-none', !showCollege);
  programWrap?.classList.toggle('d-none', !showProgram);
  courseWrap?.classList.toggle('d-none', !showCourse);

  if (collegeSelect) collegeSelect.required = showCollege;
  if (programSelect) programSelect.required = showProgram;
  if (courseSelect) courseSelect.required = showCourse;

  if (showProgram && !programSelect.value) {
    fillSelect(programSelect, [], '— Select program —');
  }
  if (showCourse && !courseSelect.value) {
    fillSelect(courseSelect, [], '— Select course —');
  }
}

/**
 * fillFromTile(tile)
 * - Prefills duplicate-modal inputs using the active template tile’s dataset.
 * - Called when the modal opens; also hydrates program/course selects via fetches.
 * - Returns the resolved department/program/course IDs for downstream use.
 */
async function fillFromTile(tile) {
  if (!tile) {
    fillSelect(document.getElementById('tb-d-program'), [], '— Select program —');
    fillSelect(document.getElementById('tb-d-course'), [], '— Select course —');
    return { depId: '', programId: '', courseId: '' };
  }

  const get = (key, fallback = '') => tile.dataset[key] ?? fallback;

  const sourceInput = document.getElementById('tb-d-src-id');
  if (sourceInput) sourceInput.value = get('templateId', '');

  const titleInput = document.getElementById('tb-d-title');
  if (titleInput && !titleInput.value) {
    const title = get('title', 'Untitled');
    titleInput.value = `Copy of ${title}`;
  }

  const scope = (get('scope', 'global') || 'global').toLowerCase();
  const scopeMap = {
    global: 'tb-d-scope-global',
    college: 'tb-d-scope-college',
    program: 'tb-d-scope-program',
    course: 'tb-d-scope-course'
  };
  const scopeId = scopeMap[scope] || scopeMap.global;
  const scopeRadio = document.getElementById(scopeId);
  if (scopeRadio && !scopeRadio.disabled) scopeRadio.checked = true;

  const depId = String(get('ownerDepartmentId', '') || '');
  const programId = String(get('programId', '') || '');
  const courseId = String(get('courseId', '') || '');

  const collegeSelect = document.getElementById('tb-d-college');
  const programSelect = document.getElementById('tb-d-program');
  const courseSelect = document.getElementById('tb-d-course');

  if (collegeSelect && depId) {
    await robustSelect(collegeSelect, depId, { injectIfMissing: true, labelIfInjected: '(Selected college)' });
  }

  if (depId) {
    const programs = await fetchPrograms(depId);
    fillSelect(programSelect, programs, '— Select program —');
    if (programId) {
      programSelect.value = programId;
      const courses = await fetchCourses(programId);
      fillSelect(courseSelect, courses, '— Select course —');
      if (courseId) courseSelect.value = courseId;
    } else {
      fillSelect(courseSelect, [], '— Select course —');
    }
  } else {
    fillSelect(programSelect, [], '— Select program —');
    fillSelect(courseSelect, [], '— Select course —');
  }

  return { depId, programId, courseId };
}

/**
 * refreshProgramCourse(selectIds, defaults)
 * - Regenerates program/course lists when scope or college changes.
 * - selectIds: element id map; defaults: optional { programId, courseId } for pre-selection.
 * - Uses fetchPrograms/fetchCourses underneath; updates the DOM selects directly.
 */
async function refreshProgramCourse(selectIds, defaults) {
  const { collegeSelectId, programSelectId, courseSelectId } = selectIds;
  const collegeSelect = document.getElementById(collegeSelectId);
  const programSelect = document.getElementById(programSelectId);
  const courseSelect = document.getElementById(courseSelectId);

  const scope = getScope();
  if (!needsCollege(scope) || !collegeSelect?.value) {
    fillSelect(programSelect, [], '— Select program —');
    fillSelect(courseSelect, [], '— Select course —');
    return;
  }

  const programs = await fetchPrograms(collegeSelect.value);
  fillSelect(programSelect, programs, '— Select program —');
  if (defaults.programId) {
    programSelect.value = defaults.programId;
  }

  if (scope === 'course' && (programSelect.value || defaults.programId)) {
    const courses = await fetchCourses(programSelect.value || defaults.programId);
    fillSelect(courseSelect, courses, '— Select course —');
    if (defaults.courseId) courseSelect.value = defaults.courseId;
  } else {
    fillSelect(courseSelect, [], '— Select course —');
  }
}

/**
 * initDuplicateModal()
 * - Primary initializer invoked from TemplateBuilder-Scaffold.js during DOMContentLoaded.
 * - Sets up event listeners (show/shown/change) so the duplicate modal stays in sync with the selected tile and role rules.
 */
export default function initDuplicateModal() {
  const modal = document.getElementById('tbDuplicateModal');
  if (!modal) return;

  const form = document.getElementById('tb-dup-form');
  const collegeSelect = document.getElementById('tb-d-college');
  const programSelect = document.getElementById('tb-d-program');
  const scopeRadios = modal.querySelectorAll('input[name="scope"]');

  /**
   * syncAction()
   * - Adjusts the form action URL with the resolved college parameter for post-duplicate redirect.
   * - Pulls from locked/default values when necessary.
   */
  const syncAction = () => {
    if (!form) return;
    if (!form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    const base = form.dataset.baseAction;
    let collegeId = '';
    if (collegeSelect) {
      if (collegeSelect.dataset.locked === '1') {
        collegeId = collegeSelect.dataset.lockedValue || collegeSelect.value || '';
      } else {
        collegeId = collegeSelect.value || '';
      }
    }
    if (!collegeId) collegeId = getCurrentCollegeParam();
    let action = base;
    if (collegeId) action += `&college=${encodeURIComponent(collegeId)}`;
    form.setAttribute('action', action);
  };

  modal.addEventListener('show.bs.modal', async () => {
    if (form && !form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    fillSelect(programSelect, [], '— Select program —');
    fillSelect(document.getElementById('tb-d-course'), [], '— Select course —');

    const tile = getActiveTile();
    const defaults = await fillFromTile(tile);

    const defaultScope = (modal.dataset.defaultScope || '').toLowerCase();
    const defaultCollege = Number(modal.dataset.defaultCollege || 0);
    const lockCollege = ['1', 'true', 'yes'].includes((modal.dataset.lockCollege || '').toLowerCase());

    if (!getScope()) {
      const preferred =
        (defaultScope === 'college' && document.getElementById('tb-d-scope-college')) ||
        document.getElementById('tb-d-scope-college') ||
        document.getElementById('tb-d-scope-program') ||
        document.getElementById('tb-d-scope-course') ||
        document.getElementById('tb-d-scope-global');
      if (preferred && !preferred.disabled) preferred.checked = true;
    }

    if (collegeSelect && needsCollege(getScope())) {
      if ((lockCollege || !collegeSelect.value) && defaultCollege > 0) {
        await ensureOptionAndSelect(collegeSelect, defaultCollege);
      }
      lockSelectElement(collegeSelect, lockCollege, collegeSelect.value || defaultCollege);
    } else {
      lockSelectElement(collegeSelect, false);
    }

    updateVisibilityAndRequirements();
    await refreshProgramCourse(
      { collegeSelectId: 'tb-d-college', programSelectId: 'tb-d-program', courseSelectId: 'tb-d-course' },
      defaults
    );
    syncAction();
  });

  modal.addEventListener('shown.bs.modal', () => {
    const defaultCollege = Number(modal.dataset.defaultCollege || 0);
    const lockCollege = ['1', 'true', 'yes'].includes((modal.dataset.lockCollege || '').toLowerCase());

    scopeRadios.forEach((radio) =>
      radio.addEventListener('change', async () => {
        const currentScope = getScope();
        const programSelectEl = document.getElementById('tb-d-program');
        const courseSelectEl = document.getElementById('tb-d-course');

        const prevProgram = programSelectEl?.value || '';
        const prevCourse = courseSelectEl?.value || '';

        if (collegeSelect && needsCollege(currentScope)) {
          if ((lockCollege || collegeSelect.dataset.locked === '1') && defaultCollege > 0) {
            lockSelectElement(collegeSelect, true, collegeSelect.value || defaultCollege);
          } else {
            lockSelectElement(collegeSelect, false);
          }
        } else {
          lockSelectElement(collegeSelect, false);
        }

        updateVisibilityAndRequirements();
        await refreshProgramCourse(
          { collegeSelectId: 'tb-d-college', programSelectId: 'tb-d-program', courseSelectId: 'tb-d-course' },
          { programId: prevProgram, courseId: prevCourse }
        );
        syncAction();
      })
    );

    collegeSelect?.addEventListener('change', async (event) => {
      if (collegeSelect.dataset.locked === '1') {
        collegeSelect.value = collegeSelect.dataset.lockedValue || collegeSelect.value;
        syncAction();
        return;
      }
      const depId = event.target.value || '';
      fillSelect(programSelect, [], '— Select program —');
      fillSelect(document.getElementById('tb-d-course'), [], '— Select course —');
      if (!depId) return;
      const programs = await fetchPrograms(depId);
      fillSelect(programSelect, programs, '— Select program —');
    });

    programSelect?.addEventListener('change', async (event) => {
      const pid = event.target.value || '';
      fillSelect(document.getElementById('tb-d-course'), [], '— Select course —');
      if (!pid) return;
      const courses = await fetchCourses(pid);
      fillSelect(document.getElementById('tb-d-course'), courses, '— Select course —');
      syncAction();
    });
  });
}