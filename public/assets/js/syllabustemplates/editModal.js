import { fetchPrograms, fetchCourses } from './dataLoaders.js';
import { fillSelect, getCurrentCollegeParam } from './utils.js';
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

  modal.addEventListener('show.bs.modal', () => {
    if (form && !form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    const tile = getActiveTile();
    fillFromTile(tile);
    syncAction(tile);
  });

  modal.addEventListener('shown.bs.modal', async () => {
    const tile = getActiveTile();
    await populateDependentSelects(tile);
    syncAction(tile);
  });

  deptSelect?.addEventListener('change', async (event) => {
    const depId = event.target.value || '';
    fillSelect(programSelect, [], '— Select program —');
    fillSelect(courseSelect, [], '— Select course —');
    if (!depId) return;
    const programs = await fetchPrograms(depId);
    fillSelect(programSelect, programs, '— Select program —');
    syncAction(getActiveTile());
  });

  programSelect?.addEventListener('change', async (event) => {
    const programId = event.target.value || '';
    fillSelect(courseSelect, [], '— Select course —');
    if (!programId) return;
    const courses = await fetchCourses(programId);
    fillSelect(courseSelect, courses, '— Select course —');
    syncAction(getActiveTile());
  });
}