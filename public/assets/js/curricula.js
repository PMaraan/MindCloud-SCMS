// /public/assets/js/curricula.js
// Wires Edit and Delete modals for Curricula.
document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('EditModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      editModal.querySelector('#edit-id').value = row.dataset.curriculumId || '';
      editModal.querySelector('#edit-curriculum_code').value = row.dataset.curriculumCode || '';
      editModal.querySelector('#edit-title').value = row.dataset.title || '';
      editModal.querySelector('#edit-start').value = row.dataset.start || '';
      editModal.querySelector('#edit-end').value = row.dataset.end || '';
    });
  }

  const deleteModal = document.getElementById('DeleteModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      const id = row.dataset.curriculumId || '';
      const code = row.dataset.curriculumCode || '';
      const title = row.dataset.title || '';

      deleteModal.querySelector('#delete-id').value = id;
      deleteModal.querySelector('#delete-id-display').textContent = id || '—';
      deleteModal.querySelector('#delete-code-display').textContent = code || '—';
      deleteModal.querySelector('#delete-title-display').textContent = title || '—';
    });
  }
});
