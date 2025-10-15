<?php
/**
 * RT Editor – Clean Build (no TipTap, no Yjs)
 * Path: /app/Modules/RTEditor/Views/index.php
 *
 * Expects: $pageTitle (string), $canEdit (bool)
 * This is a simple, controlled environment to prove we can type.
 */
$ASSET_BASE = defined('BASE_PATH') ? BASE_PATH : '';
?>
<div class="container py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h5>
    <span class="badge <?= $canEdit ? 'text-bg-success' : 'text-bg-warning' ?>">
      <?= $canEdit ? 'Editable' : 'Read-only' ?>
    </span>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <strong>Typing Test Area</strong>
    </div>
    <div class="card-body">
      <!-- Plain contenteditable area with zero external dependencies -->
      <!-- TipTap mount (no contenteditable attribute—TipTap will manage it) -->

      <!-- Toolbar -->
      <div class="d-flex gap-2 mb-2">
        <div class="btn-group btn-group-sm" role="group" aria-label="Text">
          <button type="button" class="btn btn-outline-secondary" data-cmd="toggleBold"><i class="bi bi-type-bold"></i></button>
          <button type="button" class="btn btn-outline-secondary" data-cmd="toggleItalic"><i class="bi bi-type-italic"></i></button>
        </div>
        <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="UndoRedo">
          <button type="button" class="btn btn-outline-secondary" data-cmd="undo"><i class="bi bi-arrow-90deg-left"></i></button>
          <button type="button" class="btn btn-outline-secondary" data-cmd="redo"><i class="bi bi-arrow-90deg-right"></i></button>
        </div>
      </div>

      <link rel="stylesheet" href="<?= BASE_PATH ?>/public/assets/css/rteditor/collab-editor.css">

      <div id="editor" class="border rounded p-3" style="min-height: 360px; background: #fff;"></div>

      <div class="mt-3 small text-muted">
        <div>Diagnostics:</div>
        <pre id="diag" class="p-2 border bg-light rounded" style="white-space:pre-wrap;"></pre>
      </div>

    </div>
  </div>
</div>

<script type="importmap">
{
  "imports": {
    "@tiptap/core": "https://esm.sh/@tiptap/core@2.6.6",
    "@tiptap/starter-kit": "https://esm.sh/@tiptap/starter-kit@2.6.6",
    "@tiptap/pm": "https://esm.sh/@tiptap/pm@2.6.6"
  }
}
</script>

<script type="module">
  import initBasicEditor, { bindBasicToolbar } from "<?= BASE_PATH ?>/public/assets/js/rteditor/collab-editor.js";

  const editor = initBasicEditor({
    selector: '#editor',
    editable: true,
    initialHTML: '<p>TipTap ready — start typing…</p>'
  });
  window.__RT_editor = editor;

  bindBasicToolbar(editor, document);

  // Diagnostics
  (function() {
    const ed = document.querySelector('#editor .ProseMirror');
    const out = document.getElementById('diag');
    const log = (m) => { out.textContent += (m + '\n'); };

    function report() {
      if (!ed) { log('[Warn] .ProseMirror not found'); return; }
      const cs = getComputedStyle(ed);
      log('[Report] TipTap contenteditable=' + ed.getAttribute('contenteditable'));
      log('[Report] pointer-events=' + cs.pointerEvents + ', user-select=' + cs.userSelect + ', display=' + cs.display + ', visibility=' + cs.visibility);
    }

    if (ed) {
      ed.addEventListener('keydown', () => log('[Event] keydown detected (TipTap)'));
      report();
    } else {
      log('[Warn] ProseMirror not ready at init');
      setTimeout(() => {
        const ed2 = document.querySelector('#editor .ProseMirror');
        if (ed2) {
          ed2.addEventListener('keydown', () => log('[Event] keydown detected (TipTap)'));
          const cs = getComputedStyle(ed2);
          log('[Report] TipTap contenteditable=' + ed2.getAttribute('contenteditable'));
          log('[Report] pointer-events=' + cs.pointerEvents + ', user-select=' + cs.userSelect + ', display=' + cs.display + ', visibility=' + cs.visibility);
        } else {
          log('[Error] ProseMirror still not found after delay.');
        }
      }, 100);
    }
  })();
</script>




