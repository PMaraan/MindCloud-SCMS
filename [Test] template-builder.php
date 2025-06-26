<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>template Builder Test</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --transition-speed: 0.4s;
    }

    body {
      margin: 0;
      font-family: sans-serif;
      height: 100vh;
      overflow: hidden;
      display: flex;
      flex-direction: row-reverse;
    }

    /* Sidebar container */
    .sidebar-wrapper {
      position: relative;
      width: 250px;
      transition: transform var(--transition-speed) ease-in-out;
      z-index: 1000;
    }

    /* Sidebar slide-out effect */
    .sidebar-wrapper.hidden {
      transform: translateX(210px);
    }

    /* Sidebar style */
    .sidebar {
      background: #222;
      color: #fff;
      padding: 10px;
      height: 100vh;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      animation: slideInSidebar var(--transition-speed) ease forwards;
    }

    /* Sidebar toggle button */
    .toggle-btn {
      width: 40px;
      height: 40px;
      background: #111;
      color: #fff;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      position: absolute;
      right: 250px;
      top: 10px;
      border-top-right-radius: 5px;
      border-bottom-right-radius: 5px;
      cursor: pointer;
      z-index: 1001;
      transition: background var(--transition-speed);
    }
    .toggle-btn:hover {
      background: #333;
    }

    /* Elements available for drag */
    .element {
      padding: 10px;
      background: #444;
      border: 1px solid #555;
      cursor: grab;
      user-select: none;
      margin-bottom: 10px;
      border-radius: 5px;
      transition: transform var(--transition-speed), background var(--transition-speed);
    }
    .element:hover {
      transform: scale(1.03);
      background: #555;
    }

    /* Main builder area */
    .builder {
      flex-grow: 1;
      height: 100vh;
      background: #f0f0f0;
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 10px;
      overflow-y: auto;
      animation: fadeIn var(--transition-speed) ease-in-out;
    }

    /* Placed element style */
    .dropped {
      padding: 10px;
      background: #fff;
      border: 1px solid #ccc;
      min-height: 50px;
      text-align: center;
      cursor: move;
      position: relative;
      animation: dropFadeIn var(--transition-speed) ease;
      transition: box-shadow var(--transition-speed);
    }
    .dropped:hover {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes dropFadeIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes slideInSidebar {
      from { transform: translateX(50px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
  </style>
</head>
<body>
  <!-- Sidebar with elements -->
  <div class="sidebar-wrapper" id="sidebarWrapper">
    <div class="sidebar">
      <div>
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <h5 class="text-white mt-3">Drag Elements</h5>
        <!-- Draggable options -->
        <div class="element" draggable="true" data-type="button">Button</div>
        <div class="element" draggable="true" data-type="input">Text Input</div>
        <div class="element" draggable="true" data-type="label">Label</div>
        <div class="element" draggable="true" data-type="radio">Radio Button</div>
        <div class="element" draggable="true" data-type="textarea">Textarea</div>
      </div>
      <div class="mt-3">
        <button class="btn btn-success w-100" onclick="saveLayout()">Save</button>
      </div>
    </div>
  </div>

  <!-- Main builder grid -->
  <div class="builder" id="builder"></div>

  <script>
    const builder = document.getElementById("builder");
    const sidebarWrapper = document.getElementById("sidebarWrapper");
    let draggedElement = null;
    let dragSource = null;

    // Enable drag from sidebar
    document.querySelectorAll(".element").forEach((el) => {
      el.addEventListener("dragstart", (e) => {
        e.dataTransfer.setData("text/plain", e.target.dataset.type);
        draggedElement = null;
        dragSource = "sidebar";
      });
    });

    // Enable drag from within builder
    builder.addEventListener("dragstart", (e) => {
      if (e.target.classList.contains("dropped")) {
        draggedElement = e.target;
        dragSource = "builder";
      }
    });

    // Allow dropping
    builder.addEventListener("dragover", (e) => {
      e.preventDefault();
    });

    // Handle drop logic
    builder.addEventListener("drop", (e) => {
      e.preventDefault();
      const type = e.dataTransfer.getData("text/plain");

      // If dragging existing element from builder
      if (dragSource === "builder" && draggedElement) {
        const afterElement = getDragAfterElement(builder, e.clientY);
        if (afterElement === null) {
          builder.appendChild(draggedElement);
        } else {
          builder.insertBefore(draggedElement, afterElement);
        }
        draggedElement = null;
        return;
      }

      // New element from sidebar
      const element = document.createElement("div");
      element.classList.add("dropped");
      element.setAttribute("draggable", "true");

      // Assign HTML for element type
      switch (type) {
        case "button":
          element.innerHTML = '<button class="btn btn-primary w-100">Click Me</button>';
          break;
        case "input":
          element.innerHTML = '<input type="text" class="form-control" placeholder="Text Input" />';
          break;
        case "label":
          element.innerHTML = '<label>Label</label>';
          break;
        case "radio":
          element.innerHTML = '<div class="form-check d-flex align-items-center justify-content-center"><input class="form-check-input me-2" type="radio" name="radioGroup"><label class="form-check-label">Radio</label></div>';
          break;
        case "textarea":
          element.innerHTML = '<textarea class="form-control" placeholder="Write something..."></textarea>';
          break;
      }

      builder.appendChild(element);
    });

    // Determine closest element for drop position
    function getDragAfterElement(container, y) {
      const draggableElements = [...container.querySelectorAll(".dropped:not(.dragging)")];

      return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          return { offset: offset, element: child };
        } else {
          return closest;
        }
      }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // Sidebar toggle logic
    function toggleSidebar() {
      sidebarWrapper.classList.toggle("hidden");
    }

    // Save layout (to console)
    function saveLayout() {
      const html = builder.innerHTML;
      console.log("Saved Layout HTML:", html);
      alert("Layout saved (check console).");
    }
  </script>
</body>
</html>
