(function () {
  function api(path) {
    const base = (window.__BASE_PATH__ || '').replace(/\/+$/, '');
    return `${base}${path}`;
  }

  function applyDark(enabled) {
    const on = !!enabled;
    // Toggle on BOTH html and body to avoid selector/specificity surprises
    document.documentElement.classList.toggle('dark-mode', on);
    document.body.classList.toggle('dark-mode', on);
  }

  // Paint hint from localStorage (prevents flash)
  try {
    if (localStorage.getItem('dark_mode') === '1') applyDark(true);
  } catch (_) {}

  document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('toggleDarkMode');
    if (!btn) return;

    // Read from DB
    fetch(api('/api/settings/get?key=dark_mode'), { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : Promise.reject(r.status))
      .then(j => { if (j && j.ok) applyDark(j.enabled === true); })
      .catch(() => { /* ignore first-paint errors */ });

    btn.addEventListener('click', () => {
      const willEnable = !document.documentElement.classList.contains('dark-mode');
      applyDark(willEnable);

      try { localStorage.setItem('dark_mode', willEnable ? '1' : '0'); } catch (_) {}

      fetch(api('/api/settings/save'), {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ key: 'dark_mode', value: willEnable ? '1' : '0' })
      }).catch(() => {});
    });
  });
})();