<script>
// ====== SINGLE KEYDOWN REGISTRATION & ENTER TRACE (TEMP) ======
(function () {
  const out = document.getElementById('diag');
  if (!out) return;
  const log = (...a) => { out.textContent += a.join(' ') + '\n'; };

  // Capture WHO registers 'keydown' (target + stack at registration time)
  const origAdd = EventTarget.prototype.addEventListener;
  const registry = [];
  EventTarget.prototype.addEventListener = function (type, handler, opts) {
    if (type === 'keydown' && typeof handler === 'function') {
      const stack = (new Error('addEventListener stack')).stack?.split('\n').slice(2, 8).join('\n') ?? '(no stack)';
      registry.push({
        target: this,
        name: handler.name || '(anonymous)',
        opts,
        stackAtRegistration: stack
      });
    }
    return origAdd.call(this, type, handler, opts);
  };

  // Dump helper you can run from console
  window.dumpKeydownListeners = function () {
    log('--- Registered keydown listeners ---');
    const nodeDesc = (n) => {
      if (n === window) return 'window';
      if (n === document) return 'document';
      if (n instanceof HTMLElement) {
        const id = n.id ? `#${n.id}` : '';
        const cls = n.className ? '.' + String(n.className).trim().replace(/\s+/g, '.') : '';
        return `<${n.tagName.toLowerCase()}${id}${cls}>`;
      }
      return String(n);
    };
    registry.forEach((r, i) => {
      log(`#${i} target=${nodeDesc(r.target)} handler=${r.name} opts=${JSON.stringify(r.opts || {})}`);
      log(r.stackAtRegistration);
      log('---');
    });
    log('Total:', registry.length);
  };

  // Trace Enter propagation & who calls preventDefault/stop*
  const origPD  = Event.prototype.preventDefault;
  const origSP  = Event.prototype.stopPropagation;
  const origSIP = Event.prototype.stopImmediatePropagation;

  function shortStack() {
    return (new Error().stack?.split('\n').slice(2, 8).join('\n')) ?? '(no stack)';
  }
  Event.prototype.preventDefault = function () {
    if (this.type === 'keydown' && this.key === 'Enter') {
      log('[TRACE] preventDefault on Enter (target=', String(this.target), ')\n', shortStack());
    }
    return origPD.apply(this, arguments);
  };
  Event.prototype.stopPropagation = function () {
    if (this.type === 'keydown' && this.key === 'Enter') {
      log('[TRACE] stopPropagation on Enter (target=', String(this.target), ')\n', shortStack());
    }
    return origSP.apply(this, arguments);
  };
  Event.prototype.stopImmediatePropagation = function () {
    if (this.type === 'keydown' && this.key === 'Enter') {
      log('[TRACE] stopImmediatePropagation on Enter (target=', String(this.target), ')\n', shortStack());
    }
    return origSIP.apply(this, arguments);
  };

  function phase(label, e) {
    if (e.key !== 'Enter') return;
    log(`[TRACE] ${label} defaultPrevented=${e.defaultPrevented} target=`, e.target?.outerHTML?.slice(0, 80) ?? String(e.target));
  }
  document.addEventListener('keydown', e => phase('CAPTURE', e), true);
  document.addEventListener('keydown', e => phase('BUBBLE ', e), false);

  log('[TRACE] ready. Do the two Enter tests, then run dumpKeydownListeners() in console.');
})();
</script>

<script>
// ===== OVERLAY DETECTOR (keep this) =====
(function () {
  const ed = document.querySelector('#editor');
  if (!ed) return;
  ed.addEventListener('click', (ev) => {
    const x = ev.clientX, y = ev.clientY;
    const topEl = document.elementFromPoint(x, y);
    if (!topEl) return;
    if (!ed.contains(topEl)) {
      console.warn('[OVERLAY] Click resolved to element OUTSIDE #editor at', topEl);
    } else {
      const z = getComputedStyle(topEl).zIndex;
      console.log('[OVERLAY] Click inside editor resolved to', topEl, 'z-index=', z);
    }
  });
})();
</script>







<script>
// ===== OVERLAY DETECTOR (TEMP) =====
(function () {
  const ed = document.querySelector('#editor');
  if (!ed) return;
  ed.addEventListener('click', (ev) => {
    const x = ev.clientX, y = ev.clientY;
    const topEl = document.elementFromPoint(x, y);
    if (!topEl) return;
    if (!ed.contains(topEl)) {
      console.warn('[OVERLAY] Click resolved to element OUTSIDE #editor at', topEl);
    } else {
      const z = getComputedStyle(topEl).zIndex;
      console.log('[OVERLAY] Click inside editor resolved to', topEl, 'z-index=', z);
    }
  });
})();
</script>