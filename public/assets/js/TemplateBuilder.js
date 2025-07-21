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
  
    const el = e.target.closest(".element, .label-block, .paragraph-block");
    this.selectElement(el && this.workspace.contains(el) ? el : null);
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

  /* -------- identify element type -------- */
  const type        = e.dataTransfer.getData("text/plain") || e.dataTransfer.getData("type");
  if (!type) return;

  const isLabel     = type === "label";
  const isText3     = type === "text-3";
  const isParagraph = type === "paragraph";
  const isTable     = type === "table";
  const isSignature = type === "signature";

  /* -------- create the shell -------- */
  const el = document.createElement("div");
  el.className =
        isLabel      ? "label-block"
      : isText3      ? "element text-3"
      : isParagraph  ? "paragraph-block"
      : isTable      ? "element table-block"
      : isSignature  ? "element signature-block"
      : /* default */ "element";

  el.dataset.type = type;
  el.classList.add("signature-block", "element");
el.style.left = "32px";
el.style.width = "calc(100% - 32px)";
el.style.boxSizing = "border-box";
el.style.padding = "4px 8px";
el.style.margin = "0";
el.style.border = "1px dashed #555";
el.style.background = "#fff";


  let body;               
  let startRows = 1;        

  if (isTable) {
    body = document.createElement("table");
    body.className      = "element-body";
    body.contentEditable = false;

    for (let i = 0; i < 3; i++) {
      const tr = document.createElement("tr");
      for (let j = 0; j < 3; j++) {
        const td = document.createElement("td");
        td.contentEditable = true;
        td.textContent     = " ";
        td.style.minWidth  = "60px";
        td.style.padding   = "4px";
        tr.appendChild(td);
      }
      body.appendChild(tr);
    }

    el.addEventListener("mousedown", ev => { ev.stopPropagation(); this.selectElement(el); });

  } else if (isSignature) {
  body = document.createElement("table");
  body.className = "element-body signature-table";
  body.contentEditable = false;

  const row1 = document.createElement("tr");
  const row2 = document.createElement("tr"); 

  for (let i = 0; i < 4; i++) {
    const sigCell = document.createElement("td");
    sigCell.style.cssText = `
      width: 180px;
      min-height: 70px;
      height: 70px;
      text-align: center;
      vertical-align: middle;
      padding: 6px;
    `;

    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/*";
    fileInput.style.display = "none";

    const img = document.createElement("img");
    img.alt = "Signature";
    img.style.cssText = `
      max-height: 50px;
      max-width: 100%;
      display: none;
    `;

    const uploadBtn = document.createElement("button");
    uploadBtn.type = "button";
    uploadBtn.className = "btn btn-sm btn-outline-secondary";
    uploadBtn.textContent = "Upload Signature";

    uploadBtn.onclick = () => fileInput.click();
    fileInput.onchange = () => {
      if (fileInput.files[0]) {
        const reader = new FileReader();
        reader.onload = () => {
          img.src = reader.result;
          img.style.display = "block";
          uploadBtn.style.display = "none";
        };
        reader.readAsDataURL(fileInput.files[0]);
      }
    };

    sigCell.appendChild(fileInput);
    sigCell.appendChild(img);
    sigCell.appendChild(uploadBtn);
    row1.appendChild(sigCell);

    const infoCell = document.createElement("td");
    infoCell.style.cssText = `
      padding: 6px;
      font-size: 12px;
      line-height: 1.3;
    `;

    const name = document.createElement("div");
    name.contentEditable = true;
    name.textContent = "Name";

    const date = document.createElement("div");
    date.contentEditable = true;
    date.textContent = "Date";

    const role = document.createElement("div");
    role.contentEditable = true;
    role.textContent = "Role";

    for (const part of [name, date, role]) {
      part.style.cssText = `
        border-bottom: 1px solid #ccc;
        margin-bottom: 4px;
        padding: 2px;
        min-height: 18px;
      `;
    }

    infoCell.appendChild(name);
    infoCell.appendChild(date);
    infoCell.appendChild(role);
    row2.appendChild(infoCell);
  }

  body.appendChild(row1);
  body.appendChild(row2);
  el.appendChild(body);
document.body.appendChild(el);
const actualHeight = el.offsetHeight;
document.body.removeChild(el);

const requiredRows = Math.ceil(actualHeight / this.ROW_HEIGHT);
el.dataset.rows = requiredRows;
el.style.height = requiredRows * this.ROW_HEIGHT + "px";


  el.addEventListener("mousedown", (ev) => {
    ev.stopPropagation();
    this.selectElement(el);
  });

  } else {
    body = document.createElement("div");
    body.className = "element-body";
    body.contentEditable = true;
    body.textContent     = isLabel ? "Label text" : "Editable text";

    body.style.fontFamily = this.defaultFontFamily;
    body.style.fontSize   = this.defaultFontSize + "px";
  }

  el.appendChild(body);

  if (isTable || isSignature) {
    document.body.appendChild(el);
    startRows = Math.ceil(el.offsetHeight / this.ROW_HEIGHT);
    document.body.removeChild(el);
  } else if (isText3) {
    startRows = 3;
  }

  el.dataset.rows = startRows;
  el.style.height = startRows * this.ROW_HEIGHT + "px";

  if (!isLabel && !isTable && !isSignature) {
    this.setupTextArea(el);
  }

  const grip = document.createElement("div");
  grip.className = "drag-handle";
  grip.innerHTML = "<i class='bi bi-grip-vertical'></i>";
  el.prepend(grip);

  /* -------- place element in current content -------- */
  const content = e.currentTarget;
  const insertY = e.offsetY;
  this.placeElement(content, el, insertY);
  this.makeMovable(el);

  if (isLabel) {
    this.applyLabelSuggestion(el);
    this.setupLabelInputRestrictions(el);
  }

  this.selectElement(el);
  this.skipNextClick = true;
}


  placeElement(startContent, el, y) {
    const visited = new Set();
    let content = startContent;
    let currentY = y;

    while (true) {
      if (visited.has(content)) return;
      visited.add(content);

      const contentH = content.clientHeight;
      const usableH = contentH - 2 * this.DROP_MARGIN;
      const rows = parseInt(el.dataset.rows || 1);
      const maxRows = Math.floor(usableH / this.ROW_HEIGHT);
      let insertRow = Math.max(0, Math.min(Math.floor(currentY / this.ROW_HEIGHT), maxRows - rows));

      el.style.top = this.DROP_MARGIN + insertRow * this.ROW_HEIGHT + "px";
      if (!content.contains(el)) content.appendChild(el);
      this.addRemoveButton(el); 

      const blocks = [...content.querySelectorAll(".element, .label-block, .paragraph-block")]
        .filter(b => b !== el)
        .sort((a, b) => parseInt(a.style.top || 0) - parseInt(b.style.top || 0));

      let cursor = parseInt(el.style.top) + rows * this.ROW_HEIGHT;
      for (const blk of blocks) {
        const blkTop = parseInt(blk.style.top || 0);
        const blkHeight = Math.ceil(blk.offsetHeight / this.ROW_HEIGHT) * this.ROW_HEIGHT;
        if (blkTop < cursor && blkTop + blkHeight > parseInt(el.style.top)) {
          blk.style.top = cursor + "px";
          cursor += blkHeight;
        }
        if (parseInt(blk.style.top) + blkHeight > contentH - this.DROP_MARGIN) {
          content.removeChild(blk);
          this.placeElement(this.getNextContent(content), blk, 0);
        }
      }

      const elBottom = parseInt(el.style.top) + rows * this.ROW_HEIGHT;
      if (elBottom > contentH - this.DROP_MARGIN) {
        content.removeChild(el);
        content = this.getNextContent(content);
        currentY = 0;
        continue;
      }
      

      break;
      
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

  const blocks = [...content.querySelectorAll(".element, .label-block, .paragraph-block")]
    .filter(el => !el.classList.contains("dragging"))
    .sort((a, b) => parseInt(a.style.top || 0) - parseInt(b.style.top || 0));

  for (let i = 0; i < blocks.length; i++) {
    const blk = blocks[i];
    let blkTop = parseInt(blk.style.top || 0);

    // 👉 Snap top and height to grid
    if (blkTop % ROW !== 0) {
      blkTop = Math.round(blkTop / ROW) * ROW;
      blk.style.top = `${blkTop}px`;
    }

    const snappedHeight = Math.ceil(blk.offsetHeight / ROW) * ROW;
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

      const usableHeight = content.clientHeight - this.DROP_MARGIN;
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

  if (!el) return;

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

  if (state === last) return; // avoid duplicate saves

  this.undoStack.push(state);
  if (this.undoStack.length > this.maxHistory) this.undoStack.shift();

  // Clear redo history when new action happens
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
rebindWorkspace() {
  this.workspace.querySelectorAll(".element, .label-block, .paragraph-block").forEach(el => {
    const type = el.dataset.type;
    this.addRemoveButton(el);
    this.makeMovable(el);

    if (type === "label") this.setupLabelInputRestrictions(el);
    if (type === "text-3" || type === "paragraph") this.setupTextArea(el);
  });

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
