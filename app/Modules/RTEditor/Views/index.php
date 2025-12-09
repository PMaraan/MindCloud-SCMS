<?php
/**
 * RT Editor – Clean Build (no TipTap, no Yjs)
 * Path: /app/Modules/RTEditor/Views/index.php
 *
 * Expects: $pageTitle (string), $canEdit (bool)
 * This is a simple, controlled environment to prove we can type.
 */
$ASSET_BASE = defined('BASE_PATH') ? BASE_PATH : '';
// Use environment from /config/config.php (.env). Set APP_ENV=development or VITE_DEV=1 for dev mode.
$useViteDev = (getenv('APP_ENV') === 'development' || getenv('VITE_DEV') === '1');
$bundlePathProd = $_SERVER['DOCUMENT_ROOT'] . '/public/assets/js/rteditor/collab-editor.bundle.js';
$bundleUrlProd  = $ASSET_BASE . '/public/assets/js/rteditor/collab-editor.bundle.js';

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

<?php
  // Script loader moved to the bottom of this view (single concise loader handles dev <-> prod).
  // This block intentionally left blank to avoid duplicate loads.
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
    <!-- Single editor container: TipTap will mount its .ProseMirror here.
     pageContainer is inside the editor so NodeViews live under ProseMirror root. -->
    <div id="editor" class="rt-canvas">
      <!-- pageContainer is intentionally inside the editor so NodeViews and contentDOM
          remain inside the ProseMirror tree (keeps selection and keyboard events working). -->
      <div id="pageContainer"></div>
    </div>
  </div>

  <!-- Diagnostics BELOW the page -->
  <div class="mt-3 small text-muted">
    <div>Diagnostics:</div>
    <pre id="diag" class="p-2 border bg-light rounded" style="white-space:pre-wrap;"></pre>
  </div>
</div>

<script>
  // Provide a minimal page config so NodeViews can size themselves immediately.
  // Replace values below with your real defaults (mm units).
  window.__RT_getPageConfig = function() {
    return {
      size: { wmm: 210, hmm: 297 },      // A4 portrait (mm)
      orientation: 'portrait',
      paddingLeftMm: 25.4,               // left margin mm
      paddingRightMm: 25.4,              // right margin mm
      paddingTopMm: 25.4,                // top margin mm
      paddingBottomMm: 25.4              // bottom margin mm
    };
  };
</script>

<script>
  window.BASE_PATH = "<?= htmlspecialchars(defined('BASE_PATH') ? BASE_PATH : '', ENT_QUOTES, 'UTF-8') ?>";
  // If you expose CSRF in your app, also add:
  // window.CSRF_TOKEN = "< htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
</script>

<?php //include __DIR__ . '/partials/importmap.php'; ?>

<?php
  // Short loader: dev uses Vite dev server (bare import resolution). Production uses either
  // a bundled script (if present) or the module in /public/assets with filemtime cache busting.
  //$viteUrl = 'http://localhost:5173/assets/js/rteditor/collab-editor.js';
  if ($useViteDev) : ?>
    <!-- Vite client + dev import so Vite rewrites bare imports (e.g. @tiptap/core) -->
    <script type="module" src="http://localhost:5173/@vite/client"></script>
    <script type="module">
      console.log('[RTEditor] Vite dev server mode active (src path)');
      import { startEditorPage } from "http://localhost:5173/src/rteditor/collab-editor.js";
      startEditorPage({ debug: false, editable: <?= $canEdit ? 'true' : 'false' ?> });
    </script>
  <?php elseif (file_exists($bundlePathProd)) : ?>
    <?php $ver = filemtime($bundlePathProd); ?>
    <script defer src="<?= htmlspecialchars($bundleUrlProd . '?v=' . $ver, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>
      (function(){
        const start = (window.RTEditor && window.RTEditor.startEditorPage) || null;
        if (start) start({ debug: false, editable: <?= $canEdit ? 'true' : 'false' ?> });
        else console.warn('[RTEditor] bundled API not found on window.RTEditor');
      })();
    </script>
  <?php else: ?>
    <script type="module">
      import { startEditorPage } from "<?= htmlspecialchars(asset_url(BASE_PATH . '/public/assets/js/rteditor/collab-editor.js'), ENT_QUOTES) ?>";
      startEditorPage({ debug: false, editable: <?= $canEdit ? 'true' : 'false' ?> });
    </script>
  <?php endif; ?>