import { fetchJSON, getBase } from './utils.js';

/**
 * fetchPrograms(departmentId)
 * - Retrieves all programs belonging to a given college/department.
 * - Called by createModal.js, editModal.js, duplicateModal.js whenever the user selects (or reopens with) a college.
 * - departmentId: numeric or string value read from the college select.
 * - Returns an array of `{ id, label }` objects consumed by fillSelect() to populate the program dropdown.
 */
export async function fetchPrograms(departmentId) {
  const depId = Number(departmentId);
  if (!Number.isFinite(depId) || depId <= 0) return [];
  const url = `${getBase()}/dashboard?page=syllabus-templates&ajax=programs&department_id=${encodeURIComponent(depId)}`;
  const payload = await fetchJSON(url);
  if (Array.isArray(payload?.programs)) return payload.programs;
  if (Array.isArray(payload)) return payload;
  return [];
}

/**
 * fetchCourses(programId)
 * - Loads courses for the selected program so course-level templates can be scoped accurately.
 * - Triggered by the same modal scripts after the program select changes or when a modal is prefilled.
 * - programId: value taken from the program select.
 * - Returns an array of `{ id, label }` entries formatted for fillSelect(); defaults to empty array on invalid input.
 */
export async function fetchCourses(programId) {
  const progId = Number(programId);
  if (!Number.isFinite(progId) || progId <= 0) return [];
  const url = `${getBase()}/api/syllabus-templates/courses?program_id=${encodeURIComponent(progId)}`;
  const payload = await fetchJSON(url);

  const rows = Array.isArray(payload?.courses) ? payload.courses : Array.isArray(payload) ? payload : [];
  return rows.map((row) => {
    const id = row.course_id ?? row.id ?? 0;
    const code = (row.course_code ?? '').trim();
    const name = (row.course_name ?? row.name ?? row.label ?? '').trim();
    const label = code && name ? `${code} — ${name}` : (code || name || '');
    return { id, label };
  });
}