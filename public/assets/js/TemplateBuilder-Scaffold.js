// /public/assets/js/TemplateBuilder-Scaffold.js
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const grid = document.getElementById('tb-grid');
    const infoBlock = document.getElementById('tb-info');
    const iTitle = document.getElementById('tb-i-title');
    const iCourse = document.getElementById('tb-i-course');
    const iOwner = document.getElementById('tb-i-owner');
    const iUpdated = document.getElementById('tb-i-updated');
    const btnOpen = document.getElementById('tb-open');
    const btnDup = document.getElementById('tb-duplicate');

    let selectedId = null;

    if (grid) {
      grid.addEventListener('click', function (ev) {
        const card = ev.target.closest('.tb-card');
        if (!card) return;

        selectedId = card.dataset.templateId;
        iTitle.textContent = card.dataset.title || '';
        iCourse.textContent = card.dataset.courseCode || '';
        iOwner.textContent = card.dataset.owner || '';
        iUpdated.textContent = card.dataset.updated || '';

        infoBlock.classList.remove('d-none');
      });
    }

    if (btnOpen) {
      btnOpen.addEventListener('click', function () {
        if (!selectedId) return;
        // Hook real navigation later — for now just highlight it
        alert('Open template ID: ' + selectedId);
      });
    }

    if (btnDup) {
      btnDup.addEventListener('click', function () {
        if (!selectedId) return;
        // Hook duplication later
        alert('Duplicate template ID: ' + selectedId);
      });
    }

    // Optional: refresh/new template buttons (stub)
    const btnRefresh = document.getElementById('tb-refresh');
    const btnNew = document.getElementById('tb-new-template');
    if (btnRefresh) btnRefresh.addEventListener('click', () => location.reload());
    if (btnNew) btnNew.addEventListener('click', () => alert('New template (wire later)'));
  });
})();
