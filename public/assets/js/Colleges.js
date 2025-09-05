// public/assets/js/modules/colleges.js
(() => {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    // ----- EDIT MODAL -----
    const editModal = document.getElementById('editCollegesModal');
    if (editModal && !editModal.dataset.bound) {
      editModal.dataset.bound = '1';

      editModal.addEventListener('show.bs.modal', (evt) => {
        const btn = evt.relatedTarget; if (!btn) return;

        const id          = btn.getAttribute('data-id') || '';
        const shortName   = btn.getAttribute('data-short_name') || '';
        const collegeName = btn.getAttribute('data-college_name') || '';
        const deanIdNo    = btn.getAttribute('data-dean_id_no') || '';

        const idInput          = editModal.querySelector('[name="id"]');
        const shortNameInput   = editModal.querySelector('[name="short_name"]');
        const collegeNameInput = editModal.querySelector('[name="college_name"]');
        const deanSelect       = editModal.querySelector('[name="dean_id_no"]');

        if (idInput)          idInput.value = id;
        if (shortNameInput)   shortNameInput.value = shortName;
        if (collegeNameInput) collegeNameInput.value = collegeName;

        if (deanSelect) {
          deanSelect.value = deanIdNo || '';
          // If the current dean isn't in options (edge case), add a temp option
          if (deanIdNo && ![...deanSelect.options].some(o => o.value === deanIdNo)) {
            const opt = document.createElement('option');
            opt.value = deanIdNo;
            opt.textContent = deanIdNo + ' — (not in list)';
            deanSelect.appendChild(opt);
            deanSelect.value = deanIdNo;
          }
        }
      });
    }

    // ----- DELETE MODAL -----
    const delModal = document.getElementById('deleteCollegesModal');
    if (delModal && !delModal.dataset.bound) {
      delModal.dataset.bound = '1';

      delModal.addEventListener('show.bs.modal', (evt) => {
        const btn = evt.relatedTarget; if (!btn) return;

        const id          = btn.getAttribute('data-id') || '';
        const shortName   = btn.getAttribute('data-short_name') || '';
        const collegeName = btn.getAttribute('data-college_name') || '';

        const idInput = delModal.querySelector('input[name="id"]');
        if (idInput) idInput.value = id;

        const sid = delModal.querySelector('.js-del-id');    if (sid) sid.textContent = id;
        const ss  = delModal.querySelector('.js-del-short'); if (ss)  ss.textContent  = shortName;
        const sn  = delModal.querySelector('.js-del-name');  if (sn)  sn.textContent  = collegeName;
      });
    }
  });
})();
