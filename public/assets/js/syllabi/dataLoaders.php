<?php
// /public/assets/js/syllabi/dataLoaders.js
header('Content-Type: application/javascript');
function ver($file) {
  $abs = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/js/syllabi/' . $file;
  return @filemtime($abs) ?: time();
}
?>
// /public/assets/js/syllabi/dataLoaders.js
import { fetchJSON, getBase } from './utils.js?v=<?=ver('utils.js')?>';

/**
 * fetchPrograms(departmentId)
 * - Retrieves programs under a given college for the Syllabi module.
 * - Returns an array of { id, label } objects.
 */
export async function fetchPrograms(departmentId) {
  const depId = Number(departmentId);
  if (!Number.isFinite(depId) || depId <= 0) return [];
  const url = `${getBase()}/dashboard?page=syllabi&action=apiprograms&department_id=${encodeURIComponent(depId)}`;
  const payload = await fetchJSON(url);
  if (Array.isArray(payload?.programs)) return payload.programs;
  if (Array.isArray(payload)) return payload;
  return [];
}

/**
 * fetchCourses(programId)
 * - Retrieves courses for the selected program in the Syllabi module.
 * - Returns an array of { id, label } objects.
 * might be deprecated in favor of fetchCourses(collegeId)
 */
/*
export async function fetchCourses(programId) {
  const progId = Number(programId);
  if (!Number.isFinite(progId) || progId <= 0) return [];
  const url = `${getBase()}/dashboard?page=syllabi&action=apicourses&program_id=${encodeURIComponent(progId)}`;
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
*/

/**
 * fetchCourses(collegeId)
 * - Retrieves courses for the selected college in the Syllabi module.
 * - Returns an array of { id, label } objects.
 */
export async function fetchCourses(collegeId) {
  if (!collegeId) return [];
  const url = `${getBase()}/dashboard?page=syllabi&action=apicourses&college_id=${encodeURIComponent(collegeId)}`;
  const payload = await fetchJSON(url);
  return payload?.courses ?? [];
}