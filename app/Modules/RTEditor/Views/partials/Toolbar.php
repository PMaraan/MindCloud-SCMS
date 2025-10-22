<?php
/**
 * Editor toolbar (Bootstrap)
 * Path: /app/Modules/RTEditor/Views/partials/Toolbar.php
 */
?>
<!-- /app/Modules/RTEditor/Views/partials/Toolbar.php -->
<div class="rt-toolbar rt-toolbar-sticky">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

    <!-- Page Layout -->
    <div class="d-flex align-items-center ms-2 gap-2">
      <div class="d-flex align-items-center gap-1">
        <label class="small text-muted">Size</label>
        <select class="form-select form-select-sm" style="width:auto" data-page-size>
          <option value="A4" selected>A4</option>
          <option value="Letter">Letter</option>
          <option value="Legal">Legal</option>
          <option value="A5">A5</option>
        </select>
      </div>

      <div class="d-flex align-items-center gap-1">
        <label class="small text-muted">Orientation</label>
        <select class="form-select form-select-sm" style="width:auto" data-page-orientation>
          <option value="portrait" selected>Portrait</option>
          <option value="landscape">Landscape</option>
        </select>
      </div>

      <div class="d-flex align-items-center gap-1">
        <label class="small text-muted">Margins</label>
        <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-top value="25mm" placeholder="top">
        <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-right value="25mm" placeholder="right">
        <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-bottom value="25mm" placeholder="bottom">
        <input type="text" class="form-control form-control-sm" style="width:70px" data-page-margin-left value="25mm" placeholder="left">
      </div>
    </div>

    <!-- Font Family -->
    <div class="d-flex align-items-center ms-2 gap-1">
      <label class="small text-muted">Font</label>
      <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setFontFamily">
        <option value="Times New Roman, Times, serif">Times New Roman</option>
        <option value="Georgia, serif">Georgia</option>
        <option value="Garamond, serif">Garamond</option>
        <option value="Arial, Helvetica, sans-serif" selected>Arial</option>
        <option value="Helvetica, Arial, sans-serif">Helvetica</option>
        <option value="Tahoma, Verdana, Segoe, sans-serif">Tahoma</option>
        <option value="Verdana, Tahoma, sans-serif">Verdana</option>
        <option value="Segoe UI, Roboto, Helvetica, Arial, sans-serif">Segoe UI</option>
        <option value="Courier New, Courier, monospace">Courier New</option>
        <option value="Consolas, Monaco, monospace">Consolas</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetFontFamily" title="Clear font family">
        <i class="bi bi-x-circle"></i>
      </button>
    </div>

    <!-- Font Size -->
    <div class="d-flex align-items-center ms-2 gap-1">
      <label class="small text-muted">Size</label>
      <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setFontSize">
        <option value="8pt">8</option><option value="9pt">9</option><option value="10pt">10</option>
        <option value="11pt" selected>11</option><option value="12pt">12</option><option value="14pt">14</option>
        <option value="16pt">16</option><option value="18pt">18</option><option value="20pt">20</option>
        <option value="22pt">22</option><option value="24pt">24</option><option value="28pt">28</option>
        <option value="32pt">32</option><option value="36pt">36</option><option value="48pt">48</option><option value="72pt">72</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetFontSize" title="Clear font size">
        <i class="bi bi-x-circle"></i>
      </button>
    </div>

    <!-- Line Spacing -->
    <div class="d-flex align-items-center ms-2 gap-1">
      <label class="small text-muted">Line</label>
      <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setLineSpacing">
        <option value="1">1.0</option>
        <option value="1.15" selected>1.15</option>
        <option value="1.5">1.5</option>
        <option value="2">2.0</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetLineSpacing" title="Clear line spacing">
        <i class="bi bi-x-circle"></i>
      </button>
    </div>

    <!-- Paragraph Spacing -->
    <div class="d-flex align-items-center ms-2 gap-1">
      <label class="small text-muted">Before</label>
      <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setParaBefore">
        <option value="0pt" selected>0</option><option value="6pt">6</option><option value="12pt">12</option><option value="18pt">18</option><option value="24pt">24</option>
      </select>
      <label class="small text-muted ms-2">After</label>
      <select class="form-select form-select-sm" style="width:auto" data-cmd-input="setParaAfter">
        <option value="0pt" selected>0</option><option value="6pt">6</option><option value="12pt">12</option><option value="18pt">18</option><option value="24pt">24</option>
      </select>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetParaSpacing" title="Clear paragraph spacing">
        <i class="bi bi-x-circle"></i>
      </button>
    </div>

    <!-- Text styles -->
    <div class="btn-group btn-group-sm" role="group" aria-label="Text">
      <button type="button" class="btn btn-outline-secondary" data-cmd="toggleBold" title="Bold"><i class="bi bi-type-bold"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="toggleItalic" title="Italic"><i class="bi bi-type-italic"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="toggleUnderline" title="Underline"><i class="bi bi-type-underline"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="toggleStrike" title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="toggleSubscript" title="Subscript">x<sub>2</sub></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="toggleSuperscript" title="Superscript">x<sup>2</sup></button>
    </div>

    <!-- Lists -->
    <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Lists">
      <button type="button" class="btn btn-outline-secondary" data-cmd="bulletList" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="orderedList" title="Numbered list"><i class="bi bi-list-ol"></i></button>
    </div>

    <!-- Indent / Outdent -->
    <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Indent">
      <button type="button" class="btn btn-outline-secondary" data-cmd="indentList" title="Increase indent (Tab)">
        <i class="bi bi-text-indent-right"></i>
      </button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="outdentList" title="Decrease indent (Shift+Tab)">
        <i class="bi bi-text-indent-left"></i>
      </button>
    </div>

    <!-- Alignment -->
    <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Align">
      <button type="button" class="btn btn-outline-secondary" data-cmd="alignLeft" title="Align left"><i class="bi bi-text-left"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="alignCenter" title="Center"><i class="bi bi-text-center"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="alignRight" title="Align right"><i class="bi bi-text-right"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="alignJustify" title="Justify"><i class="bi bi-justify"></i></button>
    </div>

    <!-- Colors -->
    <div class="d-flex align-items-center ms-2 gap-1">
      <label class="small text-muted">Text</label>
      <input type="color" data-cmd-input="setColor" class="form-control form-control-color p-0" value="#000000" title="Text color">
      <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetColor" title="Clear text color"><i class="bi bi-x-circle"></i></button>
    </div>

    <div class="d-flex align-items-center ms-2 gap-1">
      <label class="small text-muted">Highlight</label>
      <button type="button" class="btn btn-warning btn-sm" data-cmd="applyHighlight" title="Highlight selection">
        <i class="bi bi-highlighter"></i>
      </button>
      <input type="color" data-cmd-input="setHighlight" class="form-control form-control-color p-0" value="#fff59d" title="Highlight color">
      <button type="button" class="btn btn-outline-secondary btn-sm" data-cmd="unsetHighlight" title="Clear highlight"><i class="bi bi-x-circle"></i></button>
    </div>

    <!-- Undo/Redo -->
    <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="UndoRedo">
      <button type="button" class="btn btn-outline-secondary" data-cmd="undo" title="Undo"><i class="bi bi-arrow-90deg-left"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="redo" title="Redo"><i class="bi bi-arrow-90deg-right"></i></button>
    </div>

    <!-- Tables -->
    <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Table">
      <button type="button" class="btn btn-outline-secondary" data-cmd="insertTable" title="Insert 3×3 table"><i class="bi bi-table"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="addRowBefore" title="Row before"><i class="bi bi-border-top"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="addRowAfter" title="Row after"><i class="bi bi-border-bottom"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="deleteRow" title="Delete row"><i class="bi bi-border-width"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="addColumnBefore" title="Col before"><i class="bi bi-border-start"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="addColumnAfter" title="Col after"><i class="bi bi-border-end"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="deleteColumn" title="Delete column"><i class="bi bi-border-style"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="toggleHeaderRow" title="Toggle header row"><i class="bi bi-layout-three-columns"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="mergeCells" title="Merge cells"><i class="bi bi-layout-wtf"></i></button>
      <button type="button" class="btn btn-outline-secondary" data-cmd="splitCell" title="Split cell"><i class="bi bi-grid-1x2"></i></button>
      <button type="button" class="btn btn-outline-danger" data-cmd="deleteTable" title="Delete table"><i class="bi bi-trash"></i></button>
    </div>

    <!-- Signature field -->
    <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Signature">
      <button type="button" class="btn btn-outline-secondary" data-cmd="insertSignature" title="Insert signature field"><i class="bi bi-pen"></i></button>
      <select class="form-select form-select-sm" style="width:auto" data-cmd-input="sigSetRole" title="Signer role">
        <option value="">(role)</option><option value="Instructor">Instructor</option><option value="Program Chair">Program Chair</option><option value="Dean">Dean</option><option value="VPAA">VPAA</option>
      </select>
      <button type="button" class="btn btn-outline-secondary" data-cmd="sigToggleRequired" title="Toggle required"><i class="bi bi-check2-square"></i></button>
    </div>

    <!-- Page Break -->
    <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Page Break">
      <button type="button" class="btn btn-outline-secondary" data-cmd="insertPageBreak" title="Insert page break">
        <i class="bi bi-scissors"></i>
      </button>
    </div>

    <!-- Auto Pagination -->
    <div class="btn-group btn-group-sm ms-2" role="group" aria-label="Auto Pagination">
      <button type="button" class="btn btn-outline-secondary" data-cmd="autoPaginate" title="Suggest & insert page breaks">
        <i class="bi bi-magic"></i>
      </button>
    </div>

  </div>
</div>
