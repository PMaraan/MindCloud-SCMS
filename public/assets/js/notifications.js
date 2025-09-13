// /public/assets/js/notifications.js
// Robust loader for the bell dropdown: fetches 5 latest notifications,
// handles edge cases where Bootstrap events or Popper are not firing as expected.

(function () {
  // --- Grab elements
  const toggle = document.getElementById('notifDropdown');
  if (!toggle) return;

  const dropdownRoot = toggle.closest('.dropdown'); // <li class="nav-item dropdown">
  const menu = dropdownRoot ? dropdownRoot.querySelector('.dropdown-menu') : null;
  if (!menu) return;

  let list = menu.querySelector('#notif-items');
  let loading = menu.querySelector('#notif-loading');
  const badge = document.getElementById('notif-badge');

  // Ensure containers exist
  if (!list) {
    list = document.createElement('li');
    list.id = 'notif-items';
    menu.insertBefore(list, menu.lastElementChild); // before footer
  }
  if (!loading) {
    loading = document.createElement('li');
    loading.id = 'notif-loading';
    loading.className = 'py-3 text-center text-muted small';
    loading.textContent = 'Loading…';
    menu.insertBefore(loading, menu.firstElementChild);
  }

  // --- Helpers
  function timeAgo(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    const m = Math.floor(diff / 60);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    const days = Math.floor(h / 24);
    return `${days}d ago`;
  }

  function showFailed(msg) {
    if (loading) loading.remove();
    list.innerHTML = '';
    const li = document.createElement('li');
    li.className = 'py-3 text-center text-danger small';
    li.textContent = msg || 'Failed to load notifications';
    // insert before footer (last li)
    menu.insertBefore(li, menu.lastElementChild);
  }

  function render(items) {
    if (loading) loading.remove();
    list.innerHTML = '';

    if (!Array.isArray(items) || items.length === 0) {
      const li = document.createElement('li');
      li.className = 'py-3 text-center text-muted small';
      li.textContent = 'No notifications';
      menu.insertBefore(li, menu.lastElementChild);
      if (badge) badge.classList.add('d-none');
      return;
    }

    const unread = items.filter(n => !n.is_read).length;
    if (badge) {
      if (unread > 0) {
        badge.textContent = String(unread);
        badge.classList.remove('d-none');
      } else {
        badge.classList.add('d-none');
      }
    }

    items.forEach(n => {
      const li = document.createElement('li');

      const a = document.createElement('a');
      a.className = 'dropdown-item py-2';
      a.href = n.url || '#';

      const title = document.createElement('div');
      title.className = 'fw-semibold text-truncate';
      title.textContent = n.title || '(no title)';

      const meta = document.createElement('div');
      meta.className = 'small text-muted d-flex justify-content-between gap-2';
      const left = document.createElement('span');
      left.className = 'text-truncate';
      left.textContent = n.body ? n.body : '';
      const right = document.createElement('span');
      right.textContent = n.created_at ? timeAgo(n.created_at) : '';
      meta.append(left, right);

      if (!n.is_read) a.classList.add('bg-light');

      a.append(title, meta);
      li.append(a);
      list.append(li);
    });
  }

  // --- Fetcher
  async function loadLatest() {
    try {
      // Add a fresh loading row if needed
      if (!menu.querySelector('#notif-loading')) {
        const li = document.createElement('li');
        li.id = 'notif-loading';
        li.className = 'py-3 text-center text-muted small';
        li.textContent = 'Loading…';
        menu.insertBefore(li, menu.firstElementChild);
        loading = li;
      }

      const basePath = toggle.dataset.basePath || '';
      const url = `${basePath}/notifications/latest`;

      const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });

      if (!res.ok) {
        // Surface status to help debugging (401/404/etc.)
        showFailed(`Failed (${res.status})`);
        return;
      }

      const data = await res.json();
      render(Array.isArray(data?.items) ? data.items : []);
    } catch (e) {
      showFailed('Failed to load notifications');
    }
  }

  // --- Bind robustly

  // 1) Preferred: Bootstrap event on root .dropdown (fires when opening)
  dropdownRoot.addEventListener('show.bs.dropdown', loadLatest);

  // 2) Fallback: click on the toggle (in case events are blocked by something custom)
  toggle.addEventListener('click', function () {
    // If menu is not shown yet, try to pre-load
    if (!menu.classList.contains('show')) {
      loadLatest();
    }
  });

  // 3) Fallback: observe when Bootstrap adds the "show" class (MutationObserver)
  const obs = new MutationObserver((mutations) => {
    for (const m of mutations) {
      if (m.type === 'attributes' && m.attributeName === 'class') {
        if (menu.classList.contains('show')) {
          loadLatest();
        }
      }
    }
  });
  obs.observe(menu, { attributes: true });

  // 4) Defensive: if devs manually instantiate, keep it compatible
  try {
    if (window.bootstrap?.Dropdown) {
      // Do not force open; just ensure the instance exists
      new window.bootstrap.Dropdown(toggle, { autoClose: 'outside', display: 'static' });
    }
  } catch {}
})();
