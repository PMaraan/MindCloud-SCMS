// /public/assets/js/Syllabi-Scaffold.js
(function () {
  function qs(sel, root=document){ return root.querySelector(sel); }
  function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

  // Wire tile → info pane (mirrors Templates scaffold behavior)
  function bindTiles() {
    const infoEmpty = qs('#sy-info-empty');
    const info = qs('#sy-info');
    const title = qs('#sy-i-title');
    const owner = qs('#sy-i-program');
    const updated = qs('#sy-i-updated');
    const status = qs('#sy-i-status');
    const btnOpen = qs('#sy-open');

    function showInfo() {
      infoEmpty?.classList.add('d-none');
      info?.classList.remove('d-none');
    }

    qsa('.sy-tile').forEach(card => {
      card.addEventListener('click', () => {
        const name  = card.getAttribute('data-title') || '';
        const prog  = card.getAttribute('data-program') || '';
        const upd   = card.getAttribute('data-updated') || '';
        const stat  = card.getAttribute('data-status') || '';
        const id    = card.getAttribute('data-syllabus-id') || '';

        if (title)  title.textContent = name;
        if (owner)  owner.textContent = prog;
        if (updated)updated.textContent = upd;
        if (status) status.textContent  = stat;

        // set Open action to RTEditor
        if (btnOpen && id) {
          const base = (typeof window.BASE_PATH === 'string') ? window.BASE_PATH : '';
          btnOpen.onclick = () => {
            window.location.href = `${base}/dashboard?page=rteditor&syllabusId=${id}`;
          };
        }
        showInfo();
      });
    });
  }

  document.addEventListener('DOMContentLoaded', bindTiles);
})();
