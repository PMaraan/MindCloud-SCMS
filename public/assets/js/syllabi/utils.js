// /public/assets/js/syllabi/utils.js
export function installCssEscapeFallback() {
  if (!window.CSS || typeof window.CSS.escape !== 'function') {
    const pattern = /[{}|\\^~[\]`"<>#%]/g;
    window.CSS = window.CSS || {};
    window.CSS.escape = (value) => String(value).replace(pattern, '\\$&');
  }
}

export function getBase() {
  if (typeof window.BASE_PATH === 'string' && window.BASE_PATH) return window.BASE_PATH;
  const path = window.location.pathname;
  const cut = path.indexOf('/dashboard');
  return cut > -1 ? path.slice(0, cut) : '';
}

export async function fetchJSON(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json' },
    cache: 'no-store',
    ...options,
  });

  if (!response.ok) {
    const errText = await response.text().catch(() => '');
    throw new Error(`HTTP ${response.status}: ${errText}`);
  }

  const contentType = (response.headers.get('content-type') || '').toLowerCase();
  if (!contentType.includes('application/json')) {
    await response.text();
    throw new Error('Non-JSON response');
  }

  return response.json();
}

export function openSyllabus(templateId, { newTab = false } = {}) {
  if (!templateId) return;
  const href = `${getBase()}/dashboard?page=rteditor&syllabusId=${encodeURIComponent(templateId)}`;
  if (newTab) {
    window.open(href, '_blank', 'noopener');
  } else {
    window.location.href = href;
  }
}

export function capitalizeForDisplay(value) {
  if (!value) return '';
  const str = String(value);
  return str.charAt(0).toUpperCase() + str.slice(1);
}

export function getCurrentCollegeParam() {
  try {
    const params = new URLSearchParams(window.location.search);
    return params.get('college') || '';
  } catch {
    return '';
  }
}

export function preselectCollege(select, hiddenInput, collegeId) {
  if (!select) return;
  select.value = collegeId || '';
  if (hiddenInput) hiddenInput.value = collegeId || '';
}

export function preselectCourse(select, courseId) {
  if (!select) return;
  select.value = courseId || '';
}

export function lockSelectElement(select, hiddenInput) {
  if (!select) return;
  const shouldLock = select.dataset.locked === '1';
  const lockedValue = select.dataset.lockedValue || '';

  if (shouldLock) {
    select.value = lockedValue;
    select.classList.add('is-readonly');
    select.addEventListener('mousedown', (e) => e.preventDefault());
    select.addEventListener('keydown', (e) => e.preventDefault());
    select.addEventListener('focus', () => select.blur());
  } else if (hiddenInput) {
    hiddenInput.value = select.value;
    select.addEventListener('change', () => {
      hiddenInput.value = select.value;
    });
  }
}

export function fillSelect(select, items = [], placeholder = '— Select —') {
  if (!select) return;

  const previous = new Set(Array.from(select.selectedOptions).map((opt) => opt.value));
  const multiple = select.multiple === true;

  select.innerHTML = '';

  if (!multiple && placeholder !== null) {
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    select.append(opt);
  }

  items.forEach((item) => {
    if (!item) return;
    const value = item.id ?? item.value ?? '';
    const label = item.label ?? item.text ?? '';
    const option = document.createElement('option');
    option.value = String(value);
    option.textContent = String(label);
    if (previous.has(option.value)) option.selected = true;
    select.append(option);
  });
}