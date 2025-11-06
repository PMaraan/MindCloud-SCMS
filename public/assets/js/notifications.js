// /public/assets/js/notifications.js
// - Dropdown shows up to 5: unread first (newest→oldest), then read (newest→oldest).
// - On open, marks the displayed unread items as read -> clears the badge.
// - Badge is kept fresh via unread-count polling.

(function () {
  async function safeGetJSON(url, opts) {
    try {
      const res = await fetch(url, Object.assign({
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store'
      }, opts || {}));
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const ct = (res.headers.get('content-type') || '').toLowerCase();
      if (!ct.includes('application/json')) {
        // read text (likely HTML) to avoid JSON parse error
        await res.text();
        throw new Error('Non-JSON response');
      }
      return await res.json();
    } catch (err) {
      // bubble a compact error; callers can decide how to react
      throw err;
    }
  }

  const toggle = document.getElementById('notifDropdown');
  if (!toggle) return;

  const dropdownRoot = toggle.closest('.dropdown');
  const menu = dropdownRoot ? dropdownRoot.querySelector('.dropdown-menu') : null;
  if (!menu) return;

  let list = menu.querySelector('#notif-items');
  let loading = menu.querySelector('#notif-loading');
  const badge = document.getElementById('notif-badge');

  // Ensure containers exist
  if (!list) {
    list = document.createElement('li');
    list.id = 'notif-items';
    menu.insertBefore(list, menu.lastElementChild);
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
      return [];
    }

    const unreadIds = [];
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

      if (!n.is_read) {
        a.classList.add('bg-light'); // visual unread
        if (Number.isInteger(n.id)) unreadIds.push(n.id);
      }

      a.append(title, meta);
      li.append(a);
      list.append(li);
    });
    return unreadIds;
  }

  // Networking
  let latestAbort = null;
  async function loadLatestAndMaybeMarkRead() {
    try {
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

      const basePath = (toggle.dataset.basePath || (window.BASE_PATH || ''));
      const url = `${basePath}/notifications/latest`;

      const data = await safeGetJSON(url, { signal: latestAbort.signal });

      const unreadIds = render(Array.isArray(data?.items) ? data.items : []);
      if (unreadIds.length > 0) {
        try {
          await markRead(unreadIds);
          list.querySelectorAll('.dropdown-item.bg-light').forEach(el => el.classList.remove('bg-light'));
          await loadUnreadCount();
        } catch { /* ignore */ }
      }
    } catch (e) {
      if (e.name !== 'AbortError') showFailed('Failed to load notifications');
    }
  }

  let countAbort = null;
  async function loadUnreadCount() {
    try {
      if (countAbort) countAbort.abort();
      countAbort = new AbortController();

      const basePath = (toggle.dataset.basePath || (window.BASE_PATH || ''));
      const url = `${basePath}/notifications/unread-count`;

      const data = await safeGetJSON(url, { signal: countAbort.signal });
      const total = Number(data?.total_unread ?? 0);
      setBadge(Number.isFinite(total) ? total : 0);
    } catch { /* transient errors ignored */ }
  }

  async function markRead(ids) {
    const basePath = (toggle.dataset.basePath || (window.BASE_PATH || ''));
    const url = `${basePath}/notifications/mark-read`;
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ ids })
    });
    if (!res.ok) throw new Error('mark-read failed');
    return res.json();
  }

  // Events
  dropdownRoot.addEventListener('show.bs.dropdown', loadLatestAndMaybeMarkRead);
  toggle.addEventListener('click', () => {
    if (!menu.classList.contains('show')) loadLatestAndMaybeMarkRead();
  });

  // Poll badge: every 2 min; pause when hidden; refresh on focus
  let pollTimer = null;
  function startPolling() {
    if (pollTimer) return;
    loadUnreadCount();
    pollTimer = setInterval(loadUnreadCount, 120000);
  }
  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') startPolling(); else stopPolling();
  });
  window.addEventListener('focus', loadUnreadCount);

  if (document.visibilityState === 'visible') startPolling();

  // Ensure bootstrap instance exists (optional)
  try {
    if (window.bootstrap?.Dropdown) {
      new window.bootstrap.Dropdown(toggle, { autoClose: 'outside', display: 'static' });
    }
  } catch {}
})();
