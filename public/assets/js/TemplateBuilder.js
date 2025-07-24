"use strict";
class TemplateBuilder {
  ROW_HEIGHT = 25;
  DROP_MARGIN = 20;
  SIZES = { A4: { w: 794, h: 1123 }, Letter: { w: 816, h: 1056 }, Legal: { w: 816, h: 1344 } };
  ROMAN_MAP = [
    [1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'], [100, 'C'],
    [90, 'XC'], [50, 'L'], [40, 'XL'], [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I']
  ];
constructor() {
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
  // Check for table-block (or element-table) click
let clickedElement =
  e.target.closest(".element, .label-block, .paragraph-block, .table-block");

if (clickedElement && this.workspace.contains(clickedElement)) {
  this.selectElement(clickedElement);

  // If it's a table-block, also notify the table toolbar
  if (clickedElement.classList.contains("table-block")) {
    this.tableToolbar?.setTable(clickedElement.querySelector("table"));
  }

} else {
  this.selectElement(null);
  this.tableToolbar?.clearSelection(); // Optional: clears table highlighting
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
      }
    }
  });

this.fontSizeSel.addEventListener("change", () => {
  const newSize = parseInt(this.fontSizeSel.value, 10) || 12;
  this.defaultFontSize = newSize;

  if (!this.selectedElement) return;

  const body = this.getEditableBody(this.selectedElement);

  if (body.classList.contains("header-title") ||
      body.classList.contains("header-subtitle")) return;

  body.style.fontSize = `${newSize}px`;

  const el = this.selectedElement;
  const ROW = this.ROW_HEIGHT;

  const forceResize = () => {
    const scrollH = body.scrollHeight;
    const rows     = Math.max(3, Math.ceil(scrollH / ROW)); 
    el.dataset.rows = rows;
    el.style.height = rows * ROW + "px";
    this.reflowContent(el.closest(".content"));
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
        const rawType = e.dataTransfer.getData("text/plain")
               || e.dataTransfer.getData("type");

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
    this.handleDrop(new Proxy(e, {
      get: (obj, prop) => prop === "currentTarget" ? targetContent : obj[prop]
    }));
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
    pg.style.width = w + "px";
    pg.style.height = h + "px";

    const first = this.workspace.querySelector(".page");
    const title = first?.querySelector(".header-title")?.innerText || "Enter Syllabus Title";
    const sub = first?.querySelector(".header-subtitle")?.innerText || "Enter Subtitle";
    const logoSrc = this.logoLoaded ? this.currentLogoSrc :
      "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='0.8' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='3' width='18' height='18' rx='2' ry='2'/><path d='M12 8v8m-4-4h8'/></svg>";
    const hasImg = this.logoLoaded ? "has-image" : "";
    const pgNum = this.workspace.children.length + 1;

    pg.innerHTML = `
      <div class="header">
        <div class="header-logo ${hasImg}" contenteditable="false">
          <input type="file" accept="image/*" style="display:none;" />
          <img src="${logoSrc}" alt="Logo">
        </div>
        <div class="header-texts">
          <div class="header-title" contenteditable="true">${title}</div>
          <div class="header-subtitle" contenteditable="true">${sub}</div>
        </div>
      </div>
      <div class="content"></div>
      <div class="footer d-flex justify-content-between">
        <div class="footer-left" contenteditable="true">${this.globalFooterText}</div>
        <div class="footer-right">Page ${pgNum}</div>
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

  
  handleDrop(e) {
  e.preventDefault();

  const type = e.dataTransfer.getData("text/plain") || e.dataTransfer.getData("type");
  if (!type) return;

  const isLabel     = type === "label";
  const isTextField = type === "text-field"; 
  const isText3     = type === "text-3";
  const isParagraph = type === "paragraph";
  const isTable     = type === "table";
  const isSignature = type === "signature";

  const el = document.createElement("div");
  el.dataset.type = type;
  el.classList.add("element");

  if (isLabel || isTextField) {
    el.classList.add("label-block");
   if (isTextField) el.classList.add("text-field");
   }
  if (isText3) el.classList.add("text-3");
  if (isParagraph) el.classList.add("paragraph-block");
  if (isTable) el.classList.add("table-block");
  if (isSignature) el.classList.add("signature-block");

  const body = document.createElement(
    isTable || isSignature ? "table" : "div"
  );
  body.className = "element-body";
  body.contentEditable = !isTable && !isSignature;

  if (isLabel || isTextField) {
  body.textContent = "Label text";
  body.style.whiteSpace = "nowrap";

  // Apply selected font and size
  const font = this.fontFamilySel?.value || this.defaultFontFamily;
  const size = this.fontSizeSel?.value || this.defaultFontSize;
  body.style.fontFamily = font;
  body.style.fontSize = `${size}px`;

  el.appendChild(body);
  el.style.height = `${this.ROW_HEIGHT}px`;
  el.dataset.rows = 1;
} else if (isText3 || isParagraph) {
  body.textContent = isText3 ? "Text block" : "Paragraph text";

  const font = this.fontFamilySel?.value || this.defaultFontFamily;
  const size = this.fontSizeSel?.value || this.defaultFontSize;
  body.style.fontFamily = font;
  body.style.fontSize = `${size}px`;

  el.appendChild(body);
  el.dataset.rows = isText3 ? 3 : 1;
  el.style.height = `${parseInt(el.dataset.rows) * this.ROW_HEIGHT}px`;
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

} else if (isSignature) {
  el.classList.add("signature-block");
  body.innerHTML = ""; // Clear in case reused

  const table = document.createElement("table");
  table.className = "signature-table";
  const row = document.createElement("tr");

  for (let i = 0; i < 4; i++) {
    const cell = document.createElement("td");
    cell.className = "signature-cell";

    // --- Image container ---
    const imgWrapper = document.createElement("div");
    imgWrapper.className = "signature-img-wrapper";

    const img = document.createElement("img");
    img.className = "signature-img";
    img.style.display = "none";
    imgWrapper.appendChild(img);

    // Upload button inside wrapper
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
          imgWrapper.classList.add("filled"); // ✅ Remove dashed border
        };
        reader.readAsDataURL(input.files[0]);
      }
    };

    imgWrapper.appendChild(btn); // Add upload button inside image wrapper

    // Signature line and fields
    const line = document.createElement("div");
    line.className = "signature-line";

    const name = document.createElement("div");
    name.contentEditable = true;
    name.textContent = "Name";
    name.className = "signature-label";

    const date = document.createElement("div");
    date.contentEditable = true;
    date.textContent = "Date";
    date.className = "signature-label";

    const role = document.createElement("div");
    role.contentEditable = true;
    role.textContent = "Role";
    role.className = "signature-label";

    // Prevent overflow and newlines
    [name, date, role].forEach(label => {
      label.addEventListener("keydown", e => {
        if (e.key === "Enter") e.preventDefault();

        const range = document.createRange();
        range.selectNodeContents(label);
        const rect = range.getBoundingClientRect();
        const parentRect = label.parentElement.getBoundingClientRect();
        const buffer = 8;

        if (
          rect.width > parentRect.width - buffer &&
          !["Backspace", "Delete", "ArrowLeft", "ArrowRight"].includes(e.key)
        ) {
          e.preventDefault();
          label.style.border = "1px solid red";
          setTimeout(() => (label.style.border = "none"), 200);
        }
      });

      label.addEventListener("paste", e => {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData("text").trim();
        document.execCommand("insertText", false, pastedText);
      });
    });

    // Append everything to the cell
    cell.appendChild(imgWrapper);
    cell.appendChild(input); // hidden input
    cell.appendChild(line);
    cell.appendChild(name);
    cell.appendChild(date);
    cell.appendChild(role);

    row.appendChild(cell);
  }

  table.appendChild(row);
  body.appendChild(table);
  el.appendChild(body);

  // Temporary sizing using double RAF for accuracy
  document.body.appendChild(el);
  el.style.position = "absolute";
  el.style.visibility = "hidden";

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      const actualHeight = el.offsetHeight;
      const requiredRows = Math.ceil(actualHeight / this.ROW_HEIGHT);
      el.dataset.rows = requiredRows;
      el.style.height = requiredRows * this.ROW_HEIGHT + "px";

      el.style.position = "";
      el.style.visibility = "";
      document.body.removeChild(el);
    });
  });
}


  const grip = document.createElement("div");
  grip.className = "drag-handle";
  grip.innerHTML = "<i class='bi bi-grip-vertical'></i>";
  el.prepend(grip);

  const content = e.currentTarget;
  const insertY = e.offsetY;
  el.style.top = `${insertY}px`;
  el.style.left = "32px";
  el.style.width = "calc(100% - 32px)";
  el.style.boxSizing = "border-box";
  el.style.position = "absolute";

  // Append to content
  this.placeElement(content, el, insertY);
  if (isText3 || isParagraph) {
  this.setupTextArea(el);

  // Force initial resize to fit current font + content
  const resizeEvt = new Event("input");
  el.querySelector(".element-body").dispatchEvent(resizeEvt);
}


  // Init
  this.makeMovable(el);
  this.addRemoveButton(el);
  this.selectElement(el);

  if (isLabel || isTextField) {
    this.setupLabelInputRestrictions(el);
    this.applyLabelSuggestion(el);
  } else if (isText3 || isParagraph) {
    this.setupTextArea(el);
  }

  this.saveHistory();
}


placeElement(startContent, el, y) {
  /* ------------------------------------------------------------------
     1️⃣  If element is a table‑block or signature‑block AND not yet
         measured, measure it off‑screen, snap height to grid rows,
         then recurse so the placement below uses correct rows.
  ------------------------------------------------------------------ */
  if (!el.dataset.measured && (el.classList.contains("table-block") ||
                               el.classList.contains("signature-block"))) {
    const ROW = this.ROW_HEIGHT;

    // Put it off‑screen temporarily so offsetHeight is accurate
    document.body.appendChild(el);
    el.style.position = "absolute";
    el.style.visibility = "hidden";

    const actual = el.offsetHeight;                    // true rendered px
    const snappedPx = Math.ceil(actual / ROW) * ROW;   // snap up to grid
    const rows = snappedPx / ROW;

    el.dataset.rows = rows;
    el.style.height = `${snappedPx}px`;

    // mark as measured so we don't re‑measure next time
    el.dataset.measured = "true";

    // clean up temporary styles and recurse for real placement
    el.style.position = "";
    el.style.visibility = "";
    return this.placeElement(startContent, el, y);
  }

  /* ------------------------------------------------------------------
     2️⃣  NORMAL placement logic (unchanged except we now have the
         correct dataset.rows & snapped height before we start).
  ------------------------------------------------------------------ */
  const visited = new Set();
  let content = startContent;
  let currentY = y;

  while (true) {
    if (visited.has(content)) return;
    visited.add(content);

    const contentHeight = content.clientHeight;
    const usableHeight  = contentHeight - 2 * this.DROP_MARGIN;

    const initialRows = parseInt(el.dataset.rows || 1, 10);
    const maxRows     = Math.floor(usableHeight / this.ROW_HEIGHT);
    const insertRow   = Math.max(
                          0,
                          Math.min(Math.floor(currentY / this.ROW_HEIGHT),
                                   maxRows - initialRows)
                        );

    const topOffset = this.DROP_MARGIN + insertRow * this.ROW_HEIGHT;
    el.style.top = `${topOffset}px`;

    if (!content.contains(el)) content.appendChild(el);
    this.addRemoveButton(el);

    /* ---------- push blocks below down if overlapping ---------- */
    const blocks = [...content.querySelectorAll(".element, .label-block, .paragraph-block")]
                    .filter(b => b !== el)
                    .sort((a, b) => parseInt(a.style.top || 0) - parseInt(b.style.top || 0));

    let cursor = topOffset + initialRows * this.ROW_HEIGHT;

    for (const blk of blocks) {
      const blkTop     = parseInt(blk.style.top || 0, 10);
      const blkHeight  = Math.ceil(blk.offsetHeight / this.ROW_HEIGHT) * this.ROW_HEIGHT;
      const blkBottom  = blkTop + blkHeight;

      if (blkTop < cursor && blkBottom > topOffset) {
        blk.style.top = `${cursor}px`;
        cursor += blkHeight;
      }

      const usable = content.clientHeight - this.DROP_MARGIN;
      const newBottom = parseInt(blk.style.top, 10) + blkHeight;
      if (newBottom > usable) {
        content.removeChild(blk);
        this.placeElement(this.getNextContent(content), blk, 0);
      }
    }
    break; // finished for this content area
  }
}


  addRemoveButton(el) {
  const btn = document.createElement("button");
  btn.className = "remove-btn";
  btn.innerHTML = "&times;";
  btn.title = "Remove Element";
  btn.addEventListener("click", (e) => {
    e.stopPropagation();
    el.remove();
    this.cleanupPages();
  });
  el.appendChild(btn);
}


  makeMovable(el) {
    const grip = el.querySelector(".drag-handle");
    if (!grip) return;

    grip.addEventListener("mousedown", startEvt => {
      this.selectElement(el);

      startEvt.preventDefault();
      document.body.style.userSelect = "none";

      const offsetInside = startEvt.clientY - el.getBoundingClientRect().top;

      const onMove = mv => {
        const targetContent = document.elementFromPoint(mv.clientX, mv.clientY)?.closest(".content");
        if (!targetContent) return;

        const contentRect = targetContent.getBoundingClientRect();
        const offsetY = mv.clientY - contentRect.top - offsetInside;
        const usableH = targetContent.clientHeight - 2 * this.DROP_MARGIN;
        const rows = parseInt(el.dataset.rows || 1);
        const maxRows = Math.floor(usableH / this.ROW_HEIGHT);
        const insertRow = Math.max(0, Math.min(Math.floor(offsetY / this.ROW_HEIGHT), maxRows - rows));
        const top = this.DROP_MARGIN + insertRow * this.ROW_HEIGHT;

        Object.assign(this.ghostLine.style, {
          top: contentRect.top + top + "px",
          left: contentRect.left + "px",
          width: contentRect.width + "px",
          display: "block"
        });

        this.ghostTarget = { content: targetContent, offsetY };
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
  
alignSelectedElement(cmd) {
  if (!this.selectedElement) return;

  const target = this.getEditableBody(this.selectedElement);
  if (!target) return;

  let alignment = "left";
  if (cmd === "justifyCenter") alignment = "center";
  else if (cmd === "justifyRight") alignment = "right";

  target.style.textAlign = alignment;
  document.querySelectorAll('[data-cmd^="justify"]').forEach(btn => {
    btn.classList.remove('active');
  });
  const btn = document.querySelector(`[data-cmd="${cmd}"]`);
  if (btn) btn.classList.add('active');
}

reflowContent(content) {
  const ROW = this.ROW_HEIGHT;
  const DROP_MARGIN = this.DROP_MARGIN;

  const blocks = [...content.querySelectorAll(".element, .label-block, .paragraph-block")]
    .filter(el => !el.classList.contains("dragging"))
    .sort((a, b) => a.offsetTop - b.offsetTop); // ✅ Use offsetTop instead of style.top

  for (let i = 0; i < blocks.length; i++) {
    const blk = blocks[i];
    let blkTop = blk.offsetTop;

    // Snap top
    if (blkTop % ROW !== 0) {
      blkTop = Math.round(blkTop / ROW) * ROW;
      blk.style.top = `${blkTop}px`;
    }

    const blkIsLabel = blk.classList.contains("label-block");
    let snappedHeight = Math.ceil(blk.offsetHeight / ROW) * ROW;
    if (blkIsLabel && snappedHeight < ROW) snappedHeight = ROW;

    if (blk.offsetHeight !== snappedHeight) {
      blk.style.height = `${snappedHeight}px`;
    }

    const blkBottom = blkTop + snappedHeight;
    let cursor = blkBottom;

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

      const usableHeight = content.clientHeight - DROP_MARGIN;
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


toggleStyleForSelected(cmd) {
  if (!this.selectedElement) return;

  const target = this.getEditableBody(this.selectedElement);
  if (!target) return;

  const style = {
    bold:       ["fontWeight", "bold",       "normal"],
    italic:     ["fontStyle",  "italic",     "normal"],
    underline:  ["textDecoration", "underline", "none"]
  }[cmd];

  if (!style) return;

  const [prop, onVal, offVal] = style;
  const isOn = (prop === "textDecoration")
    ? target.style[prop].includes(onVal)  
    : target.style[prop] === onVal;

  target.style[prop] = isOn ? offVal : onVal;

  this.selectElement(this.selectedElement);
}

  getNextContent(content) {
    const nextPage = content.closest(".page").nextElementSibling;
    return nextPage?.querySelector(".content") || this.createPage();
  }

selectElement(el) {
  if (this.selectedElement) {
    this.selectedElement.classList.remove("selected");
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

  const body = el.querySelector(".element-body, .header-title, .header-subtitle, .footer-left") || el;
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

    // Prevent adding duplicate click listeners
    if (!table._toolbarBound) {
      table.addEventListener("click", e => {
        if (e.target.tagName === "TD") {
          this.tableToolbar.showForTable(table, e.target);
        }
      });
      table._toolbarBound = true; // mark as bound
    }

    // Show toolbar now
    const firstCell = table.querySelector("td");
    this.tableToolbar.showForTable(table, firstCell);
  } else {
    this.tableToolbar.hide(
      
    );
  }
}

  updatePageNumbers() {
    this.workspace.querySelectorAll(".page .footer-right").forEach((node, i) => {
      node.textContent = `Page ${i + 1}`;
    });
  }

 getEditableBody(el) {
    return el.querySelector(".element-body, .header-title, .header-subtitle, .footer-left") || el;
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

    // Shrink if necessary
    if (currentRows > minRows) el.style.height = 'auto';

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
      // Snap even if no row count changed
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

  body.addEventListener("input", resizeToContent);
  body.addEventListener("paste", e => this.sanitizePaste(e));

  requestAnimationFrame(() => {
    this.setupGripResize(el, body);
  });
  body.addEventListener("input", () => this.saveHistory());

}


restoreCursor(body) {
  const range = document.createRange();
  const sel = window.getSelection();
  range.selectNodeContents(body);
  range.collapse(false);
  sel.removeAllRanges();
  sel.addRange(range);
}

maxAllowedHeight(el) {
  const content = el.closest(".content");
  const elementTop = parseInt(el.style.top || 0);
  return content.offsetHeight - this.DROP_MARGIN - elementTop;
}

sanitizePaste(e) {
  e.preventDefault();
  const text = (e.clipboardData || window.clipboardData).getData("text").replace(/[\r\n]+/g, " ");
  document.execCommand("insertText", false, text);
}
saveHistory() {
  const state = this.workspace.innerHTML;
  const last = this.undoStack[this.undoStack.length - 1];

  if (state === last) return;

  this.undoStack.push(state);
  if (this.undoStack.length > this.maxHistory) this.undoStack.shift();

  this.redoStack = [];
}
undo() {
  if (this.undoStack.length <= 1) return;

  const current = this.undoStack.pop();
  this.redoStack.push(current);

  const prev = this.undoStack[this.undoStack.length - 1];
  if (prev) {
    this.workspace.innerHTML = prev;
    this.rebindWorkspace();
  }
}

redo() {
  if (this.redoStack.length === 0) return;

  const next = this.redoStack.pop();
  if (next) {
    this.undoStack.push(next);
    this.workspace.innerHTML = next;
    this.rebindWorkspace(); 
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
  all.sort((a, b) => parseInt(a.style.top || 0, 10) - parseInt(b.style.top || 0, 10));

  for (let i = 0; i < all.length; i++) {
    const el = all[i];
    const elTop = parseInt(el.style.top || 0, 10);
    const elHeight = parseInt(el.style.height || 0, 10);
    const elBottom = elTop + elHeight;

    if (elTop < bottom && elBottom > top) {
      const newTop = Math.ceil(bottom / ROW) * ROW;

      if (newTop + elHeight > usableHeight) {
        content.removeChild(el);
        this.placeElement(this.getNextContent(content), el, 0);
        all.splice(i, 1);
        i--;
      } else {
        el.style.top = `${newTop}px`;
        bottom = newTop + elHeight;
      }
    } else {
      bottom = Math.max(bottom, elBottom);
    }
  }

  const finalBottom = parseInt(sourceEl.style.top || 0, 10) + parseInt(sourceEl.style.height || 0, 10);
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


setupGripResize(el, body) {
  const grip = document.createElement("div");
  grip.className = "resize-handle";
  el.appendChild(grip);

  grip.addEventListener("mousedown", startEvt => {
    startEvt.preventDefault();

    const startY = startEvt.clientY;
    const startHeight = el.offsetHeight;
    const scrollHeightAtStart = body.scrollHeight;

    const onMove = mv => {
      const delta = mv.clientY - startY;
      let newHeight = startHeight + delta;

      const maxH = this.maxAllowedHeight(el);
      const minH = scrollHeightAtStart;

      if (newHeight < minH) {
        newHeight = Math.ceil(minH / this.ROW_HEIGHT) * this.ROW_HEIGHT;
      }
      if (newHeight > maxH) {
        newHeight = Math.floor(maxH / this.ROW_HEIGHT) * this.ROW_HEIGHT;
      }

      const rows = Math.round(newHeight / this.ROW_HEIGHT);
      el.dataset.rows = rows;
      el.style.height = `${newHeight}px`;
      body.style.height = "100%";

      const content = el.closest(".content");
      this.reflowContent(content);
    };

    const onUp = () => {
      document.removeEventListener("mousemove", onMove);
      document.removeEventListener("mouseup", onUp);
    };

    document.addEventListener("mousemove", onMove);
    document.addEventListener("mouseup", onUp);
  });
}

refreshElementSize(el) {
  const ROW = this.ROW_HEIGHT;
  const snapped = Math.ceil(el.offsetHeight / ROW);
  el.dataset.rows = snapped;
  el.style.height = `${snapped * ROW}px`;
}

setupLabelInputRestrictions(labelElement) {
  const body = labelElement.querySelector(".element-body");
  if (!body) return;

  let prevText = body.innerText;

  body.addEventListener("keydown", e => {
    if (e.key === "Enter") e.preventDefault();
  });

  body.addEventListener("paste", e => this.sanitizePaste(e));

  body.addEventListener("input", () => {
    body.style.whiteSpace = "nowrap";

    if (body.scrollWidth > body.clientWidth) {
      body.innerText = prevText;
      this.restoreCursor(body);
    } else {
      prevText = body.innerText;
    }

    body.style.whiteSpace = "";
  });
}

  applyLabelSuggestion(newLabel) {
    const labels = [...newLabel.parentElement.querySelectorAll(".label-block")]
      .filter(l => l !== newLabel)
      .sort((a, b) => parseInt(a.style.top || 0) - parseInt(b.style.top || 0));
    const prev = labels.reverse().find(l => parseInt(l.style.top) < parseInt(newLabel.style.top));
    if (!prev) return;

    const pat = this.parsePattern(prev.querySelector(".element-body")?.innerText?.trim() || "");
    if (pat.type === "none") return;

    const next = this.nextPatternValue(pat);
    const body = newLabel.querySelector(".element-body");
    if (body) body.innerText = this.patternToString({ ...pat, value: next });
  }

  parsePattern(text) {
    const trimmed = text.trim();
    const match = trimmed.match(/^([IVXLCDM]+\.|\d+\.|[A-Z]\.|[-•])\s?/i);
    if (!match) return { type: "none" };

    const prefix = match[1];

    if (/^\d+\.$/.test(prefix)) {
      return { type: "arabic", value: parseInt(prefix) };
    }
    if (/^[IVXLCDM]+\.$/i.test(prefix)) {
      return { type: "roman", value: this.romanToInt(prefix.replace(".", "")) };
    }
    if (/^[A-Z]\.$/.test(prefix)) {
      return { type: "alpha", value: prefix.charCodeAt(0) - 64 };
    }
    if (/^[-•]$/.test(prefix)) {
      return { type: "bullet", value: "•" };
    }

    return { type: "none" };
  }

  nextPatternValue(p) {
    return p.type === "bullet" ? "•" : p.value + 1;
  }

  patternToString(p) {
    switch (p.type) {
      case "arabic": return p.value + ".";
      case "roman": return this.intToRoman(p.value) + ".";
      case "alpha": return String.fromCharCode(64 + p.value) + ".";
      case "bullet": return "•";
    }
  }

  intToRoman(num) {
    let res = "";
    for (const [v, s] of this.ROMAN_MAP) {
      while (num >= v) {
        res += s;
        num -= v;
      }
    }
    return res;
  }

  romanToInt(str) {
    let i = 0, val = 0;
    const map = { I:1,V:5,X:10,L:50,C:100,D:500,M:1000 };
    while (i < str.length) {
      const cur = map[str[i].toUpperCase()] || 0;
      const next = map[str[i+1]?.toUpperCase()] || 0;
      if (cur < next) {
        val += next - cur;
        i += 2;
      } else {
        val += cur;
        i += 1;
      }
    }
    return val;
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
    this.onTableChanged = () => {};

    this.table = null;
    this.selectedCell = null;
    this.selectedCells = new Set();

    this.toolbarEl.querySelectorAll("[data-table-cmd]").forEach(btn => {
      const cmd = btn.getAttribute("data-table-cmd");
      btn.addEventListener("click", () => this.handleCommand(cmd));
    });

    this.resizeObserver = new ResizeObserver(() => {
      this.resnapAndReflow();
    });
  }

  showForTable(tableEl, cellEl = null) {
    if (!tableEl) return;

    this.table = tableEl;
    this.selectedCell = cellEl || tableEl.querySelector("td");

    this.toolbarEl.classList.remove("d-none");
    this.workspace.classList.add("table-toolbar-visible");

    this.rebindTableCellEvents();
    this.updateCellSelection();

    this.resizeObserver.disconnect();
    this.resizeObserver.observe(this.table);

    // 🔒 Lock or unlock based on page limit
    if (this.isTableAtPageLimit()) {
      this.lockCellEditing();
    } else {
      this.unlockCellEditing();
    }
  }

 hide() {
  this.toolbarEl.classList.add("d-none");
  this.workspace.classList.remove("table-toolbar-visible");

  // 🧼 Clear any lingering multi-selected cells
  if (this.table) {
    this.table.querySelectorAll("td").forEach(td => {
      td.classList.remove("multi-selected");
      td.classList.remove("selected-cell");
    });
  }

  this.table = null;
  this.selectedCell = null;
  this.selectedCells.clear();
  this.resizeObserver.disconnect();
}
updateCellSelection() {
  if (!this.table || !this.selectedCell) return;

  const allCells = Array.from(this.table.querySelectorAll("td"));
  allCells.forEach(td => td.classList.remove("multi-selected", "selected-cell"));

  // Always highlight the most recently clicked cell
  this.selectedCell.classList.add("selected-cell");

  if (this.selectedCells.size <= 1) return;

  // ---- Map cell placement to grid ----
  const map = [];
  const rowCount = this.table.rows.length;

  for (let r = 0; r < rowCount; r++) map[r] = [];

  for (let r = 0; r < rowCount; r++) {
    const row = this.table.rows[r];
    let col = 0;
    for (let c = 0; c < row.cells.length; c++) {
      const cell = row.cells[c];
      const rowspan = cell.rowSpan || 1;
      const colspan = cell.colSpan || 1;

      // Skip over filled positions
      while (map[r][col]) col++;

      for (let i = 0; i < rowspan; i++) {
        for (let j = 0; j < colspan; j++) {
          map[r + i][col + j] = cell;
        }
      }

      col += colspan;
    }
  }

  // ---- Determine selection rectangle ----
  let top = Infinity, left = Infinity, bottom = -1, right = -1;
  for (const cell of this.selectedCells) {
    const pos = this.getCellCoordinates(cell);
    if (!pos) continue;

    const rowspan = cell.rowSpan || 1;
    const colspan = cell.colSpan || 1;

    top = Math.min(top, pos.row);
    left = Math.min(left, pos.col);
    bottom = Math.max(bottom, pos.row + rowspan - 1);
    right = Math.max(right, pos.col + colspan - 1);
  }

  // ---- Select all unique cells in the rectangle ----
  const selected = new Set();

  for (let r = top; r <= bottom; r++) {
    for (let c = left; c <= right; c++) {
      const cell = map[r]?.[c];
      if (cell) selected.add(cell);
    }
  }

  // Clear and apply correct selection
  this.selectedCells.clear();
  selected.forEach(cell => {
    cell.classList.add("multi-selected");
    this.selectedCells.add(cell);
  });
}


getCellCoordinates(targetCell) {
  for (let r = 0; r < this.table.rows.length; r++) {
    let col = 0;
    const row = this.table.rows[r];
    for (let c = 0; c < row.cells.length; c++) {
      const cell = row.cells[c];
      const rowspan = cell.rowSpan || 1;
      const colspan = cell.colSpan || 1;

      if (cell === targetCell) {
        return { row: r, col };
      }

      col += colspan;
    }
  }
  return null;
}

getCellAt(rowIndex, colIndex) {
  const map = [];
  const rowCount = this.table.rows.length;

  for (let i = 0; i < rowCount; i++) map[i] = [];

  for (let r = 0; r < rowCount; r++) {
    const row = this.table.rows[r];
    let col = 0;
    for (let c = 0; c < row.cells.length; c++) {
      const cell = row.cells[c];
      const rowspan = cell.rowSpan || 1;
      const colspan = cell.colSpan || 1;

      while (map[r][col]) col++;

      for (let i = 0; i < rowspan; i++) {
        for (let j = 0; j < colspan; j++) {
          map[r + i][col + j] = cell;
        }
      }

      col += colspan;
    }
  }

  return map[rowIndex]?.[colIndex] || null;
}

startColumnResize(e, td) {
  e.preventDefault();

  const table = this.table; // 🔧 Moved this up before it's used
  const colIndex = td.cellIndex;
  const startX = e.clientX;

  const rows = Array.from(table.rows);
  const leftCells = rows.map(row => row.cells[colIndex]);
  const rightCells = rows.map(row => row.cells[colIndex + 1]);

  const leftStartWidths = leftCells.map(cell => cell.offsetWidth);
  const rightStartWidths = rightCells.map(cell => cell?.offsetWidth || 0);

  const minCellWidth = 40;
  const tableWidth = table.offsetWidth;

  const onMouseMove = (moveEvt) => {
    const deltaX = moveEvt.clientX - startX;

   const maxDeltaLeft = leftStartWidths[0] - minCellWidth;
const maxDeltaRight = rightStartWidths[0] - minCellWidth;

// Clamp how far you can drag in either direction
const clampedDelta = Math.max(-maxDeltaLeft, Math.min(deltaX, maxDeltaRight));

const newLeft = leftStartWidths[0] + clampedDelta;
const newRight = rightStartWidths[0] - clampedDelta;


    // Enforce total width constraint for the two columns being resized
    const totalAllowed = leftStartWidths[0] + rightStartWidths[0];
    if (newLeft + newRight > totalAllowed) {
      const scale = totalAllowed / (newLeft + newRight);
      newLeft *= scale;
      newRight *= scale;
    }

    for (let i = 0; i < rows.length; i++) {
      if (leftCells[i])  leftCells[i].style.width = `${newLeft}px`;
      if (rightCells[i]) rightCells[i].style.width = `${newRight}px`;
    }

  };

  const onMouseUp = () => {
    document.removeEventListener("mousemove", onMouseMove);
    document.removeEventListener("mouseup", onMouseUp);
    this.builder?.saveHistory?.();
    this.resnapAndReflow();
  };

  document.addEventListener("mousemove", onMouseMove);
  document.addEventListener("mouseup", onMouseUp);
}


  lockCellEditing() {
    if (!this.table) return;
    this.table.querySelectorAll("td").forEach(td => {
      td.setAttribute("contenteditable", "false");
      td.classList.add("locked-cell");
    });
  }

  unlockCellEditing() {
    if (!this.table) return;
    this.table.querySelectorAll("td").forEach(td => {
      td.setAttribute("contenteditable", "true");
      td.classList.remove("locked-cell");
    });
  }

  isTableAtPageLimit() {
    const container = this.table?.closest(".element");
    const content = container?.closest(".content");
    if (!container || !content) return false;

    const DROP_MARGIN = this.builder.DROP_MARGIN || 20;
    const usableHeight = content.clientHeight - DROP_MARGIN;
    const top = parseInt(container.style.top || 0, 10);
    const currentBottom = top + container.offsetHeight;

    return currentBottom >= usableHeight;
  }

  updateSelectionAndRefresh() {
    if (!this.table) return;

    const container = this.table.closest(".element");
    if (!container || !this.builder) return;

    const ROW = this.builder.ROW_HEIGHT;

    Array.from(this.table.rows).forEach(row => {
      row.style.removeProperty("height");
      row.style.removeProperty("min-height");
    });

    Array.from(this.table.rows).forEach(row => {
      const snapped = Math.ceil(row.offsetHeight / ROW) * ROW;
      row.style.height = `${snapped}px`;
    });

    const totalSnapped = Math.round(this.table.offsetHeight / ROW) * ROW;
    container.dataset.rows = totalSnapped / ROW;
    container.style.height = `${totalSnapped}px`;

    this.rebindTableCellEvents();
    this.updateCellSelection();
    this.builder.pushElementsDown(container);
    requestAnimationFrame(() => this.builder.reflowContent());
    this.onTableChanged?.();

    // Re-check page limit after refresh
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

if (maxContentHeight < 5) maxContentHeight = ROW;

const snapped = Math.ceil(maxContentHeight / ROW) * ROW;

      row.style.minHeight = "0";
      row.style.height = `${snapped}px`;
      Array.from(row.cells).forEach(cell => {
        cell.style.height = `${snapped}px`;
      });
    });

    void this.table.offsetHeight;
    const tableHeight = this.table.offsetHeight;
    const snappedHeight = Math.round(tableHeight / ROW) * ROW;
    container.dataset.rows = snappedHeight / ROW;
    container.style.height = `${snappedHeight}px`;
    this.builder.pushElementsDown(container);
    this.builder.reflowContent(container.closest(".content"));
    this.builder.saveHistory?.();

    // Recheck lock status
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

getCellAt(rowIndex, colIndex) {
  const rows = Array.from(this.table.rows);
  let realCol = 0;
  for (const cell of rows[rowIndex]?.cells || []) {
    const span = cell.colSpan || 1;
    if (realCol <= colIndex && colIndex < realCol + span) {
      return cell;
    }
    realCol += span;
  }
  return null;
}

getColumnCount() {
  const rows = this.table.rows;
  let maxCols = 0;
  for (const row of rows) {
    let count = 0;
    for (const cell of row.cells) {
      count += cell.colSpan || 1;
    }
    maxCols = Math.max(maxCols, count);
  }
  return maxCols;
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


insertRow(below = false) {
  if (!this.selectedCell) return;
  const selectedRow = this.selectedCell.parentElement;
  const rowIndex = Array.from(selectedRow.parentElement.children).indexOf(selectedRow);
  const insertIndex = below ? rowIndex + 1 : rowIndex;

  const colCount = this.getColumnCount();
  const newRow = this.table.insertRow(insertIndex);

  let colIndex = 0;
  while (colIndex < colCount) {
    const aboveIndex = insertIndex - 1;
    const cellAbove = this.getCellAt(aboveIndex, colIndex);

    const newCell = document.createElement("td");

    if (cellAbove && cellAbove.rowSpan > 1 && aboveIndex >= 0) {
      // Check if the cell above spans into the new row
      const isSpanning = aboveIndex + cellAbove.rowSpan > insertIndex;
      if (isSpanning) {
        cellAbove.rowSpan += 1;
        colIndex += cellAbove.colSpan || 1;
        continue;
      }
    }

    this.initializeCell(newCell);
    newRow.appendChild(newCell);
    colIndex++;
  }

  this.rebindTableCellEvents(); // 🔁 Ensure new row is wired up
  this.builder.reflowTable(this.table);
  this.builder.pushHistory();
}


mergeSelectedCells() {
  if (!this.table || this.selectedCells.size <= 1) return;

  const selected = Array.from(this.selectedCells);
  const rows = Array.from(this.table.rows);

  // Step 1: Determine bounding box of selection
  let minRow = Infinity, maxRow = -1, minCol = Infinity, maxCol = -1;
  const selectedMap = new Set();

  for (const cell of selected) {
    const row = cell.parentElement.rowIndex;
    const col = cell.cellIndex;
    selectedMap.add(`${row},${col}`);
    minRow = Math.min(minRow, row);
    maxRow = Math.max(maxRow, row);
    minCol = Math.min(minCol, col);
    maxCol = Math.max(maxCol, col);
  }

  // Step 2: Check if all cells within bounding box are selected
  let allCovered = true;
  for (let r = minRow; r <= maxRow; r++) {
    const row = rows[r];
    for (let c = minCol; c <= maxCol; c++) {
      const key = `${r},${c}`;
      const cell = row.cells[c];
      if (!selectedMap.has(key) || !cell.classList.contains("multi-selected")) {
        allCovered = false;
        break;
      }
    }
    if (!allCovered) break;
  }

  if (!allCovered) {
    alert("⚠️ Please select a full rectangular block of cells to merge.");
    return;
  }

  // Step 3: Perform merge
  const rowspan = maxRow - minRow + 1;
  const colspan = maxCol - minCol + 1;
  const targetCell = rows[minRow].cells[minCol];

  targetCell.rowSpan = rowspan;
  targetCell.colSpan = colspan;
  targetCell.classList.remove("multi-selected");

  for (let r = minRow; r <= maxRow; r++) {
    const row = rows[r];
    for (let c = maxCol; c >= minCol; c--) {
      const cell = row.cells[c];
      if (cell !== targetCell) row.deleteCell(c);
    }
  }

  this.selectedCells.clear();
  this.selectedCell = targetCell;
  this.updateSelectionAndRefresh();
}
unmergeSelectedCell() {
  if (!this.selectedCell || !this.table) return;

  const td = this.selectedCell;
  const row = td.parentElement.rowIndex;
  const col = td.cellIndex;

  const rowspan = td.rowSpan || 1;
  const colspan = td.colSpan || 1;

  if (rowspan === 1 && colspan === 1) {
    console.warn("⚠️ Cell is not merged.");
    return;
  }

  td.removeAttribute("rowSpan");
  td.removeAttribute("colSpan");

  // Add missing cells to restore the original grid
  for (let r = row; r < row + rowspan; r++) {
    const targetRow = this.table.rows[r];
    if (!targetRow) continue;

    for (let c = col; c < col + colspan; c++) {
      if (r === row && c === col) continue; // Skip original cell

      const newCell = targetRow.insertCell(c);
      newCell.setAttribute("contenteditable", "true");
      newCell.textContent = "";

      newCell.style.direction = "ltr";
      newCell.style.unicodeBidi = "plaintext";
      newCell.style.textAlign = "left";
      newCell.style.padding = "2px 4px";

      // Optional: copy visual style
      const ref = td;
      const computed = getComputedStyle(ref);
      newCell.style.width         = ref.offsetWidth + "px";
      newCell.style.border        = computed.border;
      newCell.style.verticalAlign = computed.verticalAlign;
      newCell.style.lineHeight    = computed.lineHeight;
      newCell.style.boxSizing     = computed.boxSizing;
      newCell.style.overflowWrap  = computed.overflowWrap;
      newCell.style.wordBreak     = computed.wordBreak;
    }
  }

  this.rebindTableCellEvents();
  this.updateSelectionAndRefresh();
}

deleteRow(rowIndex) {
  const rows = Array.from(this.table.rows);
  if (rows.length <= 1) return; // Prevent deleting the last row

  const rowToDelete = rows[rowIndex];
  if (!rowToDelete) return;

  // Adjust rowSpan of affected cells above
  for (let i = 0; i < rowToDelete.cells.length; i++) {
    const cell = rowToDelete.cells[i];
    const colIndex = cell.cellIndex;

    const aboveCell = this.getCellAt(rowIndex - 1, colIndex);
    if (aboveCell && aboveCell.rowSpan > 1) {
      aboveCell.rowSpan -= 1;
    }
  }

  this.table.deleteRow(rowIndex);

  // Reset selected cell to a nearby valid one
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
  this.builder.reflowTable(this.table);
  this.builder.pushHistory();
}

rebindTableCellEvents() {
  if (!this.table) return;

  const rows = Array.from(this.table.rows);

  const getPosition = (cell) => {
    const row = cell.parentElement.rowIndex;
    let col = 0;
    let found = false;
    for (const c of cell.parentElement.cells) {
      if (c === cell) {
        found = true;
        break;
      }
      col += c.colSpan || 1;
    }
    return { row, col };
  };

  const getCellBox = (cell) => {
    const { row, col } = getPosition(cell);
    return {
      top: row,
      left: col,
      bottom: row + (cell.rowSpan || 1) - 1,
      right: col + (cell.colSpan || 1) - 1
    };
  };

  this.table.querySelectorAll("td").forEach(td => {
    td.onclick = (e) => {
      const box = getCellBox(td);

      if (e.shiftKey && this.anchorCell) {
        const anchorBox = getCellBox(this.anchorCell);

        const minRow = Math.min(anchorBox.top, box.top);
        const maxRow = Math.max(anchorBox.bottom, box.bottom);
        const minCol = Math.min(anchorBox.left, box.left);
        const maxCol = Math.max(anchorBox.right, box.right);

        this.selectedCells.clear();
        this.table.querySelectorAll("td").forEach(cell => {
          cell.classList.remove("multi-selected", "selected-cell");

          const b = getCellBox(cell);
          if (
            b.top >= minRow && b.bottom <= maxRow &&
            b.left >= minCol && b.right <= maxCol
          ) {
            cell.classList.add("multi-selected");
            this.selectedCells.add(cell);
          }
        });

        this.selectedCell = td;
        td.classList.add("selected-cell");

        this.updateCellSelection();
      } else {
        // Regular click – clear others, select only this cell
        this.anchorCell = td;
        this.selectedCell = td;
        this.selectedCells.clear();

        this.table.querySelectorAll("td").forEach(cell =>
          cell.classList.remove("multi-selected", "selected-cell")
        );

        td.classList.add("multi-selected", "selected-cell");
        this.selectedCells.add(td);

        this.updateCellSelection();
        this.showForTable(this.table, td);
      }
    };

    td.onkeydown = (e) => {
      if (td.classList.contains("locked-cell")) {
        const blocked = ["Enter", "Tab"];
        if (blocked.includes(e.key) || ((e.ctrlKey || e.metaKey) && ["+", "="].includes(e.key))) {
          e.preventDefault();
        }
      }
    };

    // Add column resizer
    if (!td.querySelector(".resizer") && td.cellIndex < td.parentElement.cells.length - 1) {
      const resizer = document.createElement("div");
      resizer.className = "resizer";
      td.style.position = "relative";
      resizer.style.cssText = `
        position: absolute;
        top: 0; right: 0;
        width: 5px;
        height: 100%;
        cursor: col-resize;
        z-index: 10;
      `;
      td.appendChild(resizer);
      resizer.addEventListener("mousedown", e => this.startColumnResize(e, td));
    }
  });
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
  const rows = this.table.rows;
  const colTotal = rows[0]?.cells.length || 0;

  if (colTotal <= 1) {
    console.warn("🚫  Only 1 column left — can’t delete.");
    return;
  }

  let index = passedIndex;
  if (index == null || isNaN(index)) {
    index = this.selectedCell ? this.selectedCell.cellIndex : colTotal - 1;
  }

  index = Math.max(0, Math.min(index, colTotal - 1));

  // Remove the column
  for (const row of rows) {
    if (index < row.cells.length) {
      row.deleteCell(index);
    }
  }

  this.selectedCell = null;

  // Normalize widths again
  const newCols = rows[0]?.cells.length || 1;
  const pctWidth = (100 / newCols) + "%";

  for (const row of rows) {
    Array.from(row.cells).forEach(cell => {
      cell.style.width = pctWidth;
    });

    // Optional: If column count is uneven, ensure every row still has correct cell count
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
  this.updateSelectionAndRefresh();
}

}

