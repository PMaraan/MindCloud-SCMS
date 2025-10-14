<?php
/**
 * RT Editor – TipTap + Yjs (using syllabus_templates)
 * Path: /app/Modules/RTEditor/Views/index.php
 * Expects: $title, $canCreate, $canEdit, and $room (string or int)
 */
use App\Helpers\CsrfHelper;

$ASSET_BASE = defined('BASE_PATH') ? BASE_PATH : '';
$templateId = isset($room) && is_numeric($room) ? (int)$room : 0;
?>
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex gap-2 align-items-center">
      <h5 class="mb-0">RT Editor</h5>
      <span class="badge text-bg-secondary">
        <?= $templateId > 0 ? ('Template ID: ' . (int)$templateId) : 'New (unsaved)' ?>
      </span>
    </div>
    <div class="d-flex gap-2">
      <button id="btnCreateDoc" class="btn btn-sm btn-primary" <?= $canCreate ? '' : 'disabled' ?>>Create New</button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_PATH ?>/dashboard?page=rteditor">Open</a>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <?php require __DIR__ . '/partials/Toolbar.php'; ?>
    </div>
    <div class="card-body">
      <form class="row g-2 align-items-center mb-2" id="metaForm" autocomplete="off">
        <?= CsrfHelper::inputField(); ?>
        <input type="hidden" id="templateId" name="template_id" value="<?= (int)$templateId ?>">
        <div class="col-auto">
          <label for="docTitle" class="form-label visually-hidden">Title</label>
          <input type="text" class="form-control form-control-sm" id="docTitle" name="title"
                 placeholder="Template title" value="<?= htmlspecialchars($title ?? 'Untitled Template') ?>" <?= $canEdit ? '' : 'disabled' ?>>
        </div>
        <div class="col-auto">
          <button id="btnSaveTitle" type="button" class="btn btn-sm btn-outline-secondary" <?= $canEdit ? '' : 'disabled' ?>>Save Title</button>
        </div>
        <div class="col-auto ms-auto">
          <button id="btnSnapshot" type="button" class="btn btn-sm btn-outline-primary" <?= $canEdit ? '' : 'disabled' ?>>Save Snapshot</button>
        </div>
      </form>

      <div id="editor" class="border rounded p-3" style="min-height:420px;"></div>
      <small class="text-muted d-block mt-2" id="connInfo">Status: initializing…</small>
    </div>
  </div>
</div>

<script type="module">
  import initCollabEditor from "<?= $ASSET_BASE ?>/public/assets/js/rteditor/collab-editor.js";

  // Set your y-websocket endpoint here
  const WS_URL = (window.EDITOR_WS_URL ?? "wss://localhost:1234");

  // Room: prefer numeric template_id; if none, use a temp string (local-only until create)
  const TID  = Number(document.getElementById('templateId').value || 0);
  const ROOM = TID > 0 ? String(TID) : (window.crypto?.randomUUID?.() ?? 'tmp-' + Math.random().toString(36).slice(2));

  initCollabEditor({
    editorSelector: '#editor',
    room: ROOM,
    wsUrl: WS_URL,
    canEdit: <?= $canEdit ? 'true' : 'false' ?>,
    onStatus: (txt) => { document.getElementById('connInfo').textContent = 'Status: ' + txt; },
  });

  // Meta actions
  const btnCreate = document.getElementById('btnCreateDoc');
  const btnSave   = document.getElementById('btnSaveTitle');
  const btnSnap   = document.getElementById('btnSnapshot');

  btnCreate?.addEventListener('click', async () => {
    const title = document.getElementById('docTitle').value.trim() || 'Untitled Template';
    const body  = new FormData();
    body.set('title', title);
    body.set('csrf', document.querySelector('input[name="csrf"]').value);

    const res = await fetch('<?= BASE_PATH ?>/dashboard?page=rteditor&action=create', { method:'POST', body });
    const json = await res.json();
    if (json.ok) {
      document.getElementById('templateId').value = json.template_id;
      alert('Template created. Reloading…');
      window.location.href = '<?= BASE_PATH ?>/dashboard?page=rteditor&template_id=' + encodeURIComponent(json.template_id);
    } else {
      alert('Create failed.');
    }
  });

  btnSave?.addEventListener('click', async () => {
    const body = new FormData(document.getElementById('metaForm'));
    const res  = await fetch('<?= BASE_PATH ?>/dashboard?page=rteditor&action=saveMeta', { method:'POST', body });
    const json = await res.json();
    alert(json.ok ? 'Title saved.' : 'Save failed.');
  });

  btnSnap?.addEventListener('click', async () => {
    const ev = new CustomEvent('rteditor:snapshot', { detail: null });
    window.dispatchEvent(ev);
  });
</script>
