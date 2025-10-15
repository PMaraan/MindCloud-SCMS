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
      <link rel="stylesheet" href="<?= BASE_PATH ?>/public/assets/css/rteditor/collab-editor.css">

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

<script type="module">
  import initBasicEditor, { bindBasicToolbar } from "<?= BASE_PATH ?>/public/assets/js/rteditor/collab-editor.js";

  const canEdit = true; // keep forced ON for this phase

  // Initialize TipTap
  const editor = initBasicEditor({
    selector: '#editor',
    editable: canEdit,
    initialHTML: '<p>TipTap ready — start typing…</p>'
  });

  // Wire the tiny toolbar
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
