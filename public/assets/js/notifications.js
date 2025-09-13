// /public/assets/js/notifications.js
// - Loads the latest 5 notifications when the bell opens.
// - Polls unread count periodically to keep the red badge live.

(function () {
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

  // Helpers
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

  function setBadge(count) {
    if (!badge) return;
    if (count > 0) {
      badge.textContent = String(count);
      badge.classList.remove('d-none');
    } else {
      badge.classList.add('d-none');
    }
  }

  function showFailed(msg) {
    if (loading) loading.remove();
    list.innerHTML = '';
    const li = document.createElement('li');
    li.className = 'py-3 text-center text-danger small';
    li.textContent = msg || 'Failed to load notifications';
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
      return;
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

  // Networking
  let latestAbort = null;
  async function loadLatest() {
    try {
      // (Re)show loading row
      if (!menu.querySelector('#notif-loading')) {
        const li = document.createElement('li');
        li.id = 'notif-loading';
        li.className = 'py-3 text-center text-muted small';
        li.textContent = 'Loading…';
        menu.insertBefore(li, menu.firstElementChild);
        loading = li;
      }

      if (latestAbort) latestAbort.abort();
      latestAbort = new AbortController();

      const basePath = toggle.dataset.basePath || '';
      const url = `${basePath}/notifications/latest`;

      const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
        signal: latestAbort.signal,
      });

      if (!res.ok) {
        showFailed(`Failed (${res.status})`);
        return;
      }

      const data = await res.json();
      render(Array.isArray(data?.items) ? data.items : []);
    } catch (e) {
      if (e.name !== 'AbortError') {
        showFailed('Failed to load notifications');
      }
    }
  }

  let countAbort = null;
  async function loadUnreadCount() {
    try {
      if (countAbort) countAbort.abort();
      countAbort = new AbortController();

      const basePath = toggle.dataset.basePath || '';
      const url = `${basePath}/notifications/unread-count`;

      const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
        signal: countAbort.signal,
        cache: 'no-store'
      });

      if (!res.ok) {
        // Don’t spam errors in UI; just hide badge on auth issues
        if (res.status === 401) setBadge(0);
        return;
      }

      const data = await res.json();
      const total = Number(data?.total_unread ?? 0);
      setBadge(Number.isFinite(total) ? total : 0);
    } catch (e) {
      // ignore transient errors; keep last known badge
    }
  }

  // Event binding
  dropdownRoot.addEventListener('show.bs.dropdown', loadLatest);

  // Fallback hookup (in case bootstrap events are blocked by other scripts):
  toggle.addEventListener('click', () => {
    if (!menu.classList.contains('show')) loadLatest();
  });

  // Polling: every 2 minutes; pause when tab is hidden; refresh on focus
  let pollTimer = null;
  function startPolling() {
    if (pollTimer) return;
    // Kick off immediately, then every 120s
    loadUnreadCount();
    pollTimer = setInterval(loadUnreadCount, 120000);
  }
  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      startPolling();
    } else {
      stopPolling();
    }
  });

  window.addEventListener('focus', loadUnreadCount);

  // Initial start
  if (document.visibilityState === 'visible') {
    startPolling();
  }

  // Ensure bootstrap instance exists (optional)
  try {
    if (window.bootstrap?.Dropdown) {
      new window.bootstrap.Dropdown(toggle, { autoClose: 'outside', display: 'static' });
    }
  } catch {}
})();
