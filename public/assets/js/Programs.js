// public/assets/js/modules/programs.js
document.addEventListener('DOMContentLoaded', function () {
  // EDIT
  const editModal = document.getElementById('editProgramModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      const idInput     = editModal.querySelector('#progEditId');
      const nameInput   = editModal.querySelector('#progEditName');
      const collegeSel  = editModal.querySelector('#progEditCollege');

      if (idInput)    idInput.value    = row.dataset.programId || '';
      if (nameInput)  nameInput.value  = row.dataset.programName || '';
      if (collegeSel) collegeSel.value = row.dataset.collegeId || '';
    });
  }

  // DELETE
  const delModal = document.getElementById('deleteProgramModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', function (ev) {
      const btn = ev.relatedTarget; if (!btn) return;
      const row = btn.closest('tr'); if (!row) return;

      const idInput = delModal.querySelector('#progDelId');
      const label   = delModal.querySelector('#progDelName');

      if (idInput) idInput.value = row.dataset.programId || '';
      if (label)   label.textContent = row.dataset.programName || '';
    });
  }
});
