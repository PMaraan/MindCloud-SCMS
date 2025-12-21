<?php
// Robust $ASSET_BASE for both routed and "open file in browser" cases
if (!defined('BASE_PATH')) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $script = str_replace('\\', '/', $script);

    if (strpos($script, '/app/') !== false) {
        $projectBase = preg_replace('#/app/.*$#', '', $script);
    } else {
        $projectBase = rtrim(dirname($script), '/');
    }

    $PROJECT_ROOT = rtrim($projectBase, '/');
    $ASSET_BASE   = $PROJECT_ROOT . '/public';
} else {
    $ASSET_BASE   = BASE_PATH;
    $PROJECT_ROOT = rtrim(dirname(BASE_PATH), '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MindCloud — TipTap Page Editor (New3)</title>

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="<?= $ASSET_BASE ?>/assets/css/TemplateBuilder-New3.css?v=<?= time() ?>">
</head>
<body>

  <!-- Top Maroon Bar -->
  <header id="mc-topbar" class="bg-maroon text-white">
    <div class="container-fluid d-flex align-items-center gap-2">

      <a href="<?= $PROJECT_ROOT ?>/" class="mc-logo-link" title="Go to Home" aria-label="Go to Home">
        <img src="<?= $ASSET_BASE ?>/assets/images/logo_lpu.png" alt="Logo" class="mc-logo" id="logoPreview">
      </a>

      <!-- Left: doc controls -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <label class="top-label me-1">Paper Size:</label>
        <select id="ctl-paper" class="form-select form-select-sm top-select">
          <option value="A4" selected>A4</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
        </select>
        <button id="ctl-addpage" class="btn btn-sm btn-outline-light">
          <i class="bi bi-file-earmark-plus"></i> Add Page
        </button>
      </div>

      <!-- Middle: FULL TIPTAP TOOLBAR -->
      <div id="tt-toolbar">
        <div class="toolbar-fonts d-flex align-items-center gap-1 me-2">
          <select id="ctl-font" class="form-select form-select-sm top-select" title="Font family">
            <option value="">Font</option>
            <option value="Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif">Inter</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="Times New Roman, Times, serif">Times New Roman</option>
            <option value="Garamond, serif">Garamond</option>
            <option value="Arial, Helvetica, sans-serif">Arial</option>
            <option value="Tahoma, Geneva, sans-serif">Tahoma</option>
            <option value="Courier New, Courier, monospace">Courier New</option>
          </select>
          <select id="ctl-size" class="form-select form-select-sm top-select" title="Font size">
            <option value="">Size</option>
            <option value="12px">12</option>
            <option value="14px">14</option>
            <option value="16px">16</option>
            <option value="18px">18</option>
            <option value="20px">20</option>
            <option value="24px">24</option>
            <option value="28px">28</option>
            <option value="32px">32</option>
          </select>
          <div class="dropdown">
            <button class="btn btn-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Line spacing">
              <i class="bi bi-text-paragraph"></i> Spacing
            </button>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="1">Single</button></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="1.15">1.15</button></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="1.5">1.5</button></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="2">Double</button></li>
              <li><hr class="dropdown-divider"></li>
              <li><button class="dropdown-item" data-action="setLineHeight" data-lh="custom">Custom…</button></li>
              <li><button class="dropdown-item" data-action="unsetLineHeight">Clear</button></li>
            </ul>
          </div>
          <div class="toolbar-sep"></div>
          <button class="btn btn-icon" data-action="insertTable" title="Insert table">
            <i class="bi bi-table"></i>
          </button>
          <button class="btn btn-icon" data-action="insertUploadBox" title="Image upload">
            <i class="bi bi-image"></i>
          </button>
          <div class="toolbar-sep"></div>
          <button class="btn btn-icon" data-action="undo" title="Undo"><i class="bi bi-arrow-counterclockwise"></i></button>
          <button class="btn btn-icon" data-action="redo" title="Redo"><i class="bi bi-arrow-clockwise"></i></button>
          <div class="toolbar-sep"></div>
          <button class="btn btn-icon" data-action="toggleBulletList" title="Bulleted list">
            <i class="bi bi-list-ul"></i>
          </button>
          <div class="toolbar-sep"></div>
          <button class="btn btn-icon" data-action="toggleBold" title="Bold"><i class="bi bi-type-bold"></i></button>
          <button class="btn btn-icon" data-action="toggleItalic" title="Italicize"><i class="bi bi-type-italic"></i></button>
          <button class="btn btn-icon" data-action="toggleUnderline" title="Underline"><i class="bi bi-type-underline"></i></button>
          <div class="toolbar-sep"></div>
          <button class="btn btn-icon" data-action="setLink" title="Add link"><i class="bi bi-link-45deg"></i></button>
          <button class="btn btn-icon" data-action="unsetLink" title="Remove link">
            <i class="bi bi-link-45deg"></i><i class="bi bi-x-lg ms-n2 small"></i>
          </button>
          <div class="toolbar-sep"></div>
          <button class="btn btn-icon" data-action="alignLeft" title="Align left"><i class="bi bi-text-left"></i></button>
          <button class="btn btn-icon" data-action="alignCenter" title="Align middle"><i class="bi bi-text-center"></i></button>
          <button class="btn btn-icon" data-action="alignRight" title="Align right"><i class="bi bi-text-right"></i></button>
          <button class="btn btn-icon" data-action="alignJustify" title="Justify"><i class="bi bi-justify"></i></button>
          <input id="ctl-color-hidden" type="color" class="form-control form-control-color ms-2" style="display:none">
        </div>
      </div>

      <!-- Right actions -->
      <div class="mc-actions">
        <button id="ctl-send" class="btn btn-icon" title="Save">
          <i class="bi bi-send"></i>
        </button>
        <button id="ctl-settings" class="btn btn-icon" title="Settings">
          <i class="bi bi-gear"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Editor Shell -->
  <div id="mc-shell">
    <aside class="gutter"></aside>
    <main id="mc-work">
      <section class="page size-A4" id="page-1" data-page="1" tabindex="0">
        <div class="page-header">
          <label class="logo-upload" title="Upload logo">
            <input id="logoInput" type="file" accept="image/*" hidden>
            <img id="logoPreviewInner" alt="Logo" />
            <span class="logo-fallback"></span>
          </label>
          <div class="header-center">
            <h1 class="title" contenteditable="true">Enter Syllabus Title</h1>
            <p class="subtitle" contenteditable="true">Enter Subtitle</p>
          </div>
        </div>
        <div id="editor" class="tiptap" data-editor aria-label="Document editor"></div>
        <footer class="page-footer" aria-label="Page footer">
          <span class="footer-left" contenteditable="true" data-placeholder="Footer Text">Footer Text</span>
          <span class="footer-right">Page <span class="page-num">1</span></span>
        </footer>
      </section>
    </main>

    <!-- Right palette -->
    <aside id="mc-sidebar">
      <div class="d-grid gap-2">
        <button id="sb-toggle" class="sb-item">
          <i class="bi bi-grid-3x3-gap"></i>
        </button>
        <button class="sb-item" draggable="true" data-type="label"><i class="bi bi-tag"></i> Label</button>
        <button class="sb-item" draggable="true" data-type="paragraph"><i class="bi bi-card-text"></i> Paragraph</button>
        <button class="sb-item" draggable="true" data-type="text"><i class="bi bi-textarea-t"></i> Text Field</button>
        <button class="sb-item" draggable="true" data-type="textarea"><i class="bi bi-textarea-resize"></i> Text Area</button>
        <button class="sb-item" draggable="true" data-type="table"><i class="bi bi-table"></i> Table</button>
        <button class="sb-item" draggable="true" data-type="signature"><i class="bi bi-pen"></i> Signature Field</button>
      </div>
    </aside>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Modular Editor (ESM) -->
  <script type="module" src="<?= $ASSET_BASE ?>/assets/js/editor2/TemplateBuilder-New3.js?v=<?= time() ?>"></script>
</body>
</html>
