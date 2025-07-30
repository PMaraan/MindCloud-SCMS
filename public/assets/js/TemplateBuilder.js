"use strict";
class TemplateBuilder {
  ROW_HEIGHT = 20;
  DROP_MARGIN = 10;
  SNAP_GRID_SIZE = 10;
  SIZES = {
    A4: { w: 794, h: 1123 },
    Letter: { w: 816, h: 1056 },
    Legal: { w: 816, h: 1344 }
  };
  ROMAN_MAP = [
    [1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'], [100, 'C'],
    [90, 'XC'], [50, 'L'], [40, 'XL'], [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I']
  ];
constructor() {
  // === Global Constants ===
  this.SNAP_GRID_SIZE = 10; // Snap every 10px

  this.workspace = document.getElementById("workspace");
  this.tableToolbar = new TableToolbar(this);

  this.ghostLine = document.createElement("div");
  this.ghostLine.className = "ghost-line";
  this.ghostLine.style.display = "none";
  this.workspace.appendChild(this.ghostLine);

  this.defaultFontFamily = "Arial";
  this.defaultFontSize   = 12;
  this.fontFamilySel     = document.getElementById("fontFamily");
  this.fontSizeSel       = document.getElementById("fontSize");
  this.paperSel          = document.getElementById("paperSize");

  this.selectedElement   = null;
  this.skipNextClick     = false;

  this.currentLogoSrc    = null;
  this.logoLoaded        = false;
  this.globalFooterText  = "Footer Text";

  // Undo/Redo history stacks
  this.undoStack = [];
  this.redoStack = [];
  this.maxHistory = 7;

  // Setup
  this.createPage();
  this.attachEventListeners();

  this.saveHistory(); // Initial state capture

  // Bind Undo/Redo buttons
  document.querySelector('[data-cmd="undo"]')?.addEventListener("click", () => this.undo());
  document.querySelector('[data-cmd="redo"]')?.addEventListener("click", () => this.redo());
}
snapToGrid(value) {
  return Math.round(value / this.SNAP_GRID_SIZE) * this.SNAP_GRID_SIZE;
} 
attachEventListeners() {
  ["justifyLeft", "justifyCenter", "justifyRight"].forEach(cmd => {
    const btn = document.querySelector(`[data-cmd="${cmd}"]`);
    if (!btn) return;

    btn.addEventListener("click", () => {
      this.alignSelectedElement(cmd);
    });
  });

  ["bold", "italic", "underline"].forEach(cmd => {
    const btn = document.querySelector(`[data-cmd="${cmd}"]`);
    if (!btn) return;

    btn.addEventListener("click", () => {
      this.toggleStyleForSelected(cmd);
    });
  });

  this.workspace.addEventListener("click", e => {
    if (this.skipNextClick) {
      this.skipNextClick = false;
      return;
    }

    const logoBox = e.target.closest(".header-logo");
    if (logoBox) return logoBox.querySelector("input[type='file']").click();

    const headerOrFooter = e.target.closest(".header-title, .header-subtitle, .footer-left");
    if (headerOrFooter) {
      this.selectElement(headerOrFooter);
      return;
    }

    const current = this.selectedElement;
    if (
      current &&
      current.contains(e.target) &&
      (e.target.closest(".element-body") || e.target.closest(".element"))
    ) return;

    let clickedElement = e.target.closest(".element, .label-block, .paragraph-block, .table-block");
    if (clickedElement && this.workspace.contains(clickedElement)) {
      this.selectElement(clickedElement);

      if (clickedElement.classList.contains("table-block")) {
        this.tableToolbar?.setTable(clickedElement.querySelector("table"));
      }

    } else {
      this.selectElement(null);
      this.tableToolbar?.clearSelection();
    }
  });

  this.fontFamilySel.addEventListener("change", () => {
    const selectedFont = this.fontFamilySel.value;
    this.defaultFontFamily = selectedFont;

    if (this.selectedElement) {
      const body =
        this.selectedElement.querySelector(".element-body, .header-title, .header-subtitle, .footer-left") ||
        this.selectedElement;

      if (body) {
        body.style.fontFamily = selectedFont;
        this.snapToGrid(this.selectedElement);
      }
    }
  });

  this.fontSizeSel.addEventListener("change", () => {
    const newSize = parseInt(this.fontSizeSel.value, 10) || 12;
    this.defaultFontSize = newSize;

    if (!this.selectedElement) return;

    const body = this.getEditableBody(this.selectedElement);

    if (
      body.classList.contains("header-title") ||
      body.classList.contains("header-subtitle")
    ) return;

    body.style.fontSize = `${newSize}px`;

    const el = this.selectedElement;
    const ROW = this.ROW_HEIGHT;

    const forceResize = () => {
      const scrollH = body.scrollHeight;
      const rows = Math.max(3, Math.ceil(scrollH / ROW));
      el.dataset.rows = rows;
      el.style.height = rows * ROW + "px";
      this.reflowContent(el.closest(".content"));
      this.snapToGrid(el);
    };

    requestAnimationFrame(forceResize);
  });

  this.workspace.addEventListener("input", e => {
    const target = e.target;

    if (target.classList.contains("header-title") || target.classList.contains("header-subtitle")) {
      const firstPage = this.workspace.querySelector(".page");
      if (target.closest(".page") === firstPage) {
        const selector = target.classList.contains("header-title") ? ".header-title" : ".header-subtitle";
        this.workspace.querySelectorAll(selector).forEach(node => {
          if (node !== target) node.innerText = target.innerText;
        });
      }
    } else if (target.classList.contains("footer-left")) {
      this.globalFooterText = target.innerText;
      this.workspace.querySelectorAll(".footer-left").forEach(node => {
        if (node !== target) node.innerText = this.globalFooterText;
      });
    }
  });

  this.workspace.addEventListener("change", e => {
    const input = e.target;
    if (input.matches("input[type='file']") && input.files[0] && input.closest(".header-logo")) {
      const reader = new FileReader();
      reader.onload = () => {
        this.currentLogoSrc = reader.result;
        this.logoLoaded = true;

        this.workspace.querySelectorAll(".page > .header > .header-logo img").forEach(img => {
          img.src = reader.result;
        });

        this.workspace.querySelectorAll(".page > .header > .header-logo").forEach(box => {
          box.classList.add("has-image");
        });
      };
      reader.readAsDataURL(input.files[0]);
    }
  });

  document.querySelectorAll(".draggable").forEach(btn =>
    btn.addEventListener("dragstart", e => {
      const type = btn.dataset.type;
      if (!type) return;
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", type);
    })
  );

  this.workspace.addEventListener("dragover", e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";
  });

  this.workspace.addEventListener("drop", e => {
    if (e._processed) return;
    const targetContent = e.target.closest(".content");
    if (!targetContent) return;
    e._processed = true;
    e.preventDefault();

    const proxyEvent = new Proxy(e, {
      get: (obj, prop) => (prop === "currentTarget" ? targetContent : obj[prop])
    });

    this.handleDrop(proxyEvent);
  });

  this.paperSel.addEventListener("change", () => {
    const { w, h } = this.SIZES[this.paperSel.value];
    this.workspace.querySelectorAll(".page").forEach(pg => {
      pg.style.width = w + "px";
      pg.style.height = h + "px";
    });
  });

  document.getElementById("addPageBtn")?.addEventListener("click", () => {
    this.createPage();
  });
}
createPage() {
  const { w, h } = this.SIZES[this.paperSel.value];
  const pg = document.createElement("div");
  pg.className = "page";
  pg.style.width = `${w}px`;
  pg.style.height = `${h}px`;

  // Clone title/subtitle from first page if it exists
  const firstPage = this.workspace.querySelector(".page");
  const title = firstPage?.querySelector(".header-title")?.innerText || "Enter Syllabus Title";
  const subtitle = firstPage?.querySelector(".header-subtitle")?.innerText || "Enter Subtitle";

  // Use logo if loaded, otherwise fallback to placeholder
  const logoSrc = this.logoLoaded
    ? this.currentLogoSrc
    : "data:image/svg+xml;utf8," + encodeURIComponent(
        `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='0.8' stroke-linecap='round' stroke-linejoin='round'>
           <rect x='3' y='3' width='18' height='18' rx='2' ry='2'/>
           <path d='M12 8v8m-4-4h8'/>
         </svg>`
      );
  const hasImgClass = this.logoLoaded ? "has-image" : "";
  const pageNum = this.workspace.children.length + 1;

  pg.innerHTML = `
    <div class="header">
      <div class="header-logo ${hasImgClass}" contenteditable="false">
        <input type="file" accept="image/*" style="display:none;" />
        <img src="${logoSrc}" alt="Logo">
      </div>
      <div class="header-texts">
        <div class="header-title" contenteditable="true">${title}</div>
        <div class="header-subtitle" contenteditable="true">${subtitle}</div>
      </div>
    </div>
    <div class="content"></div>
    <div class="footer d-flex justify-content-between">
      <div class="footer-left" contenteditable="true">${this.globalFooterText}</div>
      <div class="footer-right">Page ${pageNum}</div>
    </div>`;

  this.workspace.appendChild(pg);
  this.updatePageNumbers();

  const content = pg.querySelector(".content");

  content.addEventListener("dragover", e => e.preventDefault());

  content.addEventListener("drop", e => {
    if (e._processed) return;
    e._processed = true;
    e.preventDefault();
    this.handleDrop(e);
  });

  return content;
}
getNextContent(content) {
  const nextPage = content.closest(".page")?.nextElementSibling;
  return nextPage?.querySelector(".content") || this.createPage();
}
updatePageNumbers() {
    this.workspace.querySelectorAll(".page .footer-right").forEach((node, i) => {
      node.textContent = `Page ${i + 1}`;
    });
}
placeElement(startContent, el, y) {
  const ROW = this.ROW_HEIGHT;

  const isResizableSingleLine =
    el.classList.contains("label-block") ||
    el.classList.contains("text-field");

  const isSignature = el.classList.contains("signature-block");

  const isAutoResizable =
    el.classList.contains("table-block") ||
    isSignature ||
    isResizableSingleLine;

  const body = el.querySelector(".element-body");

  // Step 1: Measure and set height if needed
  if (isAutoResizable) {
    document.body.appendChild(el);
    el.style.position = "absolute";
    el.style.visibility = "hidden";
    el.style.height = "auto";
    if (body) body.style.height = "auto";

    // ⚠️ Patch: Correct signature height handling
    let measuredHeight;
    if (isSignature) {
      const canvas = el.querySelector("canvas");
      measuredHeight = canvas ? canvas.offsetHeight : el.scrollHeight;
    } else {
      measuredHeight = el.scrollHeight;
    }

    const snapped = this.snapToGrid(measuredHeight);
    const rows = Math.max(1, Math.ceil(snapped / ROW));

    el.dataset.rows = rows;
    el.style.height = `${snapped}px`;
    if (body) body.style.height = `${snapped}px`;

    el.style.position = "";
    el.style.visibility = "";

    if (el.parentElement === document.body) {
      document.body.removeChild(el);
    }
  }

  // Step 2: Find where to place
  const visited = new Set();
  let content = startContent;
  let currentY = y;

  while (true) {
    if (visited.has(content)) return;
    visited.add(content);

    const contentHeight = content.clientHeight;
    const usableHeight = contentHeight - 2 * this.DROP_MARGIN;

    const rows = parseInt(el.dataset.rows || 1, 10);
    const maxRows = Math.floor(usableHeight / ROW);
    const insertRow = Math.max(
      0,
      Math.min(Math.floor(currentY / ROW), maxRows - rows)
    );

    const topOffset = this.DROP_MARGIN + insertRow * ROW;
    el.style.top = `${topOffset}px`;

    // Step 3: Append to content
    if (!content.contains(el)) {
      content.appendChild(el);
    }

    this.addRemoveButton(el);
    this.makeMovable(el);

    const blocks = Array.from(content.querySelectorAll(".element, .label-block, .paragraph-block"))
      .filter(b => b !== el)
      .sort((a, b) => parseInt(a.style.top || 0, 10) - parseInt(b.style.top || 0, 10));

    let cursor = topOffset + rows * ROW;

    for (const blk of blocks) {
      const blkTop = parseInt(blk.style.top || 0, 10);
      const blkHeight = this.snapToGrid(blk.offsetHeight);
      const blkBottom = blkTop + blkHeight;

      if (blkTop < cursor && blkBottom > topOffset) {
        blk.style.top = `${cursor}px`;
        cursor += blkHeight;
      }

      const usableBottom = content.clientHeight - this.DROP_MARGIN;
      const newBottom = parseInt(blk.style.top, 10) + blkHeight;

      if (newBottom > usableBottom) {
        content.removeChild(blk);
        this.placeElement(this.getNextContent(content), blk, 0);
      }
    }

    // Step 4: Watch for font changes
    if (isResizableSingleLine && !el.__resizeObserverAttached) {
      this.setupAutoResizeSingleLine(el);
      el.__resizeObserverAttached = true;
    }

    // Step 5: Restore selection highlight
    this.selectElement(el);

    break;
  }
}
handleDrop(e) {
  e.preventDefault();

  const type = e.dataTransfer.getData("text/plain") || e.dataTransfer.getData("type");
  if (!type) return;

  const el = document.createElement("div");
  el.dataset.type = type;
  el.classList.add("element");

  const isLabel     = type === "label";
  const isTextField = type === "text-field";
  const isText3     = type === "text-3";
  const isParagraph = type === "paragraph";
  const isTable     = type === "table";
  const isSignature = type === "signature";

  if (isLabel || isTextField) el.classList.add("label-block");
  if (isTextField) el.classList.add("text-field");
  if (isText3) el.classList.add("text-3");
  if (isParagraph) el.classList.add("paragraph-block");
  if (isTable) el.classList.add("table-block");
  if (isSignature) el.classList.add("signature-block");

  const body = document.createElement((isTable || isSignature) ? "table" : "div");
  body.className = "element-body";
  body.contentEditable = !(isTable || isSignature);

  const font = this.fontFamilySel?.value || this.defaultFontFamily;
  const size = this.fontSizeSel?.value || this.defaultFontSize;
  body.style.fontFamily = font;
  body.style.fontSize = `${size}px`;

  if (isLabel || isTextField) {
    body.textContent = "Label text";
    body.style.whiteSpace = "nowrap";
    el.appendChild(body);
    el.dataset.rows = 1;
    el.style.height = this.snapToGrid(this.ROW_HEIGHT) + "px";
  }

  else if (isText3 || isParagraph) {
    body.textContent = isText3 ? "Text block" : "Paragraph text";
    el.appendChild(body);
    const rows = isText3 ? 3 : 1;
    el.dataset.rows = rows;
    el.style.height = this.snapToGrid(rows * this.ROW_HEIGHT) + "px";
  }

  else if (isTable) {
    for (let i = 0; i < 3; i++) {
      const tr = document.createElement("tr");
      for (let j = 0; j < 3; j++) {
        const td = document.createElement("td");
        td.textContent = " ";
        td.contentEditable = true;
        tr.appendChild(td);
      }
      body.appendChild(tr);
    }
    el.appendChild(body);
  }

  else if (isSignature) {
    body.innerHTML = "";
    const table = document.createElement("table");
    table.className = "signature-table";
    const row = document.createElement("tr");

    for (let i = 0; i < 4; i++) {
      const cell = document.createElement("td");
      cell.className = "signature-cell";

      const imgWrapper = document.createElement("div");
      imgWrapper.className = "signature-img-wrapper";

      const img = document.createElement("img");
      img.className = "signature-img";
      img.style.display = "none";
      imgWrapper.appendChild(img);

      const btn = document.createElement("button");
      btn.textContent = "Upload";
      btn.className = "upload-btn inside-wrapper";

      const input = document.createElement("input");
      input.type = "file";
      input.accept = "image/*";
      input.style.display = "none";

      btn.onclick = () => input.click();
      input.onchange = () => {
        if (input.files[0]) {
          const reader = new FileReader();
          reader.onload = () => {
            img.src = reader.result;
            img.style.display = "block";
            btn.style.display = "none";
            imgWrapper.classList.add("filled");
          };
          reader.readAsDataURL(input.files[0]);
        }
      };

      imgWrapper.appendChild(btn);

      const line = document.createElement("div");
      line.className = "signature-line";

      const makeLabel = (text) => {
        const div = document.createElement("div");
        div.textContent = text;
        div.contentEditable = true;
        div.className = "signature-label";

        div.addEventListener("keydown", e => {
          if (e.key === "Enter") e.preventDefault();

          const range = document.createRange();
          range.selectNodeContents(div);
          const rect = range.getBoundingClientRect();
          const parentRect = div.parentElement.getBoundingClientRect();
          const buffer = 8;

          if (rect.width > parentRect.width - buffer &&
              !["Backspace", "Delete", "ArrowLeft", "ArrowRight"].includes(e.key)) {
            e.preventDefault();
            div.style.border = "1px solid red";
            setTimeout(() => (div.style.border = "none"), 200);
          }
        });

        div.addEventListener("paste", e => {
          e.preventDefault();
          const pasted = (e.clipboardData || window.clipboardData).getData("text").trim();
          document.execCommand("insertText", false, pasted);
        });

        return div;
      };

      cell.appendChild(imgWrapper);
      cell.appendChild(input);
      cell.appendChild(line);
      cell.appendChild(makeLabel("Name"));
      cell.appendChild(makeLabel("Date"));
      cell.appendChild(makeLabel("Role"));

      row.appendChild(cell);
    }

    table.appendChild(row);
    body.appendChild(table);
    el.appendChild(body);

    // Measure to set rows and height correctly
    document.body.appendChild(el);
    el.style.position = "absolute";
    el.style.visibility = "hidden";

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        const actual = el.offsetHeight;
        const rows = Math.ceil(actual / this.ROW_HEIGHT);
        el.dataset.rows = rows;
        el.style.height = `${this.snapToGrid(rows * this.ROW_HEIGHT)}px`;

        el.style.position = "";
        el.style.visibility = "";
        document.body.removeChild(el);
      });
    });
  }

  // Append grip handle
  const grip = document.createElement("div");
  grip.className = "drag-handle";
  grip.innerHTML = "<i class='bi bi-grip-vertical'></i>";
  el.prepend(grip);

  // Initial drop positioning
  const content = e.currentTarget;
  const insertY = this.snapToGrid(e.offsetY);
  el.style.top = `${insertY}px`;
  el.style.left = "32px";
  el.style.width = "calc(100% - 32px)";
  el.style.boxSizing = "border-box";
  el.style.position = "absolute";

  // Place element into the page
  this.placeElement(content, el, insertY);

  // Setup
  if (isLabel || isTextField) {
    this.setupLabelInputRestrictions(el);
    this.applyLabelSuggestion(el);
  }

  if (isText3 || isParagraph) {
    this.setupTextArea(el);
    const resizeEvt = new Event("input");
    el.querySelector(".element-body").dispatchEvent(resizeEvt);
  }

  this.makeMovable(el);
  this.addRemoveButton(el);
  this.selectElement(el);
  this.saveHistory();
}
makeMovable(el) {
  const grip = el.querySelector(".drag-handle");
  if (!grip) return;

  // Prevent the grip itself from being draggable or duplicated
  grip.setAttribute("draggable", "false");
  grip.addEventListener("dragstart", e => e.preventDefault());

  grip.addEventListener("mousedown", startEvt => {
    this.selectElement(el);
    startEvt.preventDefault();
    document.body.style.userSelect = "none";

    const offsetInside = startEvt.clientY - el.getBoundingClientRect().top;

    const onMove = mv => {
      const targetContent = document.elementFromPoint(mv.clientX, mv.clientY)?.closest(".content");
      if (!targetContent) return;

      const contentRect = targetContent.getBoundingClientRect();
      const usableHeight = targetContent.clientHeight - 2 * this.DROP_MARGIN;

      const proposedY = mv.clientY - contentRect.top - offsetInside;
      const maxSnap = usableHeight - parseInt(el.dataset.rows || 1) * this.ROW_HEIGHT;
      const snappedY = this.snapToGrid(Math.max(0, Math.min(proposedY, maxSnap)));

      Object.assign(this.ghostLine.style, {
        top: contentRect.top + snappedY + "px",
        left: contentRect.left + "px",
        width: contentRect.width + "px",
        display: "block"
      });

      this.ghostTarget = { content: targetContent, offsetY: snappedY };
    };

    const onUp = () => {
      document.body.style.userSelect = "";
      document.removeEventListener("mousemove", onMove);
      document.removeEventListener("mouseup", onUp);

      this.ghostLine.style.display = "none";

      if (this.ghostTarget) {
        this.placeElement(this.ghostTarget.content, el, this.ghostTarget.offsetY);
        this.ghostTarget = null;
        this.skipNextClick = true;
      }
    };

    document.addEventListener("mousemove", onMove);
    document.addEventListener("mouseup", onUp);
  });
}
reflowContent(content) {
  const ROW = this.ROW_HEIGHT;
  const DROP_MARGIN = this.DROP_MARGIN;

  const blocks = Array.from(content.querySelectorAll(".element, .label-block, .paragraph-block"))
    .filter(el => !el.classList.contains("dragging"))
    .sort((a, b) => a.offsetTop - b.offsetTop); // Sort by visual top

  for (let i = 0; i < blocks.length; i++) {
    const blk = blocks[i];
    let blkTop = blk.offsetTop;

    // Snap top using global snapToGrid
    blkTop = this.snapToGrid(blkTop);
    blk.style.top = `${blkTop}px`;

    // Snap height
    const blkIsLabel = blk.classList.contains("label-block");
    let snappedHeight = Math.ceil(blk.offsetHeight / ROW) * ROW;
    if (blkIsLabel && snappedHeight < ROW) snappedHeight = ROW;

    if (blk.offsetHeight !== snappedHeight) {
      blk.style.height = `${snappedHeight}px`;
    }

    const blkBottom = blkTop + snappedHeight;
    let cursor = blkBottom;

    // Push blocks below if overlapping
    for (let j = i + 1; j < blocks.length; j++) {
      const nextBlk = blocks[j];
      const nextTop = parseInt(nextBlk.style.top || 0);
      const nextHeight = Math.ceil(nextBlk.offsetHeight / ROW) * ROW;

      if (nextTop < cursor) {
        nextBlk.style.top = `${cursor}px`;
        cursor += nextHeight;
      } else {
        break;
      }

      // Handle overflow to next page
      const usableHeight = content.clientHeight - 2 * DROP_MARGIN;
      const newBottom = parseInt(nextBlk.style.top) + nextHeight;
      if (newBottom > usableHeight) {
        content.removeChild(nextBlk);
        this.placeElement(this.getNextContent(content), nextBlk, 0);
        blocks.splice(j, 1);
        j--;
      }
    }
  }
}
pushElementsDown(sourceEl) {
  const ROW = this.ROW_HEIGHT;
  const content = sourceEl.closest(".content");
  if (!content) return;

  const usableHeight = content.clientHeight - this.DROP_MARGIN;

  let top = parseInt(sourceEl.style.top || 0, 10);
  let height = parseInt(sourceEl.style.height || 0, 10);
  let bottom = top + height;

  const all = [...content.querySelectorAll(".element")].filter(el => el !== sourceEl);

  // Sort by top position
  all.sort((a, b) => parseInt(a.style.top || 0, 10) - parseInt(b.style.top || 0, 10));

  for (let i = 0; i < all.length; i++) {
    const el = all[i];
    let elTop = parseInt(el.style.top || 0, 10);
    let elHeight = parseInt(el.style.height || 0, 10);
    let elBottom = elTop + elHeight;

    const overlap = elTop < bottom && elBottom > top;

    if (overlap) {
      // Snap new top to next row-aligned position after current bottom
      const newTop = Math.ceil(bottom / ROW) * ROW;

      if (newTop + elHeight > usableHeight) {
        // Overflow: move to next page
        content.removeChild(el);
        this.placeElement(this.getNextContent(content), el, 0);
        all.splice(i, 1);
        i--;
      } else {
        // Snap and reposition
        el.style.top = `${newTop}px`;
        bottom = newTop + elHeight;
      }
    } else {
      bottom = Math.max(bottom, elBottom);
    }
  }

  // Check if source itself overflows after shifting
  const finalTop = parseInt(sourceEl.style.top || 0, 10);
  const finalBottom = finalTop + parseInt(sourceEl.style.height || 0, 10);
  if (finalBottom > usableHeight) {
    content.removeChild(sourceEl);
    this.placeElement(this.getNextContent(content), sourceEl, 0);
  }
}
rebindWorkspace() {
  this.workspace.querySelectorAll(".element, .label-block, .paragraph-block").forEach(el => {
    const type = el.dataset.type;

    this.addRemoveButton(el);
    this.makeMovable(el);

    if (type === "label" || el.classList.contains("label-block")) {
      this.setupLabelInputRestrictions(el);
      this.applyLabelSuggestion(el);
    }

    if (type === "text-3" || type === "paragraph") {
      this.setupTextArea(el);
    }

    // Resizing based on actual height
    const isResizableSingleLine =
      el.classList.contains("label-block") ||
      el.classList.contains("text-field");

    if (isResizableSingleLine) {
      const height = this.snapToGrid(el.offsetHeight);
      const rows = height / this.ROW_HEIGHT;
      el.dataset.rows = rows;
      el.style.height = `${height}px`;
    }

    // Snap all elements to the grid
    const top = parseInt(el.style.top || 0, 10);
    const height = parseInt(el.style.height || 0, 10);
    el.style.top = `${Math.round(top / this.ROW_HEIGHT) * this.ROW_HEIGHT}px`;
    el.style.height = `${Math.round(height / this.ROW_HEIGHT) * this.ROW_HEIGHT}px`;
  });

  this.workspace.querySelectorAll(".content").forEach(content => {
    content.addEventListener("dragover", e => e.preventDefault());

    content.addEventListener("drop", e => {
      if (e._processed) return;
      e._processed = true;
      e.preventDefault();
      this.handleDrop(e);
    });
  });

  if (!this.ghostLine || !this.workspace.contains(this.ghostLine)) {
    this.ghostLine = document.createElement("div");
    this.ghostLine.className = "ghost-line";
    this.ghostLine.style.display = "none";
    this.workspace.appendChild(this.ghostLine);
  }

  this.updatePageNumbers();
}
selectElement(el) {
  if (this.selectedElement) {
    this.selectedElement.classList.remove("selected");

    // Disable user-select for previously selected element
    const prevBody = this.selectedElement.querySelector(".element-body");
    if (prevBody) prevBody.style.userSelect = "none";
  }

  this.selectedElement = null;

  document.querySelectorAll('[data-cmd^="justify"], [data-cmd="bold"], [data-cmd="italic"], [data-cmd="underline"]')
    .forEach(btn => btn.classList.remove("active"));

  if (!el) {
    this.tableToolbar.hide();
    return;
  }

  el.classList.add("selected");
  this.selectedElement = el;

  // ✅ Enable user-select only for selected element
  const body = el.querySelector(".element-body, .header-title, .header-subtitle, .footer-left") || el;
  if (body && body.classList.contains("element-body")) {
    body.style.userSelect = "text";
  }

  // Snap position of selected element (optional: for visual alignment)
  const top  = parseInt(el.style.top || 0, 10);
  const left = parseInt(el.style.left || 0, 10);
  el.style.top  = `${this.snapToGrid(top)}px`;
  el.style.left = `${this.snapToGrid(left)}px`;

  const cs = window.getComputedStyle(body);

  const fam = cs.fontFamily.split(",")[0].replace(/["']/g, "").trim();
  if (fam && this.fontFamilySel.value !== fam) {
    this.fontFamilySel.value = fam;
  }

  const sz = parseInt(cs.fontSize, 10);
  if (!isNaN(sz) && this.fontSizeSel.value !== String(sz)) {
    this.fontSizeSel.value = String(sz);
  }

  const fontStyles = {
    bold:      ["fontWeight",    "bold"],
    italic:    ["fontStyle",     "italic"],
    underline: ["textDecoration","underline"]
  };

  for (const [cmd, [prop, val]] of Object.entries(fontStyles)) {
    const btn = document.querySelector(`[data-cmd="${cmd}"]`);
    if (!btn) continue;

    const current = cs[prop];
    let matches = false;

    if (prop === "textDecoration") {
      matches = current.includes(val);
    } else if (prop === "fontWeight") {
      matches = current === "bold" || parseInt(current, 10) >= 600;
    } else {
      matches = current === val;
    }

    if (matches) btn.classList.add("active");
  }

  const align = cs.textAlign || "left";
  const cmd = align === "center" ? "justifyCenter"
            : align === "right"  ? "justifyRight"
            : "justifyLeft";
  const alignBtn = document.querySelector(`[data-cmd="${cmd}"]`);
  if (alignBtn) alignBtn.classList.add("active");

  // === Table toolbar logic ===
  if (el.classList.contains("table-block")) {
    const table = el.querySelector("table");

    if (!table._toolbarBound) {
      table.addEventListener("click", e => {
        if (e.target.tagName === "TD") {
          this.tableToolbar.showForTable(table, e.target);
        }
      });
      table._toolbarBound = true;
    }

    const firstCell = table.querySelector("td");
    this.tableToolbar.showForTable(table, firstCell);
  } else {
    this.tableToolbar.hide();
  }
}
toggleStyleForSelected(cmd) {
  if (!this.selectedElement) return;

  const target = this.getEditableBody(this.selectedElement);
  if (!target) return;

  const styleMap = {
    bold:      ["fontWeight",    "bold",      "normal"],
    italic:    ["fontStyle",     "italic",    "normal"],
    underline: ["textDecoration","underline", "none"]
  };

  const style = styleMap[cmd];
  if (!style) return;

  const [prop, onVal, offVal] = style;

  // Normalize existing value
  let current = target.style[prop] || "";
  let isOn;

  if (prop === "textDecoration") {
    isOn = current.includes(onVal);
  } else if (prop === "fontWeight") {
    isOn = current === "bold" || parseInt(current, 10) >= 600;
  } else {
    isOn = current === onVal;
  }

  target.style[prop] = isOn ? offVal : onVal;

  // Re-apply toolbar state
  this.selectElement(this.selectedElement);

  // Update history
  this.saveHistory();
}
sanitizePaste(e) {
  e.preventDefault();

  const text = (e.clipboardData || window.clipboardData)
    .getData("text")
    .replace(/[\r\n]+/g, " ")    // Convert newlines to spaces
    .trim();                     // Remove leading/trailing spaces

  const selection = window.getSelection();
  if (!selection.rangeCount) return;

  const range = selection.getRangeAt(0);
  range.deleteContents();
  range.insertNode(document.createTextNode(text));

  // Move cursor to the end of the inserted text
  range.collapse(false);
  selection.removeAllRanges();
  selection.addRange(range);
}
setupTextArea(el) {
  const body = el.querySelector(".element-body");
  if (!body) return;

  const type = el.dataset.type;
  const ROW = this.ROW_HEIGHT;
  const minRows = type === "paragraph" ? 1 : 3;

  body.style.resize = "none";

  const observeResize = () => {
    const snapped = Math.round(el.offsetHeight / ROW);
    el.dataset.rows = snapped;
    el.style.height = `${snapped * ROW}px`;
  };

  requestAnimationFrame(() => {
    new ResizeObserver(observeResize).observe(el);
  });

  let prevHTML = body.innerHTML;

  const resizeToContent = () => {
    const maxHeight = this.maxAllowedHeight(el);
    const currentRows = parseInt(el.dataset.rows || minRows, 10);

    // Allow shrink
    if (currentRows > minRows) el.style.height = "auto";

    const contentHeight = body.scrollHeight;

    if (contentHeight > maxHeight) {
      el.style.height = `${currentRows * ROW}px`;
      body.innerHTML = prevHTML;
      this.restoreCursor(body);
      return;
    }

    const rawRows = contentHeight / ROW;
    const newRows = Math.max(minRows, Math.ceil(rawRows));
    if (newRows !== currentRows) {
      el.dataset.rows = newRows;
      el.style.height = `${newRows * ROW}px`;
      requestAnimationFrame(() => {
        this.reflowContent(el.closest(".content"));
      });
    } else {
      // Snap height to grid even if row count didn't change
      const snappedHeight = newRows * ROW;
      if (el.offsetHeight !== snappedHeight) {
        el.style.height = `${snappedHeight}px`;
      }
    }

    prevHTML = body.innerHTML;
  };

  body.addEventListener("keydown", e => {
    if (["Enter", "Backspace", "Delete"].includes(e.key)) {
      prevHTML = body.innerHTML;
      requestAnimationFrame(resizeToContent);
    }
  });

  body.addEventListener("input", () => {
    resizeToContent();
    this.saveHistory();
  });

  body.addEventListener("paste", e => {
    this.sanitizePaste(e);
    requestAnimationFrame(resizeToContent);
  });

  requestAnimationFrame(() => {
    this.setupGripResize(el, body);
    resizeToContent(); // Ensure it's initialized correctly
  });
}
setupAutoResizeSingleLine(el) {
  const ROW = this.ROW_HEIGHT;
  const body = el.querySelector(".element-body");
  if (!body) return;

  const resize = () => {
    el.style.height = "auto";
    body.style.height = "auto";
    const h = this.snapToGrid(el.scrollHeight);
    el.style.height = `${h}px`;
    body.style.height = `${h}px`;
    el.dataset.rows = h / ROW;
  };

  const observer = new ResizeObserver(resize);
  observer.observe(body);
}
setupLabelAndTextFieldResize(el) {
  const body = el.querySelector(".element-body");
  if (!body) return;

  const ROW = this.ROW_HEIGHT;
  const minRows = 1;
  const type = el.dataset.type;

  // Prevent manual resizing
  body.style.resize = "none";

  // Initial observation
  requestAnimationFrame(() => {
    new ResizeObserver(() => {
      const snapped = Math.round(el.offsetHeight / ROW);
      el.dataset.rows = snapped;
      el.style.height = `${snapped * ROW}px`;
    }).observe(el);
  });

  let prevHTML = body.innerHTML;

  const resizeToFitContent = () => {
    const maxHeight = this.maxAllowedHeight(el);
    const currentRows = parseInt(el.dataset.rows || minRows, 10);

    // Reset height to shrink first
    if (currentRows > minRows) {
      el.style.height = "auto";
    }

    const contentHeight = body.scrollHeight;

    if (contentHeight > maxHeight) {
      el.style.height = `${currentRows * ROW}px`;
      body.innerHTML = prevHTML;
      this.restoreCursor(body);
      return;
    }

    const rawRows = contentHeight / ROW;
    const newRows = Math.max(minRows, Math.ceil(rawRows));

    if (newRows !== currentRows) {
      el.dataset.rows = newRows;
      el.style.height = `${newRows * ROW}px`;
      requestAnimationFrame(() => {
        this.reflowContent(el.closest(".content"));
      });
    } else {
      // Force snap to grid if needed
      const snapped = newRows * ROW;
      if (el.offsetHeight !== snapped) {
        el.style.height = `${snapped}px`;
      }
    }

    prevHTML = body.innerHTML;
  };

  body.addEventListener("input", resizeToFitContent);
  body.addEventListener("keydown", e => {
    if (["Enter", "Backspace", "Delete"].includes(e.key)) {
      prevHTML = body.innerHTML;
      requestAnimationFrame(resizeToFitContent);
    }
  });
  body.addEventListener("paste", e => this.sanitizePaste(e));

  requestAnimationFrame(() => {
    this.setupGripResize(el, body);
  });

  body.addEventListener("input", () => this.saveHistory());
}
setupLabelInputRestrictions(labelElement) {
  const body = labelElement.querySelector(".element-body");
  if (!body) return;

  let prevText = body.innerText;

  body.addEventListener("keydown", (e) => {
    if (e.key === "Enter") e.preventDefault();
  });

  body.addEventListener("paste", (e) => {
    this.sanitizePaste(e);
    requestAnimationFrame(() => enforceLimit());
  });

  const enforceLimit = () => {
    const clone = body.cloneNode(true);
    clone.style.visibility = "hidden";
    clone.style.position = "absolute";
    clone.style.whiteSpace = "nowrap";
    clone.style.width = body.clientWidth + "px";
    clone.style.maxWidth = "none";
    clone.style.pointerEvents = "none";
    body.parentNode.appendChild(clone);

    clone.innerText = body.innerText;

    if (clone.scrollWidth > body.clientWidth) {
      body.innerText = prevText;
      this.restoreCursor(body);
    } else {
      prevText = body.innerText;
    }

    clone.remove();
  };

  body.addEventListener("input", () => {
    enforceLimit();
  });
}
alignSelectedElement(cmd) {
  if (!this.selectedElement) return;

  const target = this.getEditableBody(this.selectedElement);
  if (!target) return;

  const alignment = {
    justifyLeft: "left",
    justifyCenter: "center",
    justifyRight: "right"
  }[cmd] || "left";

  target.style.textAlign = alignment;

  document.querySelectorAll('[data-cmd^="justify"]').forEach(btn =>
    btn.classList.remove("active")
  );

  const btn = document.querySelector(`[data-cmd="${cmd}"]`);
  if (btn) btn.classList.add("active");
}
getEditableBody(el) {
  return el.querySelector(".element-body, .header-title, .header-subtitle, .footer-left") || el;
}
restoreCursor(body) {
  const range = document.createRange();
  range.selectNodeContents(body);
  range.collapse(false);

  const sel = window.getSelection();
  sel.removeAllRanges();
  sel.addRange(range);
}
maxAllowedHeight(el) {
  const content = el.closest(".content");
  const top = parseInt(el.style.top || "0", 10);
  return content.offsetHeight - this.DROP_MARGIN - top;
}
refreshElementSize(el) {
  const ROW = this.ROW_HEIGHT;
  const rows = Math.max(1, Math.round(el.offsetHeight / ROW));
  el.dataset.rows = rows;
  el.style.height = `${rows * ROW}px`;
}
addRemoveButton(el) {
  // Avoid adding multiple remove buttons
  if (el.querySelector(".remove-btn")) return;

  const btn = document.createElement("button");
  btn.className = "remove-btn";
  btn.innerHTML = "&times;";
  btn.title = "Remove Element";

  btn.addEventListener("click", e => {
    e.stopPropagation();
    el.remove();
    this.cleanupPages?.(); // Safe call in case cleanupPages is undefined
  });

  el.appendChild(btn);
}
saveHistory() {
  const currentState = this.workspace.innerHTML;
  const lastState = this.undoStack[this.undoStack.length - 1];

  if (currentState === lastState) return;

  this.undoStack.push(currentState);
  if (this.undoStack.length > this.maxHistory) {
    this.undoStack.shift(); // remove oldest
  }

  this.redoStack.length = 0; // clear redo stack
}
undo() {
  if (this.undoStack.length <= 1) return; // Nothing to undo

  const currentState = this.undoStack.pop();
  this.redoStack.push(currentState);

  const previousState = this.undoStack[this.undoStack.length - 1];
  if (previousState) {
    this.workspace.innerHTML = previousState;
    this.rebindWorkspace(); // re-attach all handlers
  }
}
redo() {
  if (this.redoStack.length === 0) return; // Nothing to redo

  const nextState = this.redoStack.pop();
  if (nextState) {
    this.undoStack.push(nextState);
    this.workspace.innerHTML = nextState;
    this.rebindWorkspace(); // re-attach all handlers
  }
}
applyLabelSuggestion(newLabel) {
  const allLabels = Array.from(newLabel.parentElement.querySelectorAll(".label-block"))
    .filter(label => label !== newLabel)
    .sort((a, b) => parseInt(a.style.top || 0) - parseInt(b.style.top || 0));

  const prevLabel = allLabels
    .reverse()
    .find(label => parseInt(label.style.top || 0) < parseInt(newLabel.style.top || 0));

  if (!prevLabel) return;

  const prevText = prevLabel.querySelector(".element-body")?.innerText?.trim() || "";
  const pattern = this.parsePattern(prevText);
  if (pattern.type === "none") return;

  const nextValue = this.nextPatternValue(pattern);
  const body = newLabel.querySelector(".element-body");
  if (body) body.innerText = this.patternToString({ ...pattern, value: nextValue });
}
parsePattern(text) {
  const trimmed = text.trim();

  // Match common patterns like: 1. A. I. • -
  const match = trimmed.match(/^([IVXLCDM]+\.|\d+\.|[A-Z]\.|[-•])\s*/i);
  if (!match) return { type: "none" };

  const prefix = match[1];

  if (/^\d+\.$/.test(prefix)) {
    return { type: "arabic", value: parseInt(prefix, 10) };
  }

  if (/^[IVXLCDM]+\.$/i.test(prefix)) {
    const roman = prefix.slice(0, -1); // remove trailing dot
    return { type: "roman", value: this.romanToInt(roman) };
  }

  if (/^[A-Z]\.$/.test(prefix)) {
    return { type: "alpha", value: prefix.charCodeAt(0) - 64 }; // 'A' = 65
  }

  if (/^[-•]$/.test(prefix)) {
    return { type: "bullet", value: "•" };
  }

  return { type: "none" };
}
nextPatternValue(pattern) {
  if (!pattern || pattern.type === "none") return null;
  return pattern.type === "bullet" ? "•" : pattern.value + 1;
}
patternToString(pattern) {
  switch (pattern.type) {
    case "arabic":
      return `${pattern.value}.`;
    case "roman":
      return `${this.intToRoman(pattern.value)}.`;
    case "alpha":
      return `${String.fromCharCode(64 + pattern.value)}.`; // 1 => A
    case "bullet":
      return "•";
    default:
      return "";
  }
}
intToRoman(num) {
  if (typeof num !== "number" || num <= 0 || num >= 4000) return "";

  const romanMap = [
    [1000, "M"], [900, "CM"], [500, "D"], [400, "CD"],
    [100, "C"], [90, "XC"], [50, "L"], [40, "XL"],
    [10, "X"], [9, "IX"], [5, "V"], [4, "IV"], [1, "I"]
  ];

  let result = "";
  for (const [value, symbol] of romanMap) {
    while (num >= value) {
      result += symbol;
      num -= value;
    }
  }
  return result;
}
romanToInt(romanStr) {
  if (typeof romanStr !== "string") return 0;

  const map = { I:1, V:5, X:10, L:50, C:100, D:500, M:1000 };
  let total = 0;
  let prevValue = 0;

  for (let i = romanStr.length - 1; i >= 0; i--) {
    const char = romanStr[i].toUpperCase();
    const current = map[char] || 0;

    if (current < prevValue) {
      total -= current;
    } else {
      total += current;
      prevValue = current;
    }
  }

  return total;
}
}
window.TemplateBuilder = TemplateBuilder;
document.addEventListener("DOMContentLoaded", () => {
  new TemplateBuilder();
});

class TableToolbar {
 constructor(builder) {
    this.builder = builder;
    this.toolbarEl = document.getElementById("tableToolbar");
    this.workspace = document.getElementById("workspace");

    this.table = null;
    this.selectedCell = null;
    this.selectedCells = new Set();

    this.onTableChanged = () => {};

    // Bind toolbar button commands
    this.toolbarEl.querySelectorAll("[data-table-cmd]").forEach(btn => {
      const cmd = btn.getAttribute("data-table-cmd");
      btn.addEventListener("click", () => this.handleCommand(cmd));
    });

    // Auto-resnap when resized
    this.resizeObserver = new ResizeObserver(() => this.resnapAndReflow());
}
showForTable(tableEl, cellEl = null) {
  if (!tableEl) return;

  this.table = tableEl;
  this.selectedCell = cellEl || tableEl.querySelector("td");

  // Show toolbar and update workspace class
  this.toolbarEl.classList.remove("d-none");
  this.workspace.classList.add("table-toolbar-visible");

  // Rebind events and update UI
  this.rebindTableCellEvents();
  this.updateCellSelection();

  // Observe table size changes (e.g., for snapping or layout recalculations)
  this.resizeObserver.disconnect();
  this.resizeObserver.observe(this.table);

  // Optional: Lock editing if table violates grid rules or page limits
  const isLocked = typeof this.isTableAtPageLimit === "function" && this.isTableAtPageLimit();
  isLocked ? this.lockCellEditing() : this.unlockCellEditing();
}
hide() {
  // Hide toolbar and cleanup workspace styling
  this.toolbarEl.classList.add("d-none");
  this.workspace.classList.remove("table-toolbar-visible");

  // Clear selection states from table cells
  if (this.table) {
    this.table.querySelectorAll("td").forEach(td => {
      td.classList.remove("multi-selected", "selected-cell");
    });
  }

  // Reset state
  this.table = null;
  this.selectedCell = null;
  this.selectedCells.clear();
  this.resizeObserver.disconnect();
}
isTableAtPageLimit() {
  const container = this.table?.closest(".element");
  const content = container?.closest(".content");
  if (!container || !content) return false;

  const ROW = this.builder.ROW_HEIGHT;
  const DROP_MARGIN = this.builder.DROP_MARGIN || 20;

  const usableHeight = content.clientHeight - DROP_MARGIN;

  const containerTop = parseInt(container.style.top || "0", 10);
  const containerHeight = parseInt(container.style.height || "0", 10);

  const bottomEdge = containerTop + containerHeight;

  return bottomEdge >= usableHeight;
}
lockCellEditing() {
  if (!this.table) return;
  this.table.classList.add("locked");

  const allCells = this.table.querySelectorAll("td");
  allCells.forEach(cell => {
    cell.setAttribute("contenteditable", "false");
    cell.classList.add("locked-cell");
  });
}
unlockCellEditing() {
  if (!this.table) return;
  this.table.classList.remove("locked");

  const allCells = this.table.querySelectorAll("td");
  allCells.forEach(cell => {
    cell.setAttribute("contenteditable", "true");
    cell.classList.remove("locked-cell");
  });
}
rebindTableCellEvents() {
  if (!this.table) return;

  const allCells = Array.from(this.table.querySelectorAll("td"));

  // Builds map of cell positions, spans, and boundaries
  const buildCellMaps = () => {
    const map = [];
    const rows = Array.from(this.table.rows);
    const cellData = new Map(); // cell -> { row, col, rowspan, colspan }

    for (let r = 0; r < rows.length; r++) map[r] = [];

    for (let r = 0; r < rows.length; r++) {
      const row = rows[r];
      let col = 0;

      for (const cell of row.cells) {
        while (map[r][col]) col++;

        const rowspan = cell.rowSpan || 1;
        const colspan = cell.colSpan || 1;

        for (let dr = 0; dr < rowspan; dr++) {
          for (let dc = 0; dc < colspan; dc++) {
            map[r + dr][col + dc] = cell;
          }
        }

        cellData.set(cell, {
          row: r,
          col: col,
          rowspan: rowspan,
          colspan: colspan,
          endRow: r + rowspan - 1,
          endCol: col + colspan - 1
        });

        col += colspan;
      }
    }

    return { map, cellData };
  };

  allCells.forEach(td => {
    td.onclick = (e) => {
      const { cellData } = buildCellMaps();
      const targetCell = td;

      if (e.shiftKey && this.anchorCell) {
        const anchor = cellData.get(this.anchorCell);
        const target = cellData.get(targetCell);
        if (!anchor || !target) return;

        // Full bounding box
        const minRow = Math.min(anchor.row, target.row);
        const maxRow = Math.max(anchor.endRow, target.endRow);
        const minCol = Math.min(anchor.col, target.col);
        const maxCol = Math.max(anchor.endCol, target.endCol);

        // Select only fully-contained cells
        const selected = new Set();
        for (const [cell, info] of cellData.entries()) {
          const isFullyInside =
            info.row >= minRow &&
            info.endRow <= maxRow &&
            info.col >= minCol &&
            info.endCol <= maxCol;

          if (isFullyInside) selected.add(cell);
        }

        this.selectedCells.clear();
        allCells.forEach(cell => cell.classList.remove("multi-selected", "selected-cell"));
        selected.forEach(cell => {
          cell.classList.add("multi-selected");
          this.selectedCells.add(cell);
        });

        this.selectedCell = targetCell;
        targetCell.classList.add("selected-cell");
        this.updateCellSelection();

      } else {
        // Single click
        this.anchorCell = targetCell;
        this.selectedCell = targetCell;
        this.selectedCells.clear();

        allCells.forEach(cell => cell.classList.remove("multi-selected", "selected-cell"));
        targetCell.classList.add("multi-selected", "selected-cell");
        this.selectedCells.add(targetCell);

        this.updateCellSelection();
        this.showForTable(this.table, targetCell);
      }
    };

    // Prevent editing locked cells
    td.onkeydown = (e) => {
      if (!td.classList.contains("locked-cell")) return;
      if (
        ["Enter", "Tab"].includes(e.key) ||
        ((e.ctrlKey || e.metaKey) && ["+", "="].includes(e.key))
      ) {
        e.preventDefault();
      }
    };

    // Add resizer
    const isLastCell = td.cellIndex >= td.parentElement.cells.length - 1;
    if (!isLastCell && !td.querySelector(".resizer")) {
      const resizer = document.createElement("div");
      resizer.className = "resizer";
      td.style.position = "relative";
      Object.assign(resizer.style, {
        position: "absolute",
        top: "0", right: "0",
        width: "5px", height: "100%",
        cursor: "col-resize", zIndex: "10"
      });
      td.appendChild(resizer);
      resizer.addEventListener("mousedown", e => this.startColumnResize(e, td));
    }
  });
}
dateSelectionAndRefresh() {
  if (!this.table) return;

  const container = this.table.closest(".element");
  if (!container || !this.builder) return;

  const ROW = this.builder.ROW_HEIGHT;

  // 🔄 Step 1: Reset existing row heights
  Array.from(this.table.rows).forEach(row => {
    row.style.removeProperty("height");
    row.style.removeProperty("min-height");
  });

  // 📏 Step 2: Snap each row's height to nearest grid unit
  Array.from(this.table.rows).forEach(row => {
    const snapped = Math.ceil(row.offsetHeight / ROW) * ROW;
    row.style.height = `${snapped}px`;
  });

  // 🧮 Step 3: Update container's row data and height
  const totalSnapped = Math.round(this.table.offsetHeight / ROW) * ROW;
  container.dataset.rows = totalSnapped / ROW;
  container.style.height = `${totalSnapped}px`;

  // 🔁 Step 4: Rebind cell events & update selection visuals
  this.rebindTableCellEvents();
  this.updateCellSelection();

  // 📦 Step 5: Reflow the layout with correct spacing
  this.builder.pushElementsDown(container);
  requestAnimationFrame(() => this.builder.reflowContent());

  // 🛠️ Optional callback hook for table updates
  this.onTableChanged?.();

  // 🚫 Step 6: Lock editing if table hits page limit
  if (this.isTableAtPageLimit()) {
    this.lockCellEditing();
  } else {
    this.unlockCellEditing();
  }
}
resnapAndReflow() {
  if (!this.table) return;

  const ROW = this.builder.ROW_HEIGHT;
  const container = this.table.closest(".element");
  if (!container) return;

  // Resize each row based on the tallest cell content
  Array.from(this.table.rows).forEach(row => {
    let maxContentHeight = 0;

    Array.from(row.cells).forEach(cell => {
      const clone = cell.cloneNode(true);
      clone.style.cssText = `
        position: absolute;
        visibility: hidden;
        height: auto;
        width: ${cell.offsetWidth}px;
        white-space: normal;
        padding: 2px 4px;
        box-sizing: border-box;
        line-height: 1.2;
      `;
      document.body.appendChild(clone);
      maxContentHeight = Math.max(maxContentHeight, clone.scrollHeight);
      document.body.removeChild(clone);
    });

    // Prevent 0-height glitch
    if (maxContentHeight < 5) maxContentHeight = ROW;

    const snapped = Math.ceil(maxContentHeight / ROW) * ROW;

    row.style.minHeight = "0";
    row.style.height = `${snapped}px`;

    Array.from(row.cells).forEach(cell => {
      cell.style.height = `${snapped}px`;
    });
  });

  // Force layout update
  void this.table.offsetHeight;

  const tableHeight = this.table.offsetHeight;
  const snappedHeight = Math.round(tableHeight / ROW) * ROW;

  container.dataset.rows = snappedHeight / ROW;
  container.style.height = `${snappedHeight}px`;

  this.builder.pushElementsDown(container);
  this.builder.reflowContent(container.closest(".content"));
  this.builder.saveHistory?.();

  // Lock or unlock table based on page constraints
  if (this.isTableAtPageLimit()) {
    this.lockCellEditing();
  } else {
    this.unlockCellEditing();
  }
}
 handleCommand(cmd) {
  if (!this.table) return;

  if (!this.selectedCell) {
    // fallback to first cell
    this.selectedCell = this.table.querySelector("td");
    if (!this.selectedCell) return;
  }

  const rowIndex = this.selectedCell.parentElement.rowIndex;
  const cellIndex = this.selectedCell.cellIndex;

  switch (cmd) {
    case "AddRow":
      this.insertRow(rowIndex + 1);
      break;
    case "deleteRow":
      this.deleteRow(rowIndex);
      break;
    case "addColLeft":
      this.insertColumn(cellIndex);
      break;
    case "addColRight":
      this.insertColumn(cellIndex + 1);
      break;
    case "deleteCol":
      this.deleteColumn(cellIndex);
      break;
    default:
      console.warn("Unhandled table command:", cmd);
     case "mergeCells":
  this.mergeSelectedCells();
  break;
case "unmergeCells":
  this.unmergeSelectedCell();
  break;

  }
}
getCellCoordinates(targetCell) {
  const map = this.buildCellMap();

  for (let r = 0; r < map.length; r++) {
    for (let c = 0; c < map[r].length; c++) {
      if (map[r][c] === targetCell) {
        return { row: r, col: c };
      }
    }
  }

  return null;
}


getCellAt(rowIndex, colIndex) {
  if (!this.table) return null;

  const map = [];
  const rows = Array.from(this.table.rows);

  for (let r = 0; r < rows.length; r++) {
    map[r] = [];
  }

  for (let r = 0; r < rows.length; r++) {
    const row = rows[r];
    let col = 0;

    for (const cell of row.cells) {
      const rowspan = cell.rowSpan || 1;
      const colspan = cell.colSpan || 1;

      // Find the next free column slot in the map
      while (map[r][col]) col++;

      // Fill in the map with references to the current cell
      for (let i = 0; i < rowspan; i++) {
        for (let j = 0; j < colspan; j++) {
          const rr = r + i;
          const cc = col + j;
          if (!map[rr]) map[rr] = [];
          map[rr][cc] = cell;
        }
      }

      col += colspan;
    }
  }

  return map[rowIndex]?.[colIndex] || null;
}
initializeCell(cell) {
  cell.contentEditable = "true";

  // Clear any inherited or buggy state
  cell.innerHTML = ""; // More reliable than textContent for ensuring true emptiness

  // Force LTR writing direction and reset bidi
  cell.style.direction = "ltr";
  cell.style.unicodeBidi = "plaintext";
  cell.style.textAlign = "left";

  // Add a zero-width space to ensure cursor anchors properly
  // without visually affecting layout
  cell.innerHTML = "&#8203;";  // Unicode U+200B (zero-width space)

  // Rebind click-to-select event
  cell.addEventListener("mousedown", (e) => {
    if (!e.shiftKey) this.clearMultiSelection();
    this.selectCell(cell, e.shiftKey);
    e.stopPropagation();
  });
}
updateCellSelection() {
  if (!this.table || !this.selectedCell) return;

  // 🧼 Clear all previous selection states
  this.table.querySelectorAll("td").forEach(td =>
    td.classList.remove("multi-selected", "selected-cell")
  );

  // 🎯 Always highlight the most recently clicked cell
  this.selectedCell.classList.add("selected-cell");

  if (this.selectedCells.size <= 1) return;

  // 🗺️ Step 1: Build cell grid map
  const map = [];
  const rows = Array.from(this.table.rows);

  for (let r = 0; r < rows.length; r++) {
    map[r] = [];
    const row = rows[r];
    let col = 0;

    for (const cell of row.cells) {
      const rowspan = cell.rowSpan || 1;
      const colspan = cell.colSpan || 1;

      // Skip already-filled slots
      while (map[r][col]) col++;

      for (let i = 0; i < rowspan; i++) {
        for (let j = 0; j < colspan; j++) {
          const rr = r + i;
          const cc = col + j;
          if (!map[rr]) map[rr] = [];
          map[rr][cc] = cell;
        }
      }

      col += colspan;
    }
  }

  // 🧮 Step 2: Compute rectangular bounds of selection
  let top = Infinity, left = Infinity, bottom = -1, right = -1;

  for (const cell of this.selectedCells) {
    const pos = this.getCellCoordinates(cell);
    if (!pos) continue;

    const rowspan = cell.rowSpan || 1;
    const colspan = cell.colSpan || 1;

    top    = Math.min(top, pos.row);
    left   = Math.min(left, pos.col);
    bottom = Math.max(bottom, pos.row + rowspan - 1);
    right  = Math.max(right, pos.col + colspan - 1);
  }

  // 🔲 Step 3: Select all unique cells in bounding box
  const selected = new Set();

  for (let r = top; r <= bottom; r++) {
    for (let c = left; c <= right; c++) {
      const cell = map[r]?.[c];
      if (cell) selected.add(cell);
    }
  }

  // ✅ Step 4: Apply selection styles
  this.selectedCells.clear();
  selected.forEach(cell => {
    cell.classList.add("multi-selected");
    this.selectedCells.add(cell);
  });
}
buildCellMap() {
  const map = [];
  const rows = Array.from(this.table?.rows || []);

  for (let r = 0; r < rows.length; r++) {
    const row = rows[r];
    if (!map[r]) map[r] = [];

    let col = 0;

    for (const cell of row.cells) {
      // Skip filled virtual cells
      while (map[r][col]) col++;

      const rowspan = cell.rowSpan || 1;
      const colspan = cell.colSpan || 1;

      for (let i = 0; i < rowspan; i++) {
        for (let j = 0; j < colspan; j++) {
          if (!map[r + i]) map[r + i] = [];
          map[r + i][col + j] = cell;
        }
      }

      col += colspan;
    }
  }

  return map;
}

getColumnCount() {
  const map = this.builder?.buildTableGrid?.(this.table);
  if (!map || !map[0]) return 0;
  return map[0].length;
}
startColumnResize(e, td) {
  e.preventDefault();

  const table = this.table;
  const colIndex = td.cellIndex;
  const startX = e.clientX;

  const rows = Array.from(table.rows);
  const leftCells = rows.map(row => row.cells[colIndex]);
  const rightCells = rows.map(row => row.cells[colIndex + 1]);

  const leftStartWidths = leftCells.map(cell => cell.offsetWidth);
  const rightStartWidths = rightCells.map(cell => cell?.offsetWidth || 0);

  const MIN_WIDTH = 40;

  const onMouseMove = (moveEvt) => {
    const deltaX = moveEvt.clientX - startX;

    const maxDeltaLeft = leftStartWidths[0] - MIN_WIDTH;
    const maxDeltaRight = rightStartWidths[0] - MIN_WIDTH;

    // ⛔ Clamp resizing to avoid collapsing below min width
    const clampedDelta = Math.max(-maxDeltaLeft, Math.min(deltaX, maxDeltaRight));

    let newLeft = leftStartWidths[0] + clampedDelta;
    let newRight = rightStartWidths[0] - clampedDelta;

    // 🔒 Enforce total width constraint
    const totalAllowed = leftStartWidths[0] + rightStartWidths[0];
    if (newLeft + newRight > totalAllowed) {
      const scale = totalAllowed / (newLeft + newRight);
      newLeft *= scale;
      newRight *= scale;
    }

    // 🔁 Apply new widths
    for (let i = 0; i < rows.length; i++) {
      if (leftCells[i])  leftCells[i].style.width = `${newLeft}px`;
      if (rightCells[i]) rightCells[i].style.width = `${newRight}px`;
    }
  };

  const onMouseUp = () => {
    document.removeEventListener("mousemove", onMouseMove);
    document.removeEventListener("mouseup", onMouseUp);

    this.builder?.saveHistory?.();
    this.resnapAndReflow(); // ⏹️ Snap table after resize to update layout
  };

  document.addEventListener("mousemove", onMouseMove);
  document.addEventListener("mouseup", onMouseUp);
}
insertRow(atIndex) {
  const rows = this.table.rows;
  if (!rows.length) return;

  const maxRows = 50;
  const currentRows = rows.length;
  if (currentRows >= maxRows) {
    console.warn("🚫 Max 50 rows reached.");
    return;
  }

  const insertAt = Math.max(0, Math.min(atIndex, currentRows));
  const refRow = rows[insertAt - 1] || rows[insertAt] || rows[0];
  const numCols = refRow?.cells.length || 1;

  const newRow = this.table.insertRow(insertAt);

  for (let c = 0; c < numCols; c++) {
    const refCell = refRow?.cells[c];
    const newCell = newRow.insertCell();

    this.initializeCell(newCell); // ✅ editable, selectable

    if (refCell) {
      const computed = getComputedStyle(refCell);
      newCell.style.width         = refCell.offsetWidth + "px";
      newCell.style.padding       = computed.padding;
      newCell.style.border        = computed.border;
      newCell.style.verticalAlign = computed.verticalAlign;
      newCell.style.lineHeight    = computed.lineHeight;
      newCell.style.boxSizing     = computed.boxSizing;
      newCell.style.overflowWrap  = computed.overflowWrap;
      newCell.style.wordBreak     = computed.wordBreak;
    } else {
      newCell.style.width = "100px";
    }
  }

  const totalCols = rows[0].cells.length;
  const pct = (100 / totalCols) + "%";
  for (const row of rows) {
    Array.from(row.cells).forEach(cell => {
      cell.style.width = pct;
    });
  }

  this.table.style.tableLayout = "fixed";
  this.rebindTableCellEvents();
  this.updateSelectionAndRefresh();
}
deleteRow(rowIndex) {
  if (!this.table) return;

  const rows = Array.from(this.table.rows);
  if (rows.length <= 1) return; // 🚫 Prevent deleting the last row

  // ✅ If no selected cell, delete the last row
  if (rowIndex == null || rowIndex < 0) {
    if (!this.selectedCell) {
      rowIndex = rows.length - 1;
    } else {
      rowIndex = this.selectedCell.parentElement?.rowIndex ?? rows.length - 1;
    }
  }

  const rowToDelete = rows[rowIndex];
  if (!rowToDelete) return;

  // 🔁 Adjust rowSpan of cells above the row being deleted
  for (let i = 0; i < rowToDelete.cells.length; i++) {
    const cell = rowToDelete.cells[i];
    const colIndex = cell.cellIndex;

    const aboveCell = this.getCellAt(rowIndex - 1, colIndex);
    if (aboveCell && aboveCell.rowSpan > 1) {
      aboveCell.rowSpan -= 1;
    }
  }

  this.table.deleteRow(rowIndex);

  // 🧭 Reset selected cell to a nearby valid one
  const fallbackRow = this.table.rows[Math.max(0, rowIndex - 1)];
  if (fallbackRow && fallbackRow.cells.length > 0) {
    this.selectedCell = fallbackRow.cells[0];
    this.selectedCells.clear();
    this.selectedCells.add(this.selectedCell);
  } else {
    this.selectedCell = null;
    this.selectedCells.clear();
  }

  this.updateCellSelection();
  this.resnapAndReflow();
  this.builder.pushElementsDown(this.table.closest(".element"));
  this.builder.reflowContent(this.table.closest(".content"));
  this.builder.saveHistory?.();
}
insertColumn(atIndex) {
  const rows = this.table.rows;
  if (!rows.length) return;

  const maxCols = 7;
  const currentCols = rows[0].cells.length;
  if (currentCols >= maxCols) {
    console.warn("🚫 Max 7 columns reached.");
    return;
  }

  const index = Math.max(0, Math.min(atIndex, currentCols));

  for (const row of rows) {
    const ref = row.cells[index - 1] || row.cells[index] || row.cells[0];
    const newCell = row.insertCell(index);  // ✅ insert at correct index

    this.initializeCell(newCell); // ✅ use standardized cell init

    if (ref) {
      const computed = getComputedStyle(ref);
      newCell.style.width         = ref.offsetWidth + "px";
      newCell.style.padding       = computed.padding;
      newCell.style.border        = computed.border;
      newCell.style.verticalAlign = computed.verticalAlign;
      newCell.style.lineHeight    = computed.lineHeight;
      newCell.style.boxSizing     = computed.boxSizing;
      newCell.style.overflowWrap  = computed.overflowWrap;
      newCell.style.wordBreak     = computed.wordBreak;
    } else {
      newCell.style.width = "100px";
    }
  }

  const total = this.table.rows[0].cells.length;
  const pct = (100 / total) + "%";
  for (const row of rows) {
    Array.from(row.cells).forEach(cell => {
      cell.style.width = pct;
    });
  }

  this.table.style.tableLayout = "fixed";
  this.rebindTableCellEvents();
  this.updateSelectionAndRefresh();
}
deleteColumn(passedIndex = null) {
  const rows = this.table?.rows;
  if (!rows || rows.length === 0) return;

  const colTotal = rows[0]?.cells.length || 0;

  if (colTotal <= 1) {
    console.warn("🚫 Only 1 column left — can’t delete.");
    return;
  }

  // Get the column index to delete
  let index = passedIndex;
  if (index == null || isNaN(index)) {
    index = this.selectedCell
      ? this.selectedCell.cellIndex
      : colTotal - 1;
  }

  index = Math.max(0, Math.min(index, colTotal - 1));

  // Delete the column at that index
  for (const row of rows) {
    if (index < row.cells.length) {
      row.deleteCell(index);
    }
  }

  this.selectedCell = null;
  this.selectedCells.clear();

  // Normalize column widths
  const newCols = rows[0]?.cells.length || 1;
  const pctWidth = (100 / newCols) + "%";

  for (const row of rows) {
    Array.from(row.cells).forEach(cell => {
      cell.style.width = pctWidth;
    });

    // Ensure all rows have the same number of columns
    while (row.cells.length < newCols) {
      const td = document.createElement("td");
      td.setAttribute("contenteditable", "true");
      td.innerHTML = "";
      td.style.width = pctWidth;
      td.style.direction = "ltr";
      td.style.unicodeBidi = "plaintext";
      td.style.textAlign = "left";
      td.style.padding = "2px 4px";
      row.appendChild(td);
    }
  }

  this.rebindTableCellEvents();
  this.table.style.tableLayout = "fixed";

  // Layout & history
  this.resnapAndReflow();
  this.builder.pushElementsDown(this.table.closest(".element"));
  this.builder.reflowContent(this.table.closest(".content"));
  this.builder.saveHistory?.();
}
mergeSelectedCells() {
  if (!this.table || this.selectedCells.size < 2) return;

  const map = this.buildCellMap();
  const coords = Array.from(this.selectedCells).map(cell => this.getCellCoordinates(cell));
  const rows = coords.map(c => c.row);
  const cols = coords.map(c => c.col);

  const minRow = Math.min(...rows);
  const maxRow = Math.max(...rows);
  const minCol = Math.min(...cols);
  const maxCol = Math.max(...cols);

  // Check if all cells within the range are selected
  for (let r = minRow; r <= maxRow; r++) {
    for (let c = minCol; c <= maxCol; c++) {
      const cell = map[r][c];
      if (!this.selectedCells.has(cell)) {
        alert("Merge failed: all cells in the rectangle must be selected.");
        return;
      }
    }
  }

  const baseCell = map[minRow][minCol];
  baseCell.rowSpan = maxRow - minRow + 1;
  baseCell.colSpan = maxCol - minCol + 1;

  // Remove all other cells from the DOM
  for (let r = minRow; r <= maxRow; r++) {
    for (let c = minCol; c <= maxCol; c++) {
      const cell = map[r][c];
      if (cell !== baseCell && cell.parentElement) {
        cell.parentElement.removeChild(cell);
      }
    }
  }

  this.selectedCells.clear();
  this.selectedCells.add(baseCell);
  this.selectedCell = baseCell;

  this.updateCellSelection();
  this.resnapAndReflow?.();
}
unmergeSelectedCell() {
  if (!this.table || !this.selectedCell) return;

  const cell = this.selectedCell;
  const coord = this.getCellCoordinates(cell);
  const map = this.buildCellMap();

  if (!coord) return;

  const { row, col } = coord;
  const rowSpan = cell.rowSpan || 1;
  const colSpan = cell.colSpan || 1;

  if (rowSpan === 1 && colSpan === 1) return;

  // Reset main cell
  cell.rowSpan = 1;
  cell.colSpan = 1;

  // Add missing cells around it
  for (let r = 0; r < rowSpan; r++) {
    const tr = this.table.rows[row + r];

    let logicalCol = col;
    for (let c = 0; c < colSpan; c++) {
      const mapCell = map[row + r]?.[col + c];

      if (!mapCell || mapCell === cell) {
        // Only insert a new td if this spot is now empty
        const newTd = document.createElement("td");
        this.initializeCell?.(newTd);

        // Find correct insertion index (account for spans)
        let insertAt = 0;
        let realCol = 0;

        for (const td of tr.cells) {
          const span = td.colSpan || 1;
          if (realCol >= col + c) break;
          realCol += span;
          insertAt++;
        }

        tr.insertBefore(newTd, tr.cells[insertAt] || null);
      }
    }
  }

  this.updateCellSelection?.();
  this.rebindTableCellEvents?.();
  this.builder?.reflowTable?.(this.table);
  this.builder?.saveHistory?.();
}

}

