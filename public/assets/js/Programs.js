(function () {
  const editModal = document.getElementById('editProgramModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function (e) {
      const btn = e.relatedTarget;
      document.getElementById('progEditId').value       = btn.getAttribute('data-program-id');
      document.getElementById('progEditName').value     = btn.getAttribute('data-program-name');
      document.getElementById('progEditCollege').value  = btn.getAttribute('data-college-id') || '';
    });
  }

  const delModal = document.getElementById('deleteProgramModal');
  if (delModal) {
    delModal.addEventListener('show.bs.modal', function (e) {
      const btn = e.relatedTarget;
      document.getElementById('progDelId').value = btn.getAttribute('data-program-id');
      document.getElementById('progDelName').textContent = btn.getAttribute('data-program-name');
    });
  }
})();
