<?php
// /public/assets/js/syllabi/editModal.js
header('Content-Type: application/javascript');
function ver($file) {
  $abs = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/js/syllabi/' . $file;
  return @filemtime($abs) ?: time();
}
?>
import {
  preselectCollege,
  preselectCourse,
  getCurrentCollegeParam,
  fillSelect,
  lockSelectElement,
} from './utils.js?v=<?=ver('utils.js')?>';
import { getActiveTile } from './state.js?v=<?=ver('state.js')?>';
import { fetchPrograms, fetchCourses } from './dataLoaders.php?v=<?=ver('dataLoaders.php')?>';

console.debug('[editModal] module loaded');

/** Chair-specific context injected by index.php (role + accessible programs). */
const SY_CONTEXT = window.SY_CONTEXT || {};
const RESTRICT_PROGRAMS = !!SY_CONTEXT.restrictPrograms;
const ACCESSIBLE_PROGRAM_SET = new Set(
  (SY_CONTEXT.accessibleProgramIds || [])
    .map((id) => Number(id))
    .filter((id) => Number.isFinite(id) && id > 0)
);

/**
 * ensureLockedProgramHiddenInput()
 * - When a chair can’t modify a program but it’s already assigned,
 *   we inject a hidden input so the mapping survives form submission.
 */
function ensureLockedProgramHiddenInput(container, programId) {
  if (!container || !programId) return;
  const existing = container.querySelector(
    `input[data-locked-program="1"][value="${CSS.escape(String(programId))}"]`
  );
  if (existing) return;

  const hidden = document.createElement('input');
  hidden.type = 'hidden';
  hidden.name = 'program_ids[]';
  hidden.value = String(programId);
  hidden.dataset.lockedProgram = '1';
  container.append(hidden);
}

/**
 * fillFromTile(tile)
 * - Copies dataset values from the currently selected syllabus tile into the edit modal form controls.
 * - tile comes from tiles.js via getActiveTile(); it exposes attributes like data-template-id, data-scope, etc.
 * - Has no return value; it mutates the DOM inputs so the modal shows the tile’s current details.
 */
function fillFromTile(tile) {
  if (!tile) return;

  const selectedProgramIds = parseProgramIds(tile.dataset.programIds || '[]');
  const assignedSet = new Set(selectedProgramIds);
  const programSelect = document.getElementById('sy-e-program');
  const hiddenPrograms = document.getElementById('sy-e-program-hidden');

  if (programSelect && programSelect.options.length) {
    const options = Array.from(programSelect.options);

    options.forEach((opt) => {
      const programId = Number(opt.value);
      const isAssigned = assignedSet.has(programId);
      const isAccessible = !RESTRICT_PROGRAMS || ACCESSIBLE_PROGRAM_SET.has(programId);

      if (!isAssigned && RESTRICT_PROGRAMS && !isAccessible) {
        opt.remove();
        return;
      }

      opt.selected = isAssigned;

      if (RESTRICT_PROGRAMS && !isAccessible) {
        opt.disabled = true;
        opt.setAttribute('disabled', 'disabled');
        opt.dataset.locked = '1';
        opt.classList.add('sy-option-locked');
        if (!opt.textContent.includes('(locked)')) {
          opt.textContent = `${opt.textContent.trim()} (locked)`;
        }
        if (isAssigned) {
          ensureLockedProgramHiddenInput(hiddenPrograms, programId);
        }
      }
    });
  }

  const get = (key, fallback = '') => tile.dataset[key] ?? fallback;

  document.getElementById('sy-e-id').value = get('syllabusId', '');
  document.getElementById('sy-e-title').value = get('title', '');
  document.getElementById('sy-e-college').value = get('collegeId', '');
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
      return parsed.map(Number).filter((id) => Number.isFinite(id) && id > 0);
    }
  } catch (_) {}
  return String(raw)
    .split(',')
    .map((id) => Number(id.trim()))
    .filter((id) => Number.isFinite(id) && id > 0);
}

