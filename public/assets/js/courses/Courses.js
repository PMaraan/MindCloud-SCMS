// /public/assets/js/courses/Courses.js
// JavaScript to handle Edit and Delete modals for courses 
document.addEventListener('DOMContentLoaded', () => {
  const CONTEXT = window.COURSE_CONTEXT || {};
  const createModal = document.getElementById('CreateModal');
  const editModal = document.getElementById('EditModal');
  const deleteModal = document.getElementById('DeleteModal');

  const lockCollegeSelect = (select) => {
    if (!select || select.dataset.locked !== '1') {
      return;
    }
    const lockedValue = select.dataset.lockedValue || '';
    if (lockedValue !== '') {
      select.value = lockedValue;
    }
    if (select.dataset.lockListenerAttached === '1') {
      return;
    }
    select.addEventListener('change', () => {
      if (lockedValue !== '') {
        select.value = lockedValue;
      }
    });
    select.dataset.lockListenerAttached = '1';
    select.classList.add('is-readonly');
  };

  const parseProfessorData = (raw) => {
    if (!raw) {
      return [];
    }
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
      console.warn('[Courses] Failed to parse professor dataset', err);
      return [];
    }
  };

  const applyProfessorSelection = (select, assigned) => {
    if (!select) {
      return;
    }

    Array.from(select.querySelectorAll('option[data-dynamic="1"]')).forEach((opt) => opt.remove());
    const options = Array.from(select.options);
    const assignedMap = new Map();

    assigned.forEach((entry) => {
      const id = String(entry?.id ?? '');
      if (!id) return;
      const label = entry?.name ? String(entry.name) : id;
      assignedMap.set(id, label);
    });

    options.forEach((opt) => {
      opt.selected = assignedMap.has(opt.value);
    });

    assignedMap.forEach((label, id) => {
      if (!select.querySelector(`option[value="${CSS.escape(id)}"]`)) {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = label;
        opt.selected = true;
        opt.dataset.dynamic = '1';
        select.appendChild(opt);
      }
    });
  };

  if (createModal) {
    createModal.addEventListener('show.bs.modal', () => {
      const collegeSel = createModal.querySelector('#create-college_id');
      if (collegeSel && collegeSel.dataset.lockedValue) {
        collegeSel.value = collegeSel.dataset.lockedValue;
      }
      lockCollegeSelect(collegeSel);

      const profSelect = createModal.querySelector('#create-professor_ids');
      if (profSelect) {
        Array.from(profSelect.options).forEach((opt) => (opt.selected = false));
      }
    });
  }

  if (editModal) {
    editModal.addEventListener('show.bs.modal', (event) => {
      const btn = event.relatedTarget;
      if (!btn) return;
      const row = btn.closest('tr');
      if (!row) return;

      editModal.querySelector('#edit-id').value = row.dataset.courseId || '';
      editModal.querySelector('#edit-course_code').value = row.dataset.courseCode || '';
      editModal.querySelector('#edit-course_name').value = row.dataset.courseName || '';

      const collegeSel = editModal.querySelector('#edit-college_id');
      if (collegeSel) {
        const rowCollege = row.dataset.collegeId || '';
        if (rowCollege) {
          collegeSel.value = rowCollege;
        } else if (collegeSel.dataset.lockedValue) {
          collegeSel.value = collegeSel.dataset.lockedValue;
        }
        lockCollegeSelect(collegeSel);
      }

      const curriculaIds = (row.dataset.curriculaIds || '')
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean);

      const curriculaSelect = editModal.querySelector('#edit-curriculum_ids');
      if (curriculaSelect) {
        Array.from(curriculaSelect.options).forEach((opt) => {
          opt.selected = curriculaIds.includes(opt.value);
        });
      }

      const professorSelect = editModal.querySelector('#edit-professor_ids');
      if (professorSelect) {
        const assigned = parseProfessorData(row.dataset.professors || '[]');
        applyProfessorSelection(professorSelect, assigned);
      }
    });
  }

  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', (event) => {
      const btn = event.relatedTarget;
      if (!btn) return;
      const row = btn.closest('tr');
      if (!row) return;

      deleteModal.querySelector('#delete-id').value = row.dataset.courseId || '';
      const label = deleteModal.querySelector('#delete-course-label');
      if (label) {
        label.textContent = `${row.dataset.courseCode || ''} — ${row.dataset.courseName || ''}`;
      }
    });
  }
});