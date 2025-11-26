<?php
/**
 * RT Editor – Clean Build (no TipTap, no Yjs)
 * Path: /app/Modules/RTEditor/Views/index.php
 *
 * Expects: $pageTitle (string), $canEdit (bool)
 * This is a simple, controlled environment to prove we can type.
 */
$ASSET_BASE = defined('BASE_PATH') ? BASE_PATH : '';

// Simple asset versioning: uses file mtime so browsers get updated file when it changes.
// In production you should use build-time content hashes instead.
function asset_url(string $path): string {
    $file = $_SERVER['DOCUMENT_ROOT'] . $path;
    if (file_exists($file)) {
        return $path . '?v=' . filemtime($file);
    }
    // fallback: return path unchanged
    return $path;
}
?>
<script>document.body.classList.add('editor-page');</script>
<!-- FIXED ribbon just below navbar, aligned to the right of the sidebar -->
<div class="container-fluid py-3 rt-sticky-header" style="--app-topbar-h:0px; --rt-toolbar-h:52px;">
  <!-- Page title and editability badge -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h5>
    <span class="badge <?= $canEdit ? 'text-bg-success' : 'text-bg-warning' ?>">
      <?= $canEdit ? 'Editable' : 'Read-only' ?>
    </span>
  </div>

  <!-- Toolbar area fixed to top-right below navbar -->
  <?php include __DIR__ . '/partials/Toolbar.php'; ?>
  
  <!-- Hidden JSON payload for initial content hydration -->
  <?php
  /**
   * [RTEditor Payload Injection]
   * Purpose: Provide the initial TipTap JSON for hydration without HTML-escaping.
   */
  $initialJsonRaw = isset($initialJsonRaw) && is_string($initialJsonRaw) && trim($initialJsonRaw) !== ''
    ? $initialJsonRaw
    : '{"type":"doc","content":[{"type":"paragraph"}]}';
  $safeJsonForScript = str_replace('</script', '<\/script', $initialJsonRaw);
  ?>
  <script id="rt-initial-json" type="application/json"><?= $safeJsonForScript ?></script>

  <?php
  /**
   * [RTEditor Meta]
   * Purpose: expose scope/id for front-end save/load logic.
   */
  $rtScope = isset($rtScope) ? (string)$rtScope : '';
  $rtId    = isset($rtId)    ? (int)$rtId      : 0;
  ?>
  <div id="rt-meta" data-scope="<?= htmlspecialchars($rtScope, ENT_QUOTES, 'UTF-8') ?>" data-id="<?= (int)$rtId ?>"></div>

  <!-- Editor CSS (kept) -->
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url(BASE_PATH . '/public/assets/css/rteditor/collab-editor.css'), ENT_QUOTES) ?>">

</div>

<!-- Full-width band for the page canvas -->
<div class="container-fluid py-3">
  <div id="pageRoot">
    <div id="rtPage" class="rt-page">
      <!-- Editable master header is part of the page so header/footer count toward the page height -->
      <div id="rtHeader" class="rt-header" contenteditable="true">Header…</div>

      <div id="rtPageContent" class="rt-page-content">
        <div id="editor" class="border-0"></div>        
      </div>
      
      <div id="rtFooter" class="rt-footer" contenteditable="true">Footer…</div>
    </div>
  </div>

  <!-- Diagnostics BELOW the page -->
  <div class="mt-3 small text-muted">
    <div>Diagnostics:</div>
    <pre id="diag" class="p-2 border bg-light rounded" style="white-space:pre-wrap;"></pre>
  </div>
</div>

<script>
  window.BASE_PATH = "<?= htmlspecialchars(defined('BASE_PATH') ? BASE_PATH : '', ENT_QUOTES, 'UTF-8') ?>";
  // If you expose CSRF in your app, also add:
  // window.CSRF_TOKEN = "< htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
</script>

<?php include __DIR__ . '/partials/importmap.php'; ?>

<!-- main module (cached by filemtime) -->
<script type="module" src="<?= htmlspecialchars(asset_url(BASE_PATH . '/public/assets/js/rteditor/collab-editor.js'), ENT_QUOTES) ?>"></script>

<!-- starter import: keep the inline import but reference the same versioned path -->
<script type="module">
  import { startEditorPage } from "<?= htmlspecialchars(asset_url(BASE_PATH . '/public/assets/js/rteditor/collab-editor.js'), ENT_QUOTES) ?>";
  startEditorPage({ debug: false, editable: <?= $canEdit ? 'true' : 'false' ?> });
</script>