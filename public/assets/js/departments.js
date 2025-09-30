// public/assets/js/departments.js
(() => {
  'use strict';

  const val = (row, name) => row?.getAttribute(`data-${name}`) || '';

  document.addEventListener('DOMContentLoaded', () => {
    // ----- EDIT MODAL -----
    const editModal = document.getElementById('editDepartmentsModal');
    if (editModal && !editModal.dataset.bound) {
      editModal.dataset.bound = '1';

      editModal.addEventListener('show.bs.modal', (evt) => {
        const btn = evt.relatedTarget; if (!btn) return;
        const row = btn.closest('tr'); if (!row) return;

        const id              = row.getAttribute('data-id') || '';
        const shortName       = row.getAttribute('data-short_name') || '';
        const departmentName  = row.getAttribute('data-department_name') || '';
        const deanIdNo        = row.getAttribute('data-dean_id_no') || '';
        const isCollege       = (row.getAttribute('data-is_college') || '0') === '1';

        editModal.querySelector('[name="id"]').value = id;
        editModal.querySelector('[name="short_name"]').value = shortName;
        editModal.querySelector('[name="department_name"]').value = departmentName;

        const deanSelect = editModal.querySelector('[name="dean_id_no"]');
        if (deanSelect) {
          deanSelect.value = deanIdNo || '';
          if (deanIdNo && ![...deanSelect.options].some(o => o.value === deanIdNo)) {
            const opt = document.createElement('option');
            opt.value = deanIdNo;
            opt.textContent = deanIdNo + ' — (not in list)';
            deanSelect.appendChild(opt);
            deanSelect.value = deanIdNo;
          }
        }

        const isCollegeCheckbox = editModal.querySelector('#edit-is-college');
        if (isCollegeCheckbox) isCollegeCheckbox.checked = isCollege;
      });
    }

    // ----- DELETE MODAL -----
    const delModal = document.getElementById('deleteDepartmentsModal');
    if (delModal && !delModal.dataset.bound) {
      delModal.dataset.bound = '1';

      delModal.addEventListener('show.bs.modal', (evt) => {
        const btn = evt.relatedTarget; if (!btn) return;
        const row = btn.closest('tr'); if (!row) return;

        const id             = val(row, 'id');
        const shortName      = val(row, 'short_name');
        const departmentName = val(row, 'department_name');

        const idInput = delModal.querySelector('input[name="id"]');
        if (idInput) idInput.value = id;

        const sid = delModal.querySelector('.js-del-id');    if (sid) sid.textContent = id;
        const ss  = delModal.querySelector('.js-del-short'); if (ss)  ss.textContent  = shortName;
        const sn  = delModal.querySelector('.js-del-name');  if (sn)  sn.textContent  = departmentName;
      });
    }
  });
})();
