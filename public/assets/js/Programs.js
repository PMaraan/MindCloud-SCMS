// /public/assets/js/programs.js
// JavaScript to handle Edit and Delete modals for courses
document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('EditModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', function (ev) {
    const btn = ev.relatedTarget; if (!btn) return;
    const row = btn.closest('tr'); if (!row) return;

    editModal.querySelector('#edit-id').value = row.dataset.courseId || '';
    editModal.querySelector('#edit-course_code').value = row.dataset.courseCode || '';
    editModal.querySelector('#edit-course_name').value = row.dataset.courseName || '';

    const collegeSel = editModal.querySelector('#edit-college_id');
    if (collegeSel) collegeSel.value = row.dataset.collegeId || '';

    // Preselect curricula
    const ids = (row.dataset.curriculaIds || '').split(',').map(s => s.trim()).filter(Boolean);
    const curSel = editModal.querySelector('#edit-curriculum_ids');
    if (curSel) {
      for (const opt of curSel.options) opt.selected = ids.includes(opt.value);
    }
  });
}

  const delModal = document.getElementById('DeleteModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      delModal.querySelector('#delete-id').value = row.dataset.courseId || '';
      const label = delModal.querySelector('#delete-course-label');
      if (label) label.textContent = (row.dataset.courseCode || '') + ' — ' + (row.dataset.courseName || '');
    });
  }
});
