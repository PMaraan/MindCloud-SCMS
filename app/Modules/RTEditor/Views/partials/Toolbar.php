<?php
/**
 * Editor toolbar (Bootstrap)
 * Path: /app/Modules/RTEditor/Views/partials/Toolbar.php
 */
?>
<div class="d-flex flex-wrap gap-2">
  <div class="btn-group btn-group-sm" role="group" aria-label="Text">
    <button data-cmd="toggleBold"    type="button" class="btn btn-outline-secondary"><i class="bi bi-type-bold"></i></button>
    <button data-cmd="toggleItalic"  type="button" class="btn btn-outline-secondary"><i class="bi bi-type-italic"></i></button>
    <button data-cmd="toggleStrike"  type="button" class="btn btn-outline-secondary"><i class="bi bi-type-strikethrough"></i></button>
  </div>

  <div class="btn-group btn-group-sm" role="group" aria-label="Headings">
    <button data-cmd="setParagraph"  type="button" class="btn btn-outline-secondary">P</button>
    <button data-cmd="setH1"         type="button" class="btn btn-outline-secondary">H1</button>
    <button data-cmd="setH2"         type="button" class="btn btn-outline-secondary">H2</button>
    <button data-cmd="setH3"         type="button" class="btn btn-outline-secondary">H3</button>
  </div>

  <div class="btn-group btn-group-sm" role="group" aria-label="Lists">
    <button data-cmd="toggleBulletList" type="button" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i></button>
    <button data-cmd="toggleOrderedList"type="button" class="btn btn-outline-secondary"><i class="bi bi-list-ol"></i></button>
  </div>

  <div class="btn-group btn-group-sm" role="group" aria-label="Insert">
    <button data-cmd="insertTable"     type="button" class="btn btn-outline-secondary"><i class="bi bi-table"></i></button>
    <button data-cmd="setBlockquote"   type="button" class="btn btn-outline-secondary"><i class="bi bi-quote"></i></button>
    <button data-cmd="setCodeBlock"    type="button" class="btn btn-outline-secondary"><i class="bi bi-code"></i></button>
  </div>

  <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="UndoRedo">
    <button data-cmd="undo" type="button" class="btn btn-outline-secondary"><i class="bi bi-arrow-90deg-left"></i></button>
    <button data-cmd="redo" type="button" class="btn btn-outline-secondary"><i class="bi bi-arrow-90deg-right"></i></button>
  </div>
</div>
