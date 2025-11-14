import { fetchPrograms, fetchCourses } from './dataLoaders.js';
import { fillSelect, getCurrentCollegeParam, lockSelectElement } from './utils.js';
import { getActiveTile } from './state.js';

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

export default function initEditModal() {
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

  const applyLock = () => {
    if (!form || !deptSelect) return;
    const shouldLock = form.dataset.lockCollege === '1';
    const defaultValue = form.dataset.defaultCollege || deptSelect.value;
    lockSelectElement(deptSelect, shouldLock, defaultValue || null, '(Your College)');
  };

  const resetCourseSelect = () => {
    fillSelect(courseSelect, [], '— Select course —');
  };

  const resetProgramSelect = () => {
    fillSelect(programSelect, [], '— Select program —');
    resetCourseSelect();
  };

  scopeRadios?.forEach((radio) => {
    radio.addEventListener('change', () => {
      updateVisibility();
      applyLock();
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
    const programs = await fetchPrograms(depId);
    fillSelect(programSelect, programs, '— Select program —');
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