/**
 * buildProgramOptions()
 * - Recreates the program multi-select while respecting chair restrictions.
 *   ▪ Accessible programs remain enabled.
 *   ▪ Assigned-but-locked programs stay selected yet disabled (and mirrored via hidden inputs).
 *   ▪ Unassigned programs outside the chair’s scope stay hidden.
 */
function buildProgramOptions(select, hiddenContainer, programs, selectedIds) {
  if (!select) return;

  const assignedSet = new Set((selectedIds || []).map(Number).filter((id) => Number.isFinite(id) && id > 0));

  select.innerHTML = '';
  if (hiddenContainer) hiddenContainer.innerHTML = '';

  const appended = new Set();

  programs.forEach((program) => {
    const id = Number(program?.id ?? program?.program_id ?? 0);
    if (!Number.isFinite(id) || id <= 0) return;

    const label = String(program?.label ?? program?.program_name ?? `Program #${id}`).trim();
    const isAssigned = assignedSet.has(id);
    const isAccessible = !RESTRICT_PROGRAMS || ACCESSIBLE_PROGRAM_SET.has(id);

    if (!isAssigned && RESTRICT_PROGRAMS && !isAccessible) {
      return; // chair can’t see unrelated programs unless already linked
    }

    const option = document.createElement('option');
    option.value = String(id);
    option.textContent = label;
    option.selected = isAssigned;

    if (RESTRICT_PROGRAMS && !isAccessible) {
      option.disabled = true;
      option.setAttribute('disabled', 'disabled');
      option.dataset.locked = '1';
      option.classList.add('sy-option-locked');
      option.textContent = `${label} (locked)`;
      if (isAssigned && hiddenContainer) {
        ensureLockedProgramHiddenInput(hiddenContainer, id);
      }
    }

    select.append(option);
    appended.add(id);
  });

  if (RESTRICT_PROGRAMS && ACCESSIBLE_PROGRAM_SET.size) {
    ACCESSIBLE_PROGRAM_SET.forEach((id) => {
      if (appended.has(id)) return;
      const option = document.createElement('option');
      option.value = String(id);
      option.textContent = `Program #${id}`;
      option.selected = assignedSet.has(id);
      select.append(option);
    });
  }
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
  const hiddenPrograms = document.getElementById('sy-e-program-hidden');
  const courseSelect = document.getElementById('sy-e-course');
  if (!collegeSelect || !programSelect || !courseSelect) return;

  const currentCollege =
    tile?.dataset.collegeId ||
    tile?.dataset.ownerDepartmentId ||
    collegeSelect?.value ||
    '';
  const selectedProgramIds = parseProgramIds(tile?.dataset.programIds || '[]');
  const currentCourse = tile?.dataset.courseId || '';

  if (!currentCollege) {
    programSelect.innerHTML = '';
    if (hiddenPrograms) hiddenPrograms.innerHTML = '';
    fillSelect(courseSelect, [], '— Select course —');
    return;
  }

  const programs = await fetchPrograms(currentCollege);
  buildProgramOptions(programSelect, hiddenPrograms, programs, selectedProgramIds);

  const courses = await fetchCourses(currentCollege);
  fillSelect(courseSelect, courses, '— Select course —');
  if (currentCourse) courseSelect.value = currentCourse;
}

/**
 * initEditModal()
 * - Called once from TemplateBuilder-Scaffold.js after DOMContentLoaded.
 * - Wires all interactions for the edit modal: fills inputs, locks college dropdowns for dean/chair, and keeps the form action scoped.
 * - No return; the side effects are event listeners and initial state sync.
 */
