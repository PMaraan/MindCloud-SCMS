export default function sidebarCollapse(){
  const STORAGE_KEY = 'mc-sb-collapsed';
  const shell = document.getElementById('mc-shell');
  const sbToggle = document.getElementById('sb-toggle');
  const collapsed = localStorage.getItem(STORAGE_KEY) === '1';
  if (collapsed) shell.classList.add('sb-collapsed');

  sbToggle?.addEventListener('click', () => {
    shell.classList.toggle('sb-collapsed');
    localStorage.setItem(STORAGE_KEY, shell.classList.contains('sb-collapsed') ? '1' : '0');
  });
}
