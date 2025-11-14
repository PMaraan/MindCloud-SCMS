export function installCssEscapeFallback() {
  if (!window.CSS || typeof window.CSS.escape !== 'function') {
    const pattern = /[{}|\\^~\[\]`"<>#%]/g;
    window.CSS = window.CSS || {};
    window.CSS.escape = (value) => String(value).replace(pattern, '\\$&');
  }
}

export function showFlashMessage(message = '', level = 'info') {
  let container = document.getElementById('tb-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'tb-toast-container';
    container.style.position = 'fixed';
    container.style.top = '1rem';
    container.style.right = '1rem';
    container.style.zIndex = 10800;
    document.body.appendChild(container);
  }

  const id = `tb-toast-${Date.now()}`;
  const toast = document.createElement('div');
  toast.className = `toast align-items-center text-bg-${level === 'danger' ? 'danger' : level === 'success' ? 'success' : 'secondary'} border-0`;
  toast.id = id;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'assertive');
  toast.setAttribute('aria-atomic', 'true');
  toast.style.minWidth = '220px';
  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${String(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;
  container.appendChild(toast);
  const instance = new bootstrap.Toast(toast, { delay: 5000 });
  toast.addEventListener('hidden.bs.toast', () => toast.remove());
  instance.show();
}

export function capitalizeForDisplay(value) {
  if (!value) return '';
  const str = String(value);
  return str.charAt(0).toUpperCase() + str.slice(1);
}

export function getBase() {
  if (typeof window.BASE_PATH === 'string' && window.BASE_PATH) return window.BASE_PATH;
  const path = window.location.pathname;
  const cut = path.indexOf('/dashboard');
  return cut > -1 ? path.slice(0, cut) : '';
}

export async function fetchJSON(url) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json' },
    cache: 'no-store'
  });

  if (!response.ok) throw new Error(`HTTP ${response.status}`);

  const contentType = (response.headers.get('content-type') || '').toLowerCase();
  if (!contentType.includes('application/json')) {
    await response.text();
    throw new Error('Non-JSON response');
  }

  return response.json();
}

export function fillSelect(select, items, placeholder = '— Select —') {
  if (!select) return;
  select.innerHTML = '';
  const opt0 = document.createElement('option');
  opt0.value = '';
  opt0.textContent = placeholder;
  select.appendChild(opt0);

  (items || []).forEach((item) => {
    const option = document.createElement('option');
    option.value = String(item.id ?? '');
    option.textContent = item.label ?? '';
    select.appendChild(option);
  });
}

export async function robustSelect(select, value, { injectIfMissing = false, labelIfInjected = '(Selected)' } = {}) {
  if (!select) return false;
  const target = String(value ?? '');
  if (!target) return false;

  const tryPick = () => {
    const option = select.querySelector(`option[value="${CSS.escape(target)}"]`);
    if (option) {
      select.value = target;
      Array.from(select.options).forEach((o) => { o.selected = o.value === target; });
      return true;
    }
    return false;
  };

  if (tryPick()) return true;
  await Promise.resolve();
  if (tryPick()) return true;
  await new Promise((resolve) => requestAnimationFrame(resolve));
  if (tryPick()) return true;

  if (injectIfMissing) {
    const opt = document.createElement('option');
    opt.value = target;
    opt.textContent = labelIfInjected;
    select.appendChild(opt);
    select.value = target;
    Array.from(select.options).forEach((o) => { o.selected = o.value === target; });
    return true;
  }

  return false;
}

export function ensureOptionAndSelect(select, value) {
  return robustSelect(select, value, { injectIfMissing: true, labelIfInjected: '(Your College)' });
}

export function getCurrentCollegeParam() {
  const params = new URLSearchParams(window.location.search);
  return params.get('college') || '';
}

export function lockSelectElement(select, locked, value, injectLabel = '(Your College)') {
  if (!select) return;

  if (locked) {
    const targetValue = value !== undefined && value !== null ? String(value) : '';
    if (targetValue) {
      let option = select.querySelector(`option[value="${CSS.escape(targetValue)}"]`);
      if (!option) {
        option = document.createElement('option');
        option.value = targetValue;
        option.textContent = injectLabel;
        select.appendChild(option);
      }
      select.value = targetValue;
      select.dataset.lockedValue = targetValue;
    }

    select.dataset.locked = '1';
    select.setAttribute('aria-readonly', 'true');
    select.classList.add('tb-select-locked');

    if (!select.__tbReadonlyBound) {
      select.addEventListener('mousedown', (event) => {
        if (select.dataset.locked === '1') event.preventDefault();
      }, true);
      select.addEventListener('keydown', (event) => {
        if (select.dataset.locked === '1') event.preventDefault();
      }, true);
      select.addEventListener('change', () => {
        if (select.dataset.locked === '1' && select.dataset.lockedValue) {
          select.value = select.dataset.lockedValue;
        }
      });
      select.__tbReadonlyBound = true;
    }
  } else {
    select.dataset.locked = '0';
    select.dataset.lockedValue = '';
    select.removeAttribute('aria-readonly');
    select.classList.remove('tb-select-locked');
  }
}