export default function initEditModal(modal) {
  if (!modal) return;

  const collegeSelect = modal.querySelector('#sy-e-college');
  const hiddenCollegeInput = modal.querySelector('input[name="college_id"][type="hidden"]');
  const programSelect = modal.querySelector('#sy-e-program');
  const hiddenPrograms = modal.querySelector('#sy-e-program-hidden');
  const courseSelect = modal.querySelector('#sy-e-course');
  const form = modal.querySelector('form');

  const tile = getActiveTile();

  preselectCollege(collegeSelect, hiddenCollegeInput, tile?.dataset.lockedValue || null);
  preselectCourse(courseSelect, tile?.dataset.courseId || null);
  lockSelectElement(collegeSelect, hiddenCollegeInput);

  modal.addEventListener('show.bs.modal', () => {
    if (!collegeSelect || !hiddenCollegeInput) return;
    if (collegeSelect.dataset.locked === '1') {
      collegeSelect.value = collegeSelect.dataset.lockedValue || '';
    } else {
      hiddenCollegeInput.value = collegeSelect.value;
    }
  });

  console.debug('[editModal] initEditModal() running');

  const deptSelect = collegeSelect;

  /**
   * syncAction(tile)
   * - Keeps the form’s POST target aligned with the currently selected college (ensures redirect stays scoped).
   * - tile is the active template card supplying fallback college info when the select is empty.
   */
  const syncAction = (activeTile) => {
    if (!form) return;
    if (!form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    const base = form.dataset.baseAction;
    let collegeId = deptSelect?.value || '';
    if (!collegeId && activeTile) collegeId = activeTile.dataset.ownerDepartmentId || '';
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
    const shouldLock = form.dataset.lockCollege === '1' || deptSelect.dataset.locked === '1';
    const defaultValue =
      deptSelect.dataset.lockedValue ||
      deptSelect.dataset.defaultValue ||
      form.dataset.defaultCollege ||
      deptSelect.value;
    lockSelectElement(deptSelect, hiddenCollegeInput, shouldLock, defaultValue || null, '(Your College)');
  };

  /**
   * resetProgramSelect()
   * - Clears the program dropdown; used when the chair switches colleges (rare) or no college is present.
   */
  const resetProgramSelect = () => {
    if (programSelect) programSelect.innerHTML = '';
    if (hiddenPrograms) hiddenPrograms.innerHTML = '';
  };

  /**
   * resetCourseSelect()
   * - Clears the course dropdown; used when program selection changes or no college is available.
   */
  const resetCourseSelect = () => {
    fillSelect(courseSelect, [], '— Select course —');
  };

  deptSelect?.addEventListener('change', async (event) => {
    if (deptSelect.dataset.locked === '1') {
      const lockedValue = deptSelect.dataset.lockedValue || deptSelect.dataset.defaultValue || '';
      if (lockedValue) deptSelect.value = lockedValue;
      syncAction(getActiveTile());
      return;
    }

    const depId = event.target.value || '';
    resetProgramSelect();
    resetCourseSelect();

    if (!depId) {
      syncAction(getActiveTile());
      return;
    }

    console.debug('[EditModal] fetchPrograms -> deptId=', depId);
    try {
      const programs = await fetchPrograms(depId);
      buildProgramOptions(programSelect, hiddenPrograms, programs, []);
      const courses = await fetchCourses(depId);
      fillSelect(courseSelect, courses, '— Select course —');
      if (hiddenCollegeInput) hiddenCollegeInput.value = depId;
      syncAction(getActiveTile());
    } catch (err) {
      console.error('[EditModal] fetchPrograms error:', err);
      resetProgramSelect();
    }
  });

  modal.addEventListener('show.bs.modal', () => {
    if (form && !form.dataset.baseAction) form.dataset.baseAction = form.getAttribute('action') || '';
    const activeTile = getActiveTile();
    fillFromTile(activeTile);
    applyLock();
    syncAction(activeTile);
  });

  modal.addEventListener('shown.bs.modal', async () => {
    const activeTile = getActiveTile();
    await populateDependentSelects(activeTile);
    applyLock();
    syncAction(activeTile);
  });
}