// === Sidebar toggle ===
const toggleBtn  = document.getElementById('toggleBtn');
const sidebar    = document.getElementById('sidebar');
const body       = document.body;

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');       // Collapse / expand sidebar
  body.classList.toggle('sidebar-collapsed');  // Tell CSS the new state
});


// === Highlight active nav‑link ===
document.addEventListener('DOMContentLoaded', () => {
  const navLinks = document.querySelectorAll('.nav-link');

  /*--- 1. Match by current file name (e.g. dashboard.php) ---*/
  const currentFile = window.location.pathname.split('/').pop();

  navLinks.forEach(link => {
    const fileName = (link.getAttribute('href') || '').split('/').pop();
    if (fileName === currentFile) {
      link.classList.add('active');
    }
  });

  /*--- 2. Match by ?page= query parameter (e.g. ?page=index) ---*/
  const params   = new URLSearchParams(window.location.search);
  const pageSlug = params.get('page');               // null if not present

  if (pageSlug) {
    navLinks.forEach(link => {
      // Match either data-page attribute or href that contains page=
      if (
        link.dataset.page === pageSlug ||
        link.href.includes(`page=${pageSlug}`)
      ) {
        link.classList.add('active');
      }
    });
  }
});
