// /public/assets/js/notifications.js
// Loads 5 latest notifications into the bell dropdown and manages the badge.

(function () {
  const dropdown = document.getElementById('notifDropdown');
  if (!dropdown) return;

  const menu = dropdown.nextElementSibling;
  const list = menu?.querySelector('#notif-items');
  const loading = menu?.querySelector('#notif-loading');
  const badge = document.getElementById('notif-badge');

  // Format time-ago text from ISO date
  function timeAgo(iso) {
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

  // Render notifications into the dropdown
  function render(items) {
    // clear loading
    if (loading) loading.remove();

    // clear previous list
    if (list) list.innerHTML = '';

    if (!items || items.length === 0) {
      const li = document.createElement('li');
      li.className = 'py-3 text-center text-muted small';
      li.textContent = 'No notifications';
      menu.insertBefore(li, menu.lastElementChild); // before footer
      return;
    }

    // Update badge (count of unread among the five)
    const unread = items.filter(n => !n.is_read).length;
    if (badge) {
      if (unread > 0) {
        badge.textContent = String(unread);
        badge.classList.remove('d-none');
      } else {
        badge.classList.add('d-none');
      }
    }

    // Build list items
    items.forEach(n => {
      // Each item lives inside a <li> within the UL .dropdown-menu (Bootstrap 5 pattern)
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
      left.textContent = n.body ? n.body : '';
      left.classList.add('text-truncate');
      const right = document.createElement('span');
      right.textContent = n.created_at ? timeAgo(n.created_at) : '';
      meta.append(left, right);

      if (!n.is_read) {
        // subtle unread indicator
        a.classList.add('bg-light');
      }

      a.append(title, meta);
      li.append(a);
      if (list) list.append(li);
    });
  }

  // Fetch latest notifications (top 5)
  async function loadLatest() {
    // show a loading row if not present
    if (loading && !menu.contains(loading)) {
      const li = document.createElement('li');
      li.id = 'notif-loading';
      li.className = 'py-3 text-center text-muted small';
      li.textContent = 'Loading…';
      menu.insertBefore(li, menu.firstElementChild);
    }

    try {
      const basePath = dropdown?.dataset?.basePath || '';
      const res = await fetch(`${basePath}/notifications/latest`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      render(Array.isArray(data?.items) ? data.items : []);
    } catch (e) {
      if (loading) loading.remove();
      const li = document.createElement('li');
      li.className = 'py-3 text-center text-danger small';
      li.textContent = 'Failed to load notifications';
      menu.insertBefore(li, menu.lastElementChild);
    }
  }

  // Load on open
  menu?.addEventListener('show.bs.dropdown', loadLatest);
  // NOTE: If your Bootstrap is the bundle, the event name is 'show.bs.dropdown' on the .dropdown element.
})();
