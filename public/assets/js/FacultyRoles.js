/* FacultyRoles.js (shared)
 * Handles search, filter, edit, and add logic for a 7‑column Faculty table using Bootstrap 5.
 */
document.addEventListener("DOMContentLoaded", () => {

  // Get the first table with exactly 7 headers (our target faculty table)
  const targetTable = Array.from(document.querySelectorAll("table"))
                           .find(t => t.querySelectorAll("thead th").length === 7);
  if (!targetTable) return; // Abort if no target table is found

  // Get core table elements
  const tableBody   = targetTable.querySelector("tbody");
  const totalCols   = targetTable.querySelectorAll("thead th").length;

  // Get DOM elements by ID
  const searchInput       = document.getElementById("generalSearch");
  const roleFilterOptions = document.getElementById("roleFilterOptions");
  const modal             = document.getElementById("editFacultyModal");
  const modalTitle        = document.getElementById("modalTitle");
  const closeBtn          = document.getElementById("modalCloseBtn");
  const saveBtn           = document.getElementById("saveFacultyBtn");
  const addBtn            = document.getElementById("addFacultyBtn");
  const deleteBtn         = document.getElementById("deleteFacultyBtn");
  const openAddBtn        = document.getElementById("openAddFacultyBtn");

  // Ensure necessary elements exist
  if (!searchInput || !roleFilterOptions || !modal) return;

  // Get modal form fields
  const firstNameField      = document.getElementById("editFirstName");
  const middleInitialField  = document.getElementById("editMiddleInitial");
  const lastNameField       = document.getElementById("editLastName");
  const idNumberField       = document.getElementById("editIdNumber");
  const emailField          = document.getElementById("editEmail");
  const roleField           = document.getElementById("editRole");

  // Initialize state variables
  let currentRow = null;
  let activeRole = null;
  let debounceId = null;

  // Create and append the "No Records" row for empty search results
  const noResultsRow = (() => {
    const tr = document.createElement("tr");
    tr.className = "no-results";
    tr.innerHTML = `<td colspan="${totalCols}" class="text-center text-muted py-3">No Records to Display.</td>`;
    tableBody.appendChild(tr);
    return tr;
  })();

  // Clear modal form fields
  const clearForm = () => {
    firstNameField.value     = "";
    middleInitialField.value = "";
    lastNameField.value      = "";
    idNumberField.value      = "";
    emailField.value         = "";
    roleField.value          = "Dean";
  };

  // Rebuild filter buttons based on unique roles in the table
  const rebuildRoleButtons = () => {
    roleFilterOptions.innerHTML = "";
    const roles = new Set(
      Array.from(tableBody.querySelectorAll("tr:not(.no-results) td:nth-child(6)"))
           .map(td => td.textContent.trim())
    );
    roles.forEach(role => {
      const btn = document.createElement("button");
      btn.className = "btn btn-outline-primary btn-sm w-100 mb-1";
      btn.dataset.role = role;
      btn.textContent = role;
      roleFilterOptions.appendChild(btn);
    });
    const reset = document.createElement("button");
    reset.className = "btn btn-outline-secondary btn-sm w-100 mt-1";
    reset.textContent = "Show All";
    roleFilterOptions.appendChild(reset);
  };
  rebuildRoleButtons(); // Initial call to build filter buttons

  // Apply text search and role filter to table rows
  const applyFilters = () => {
    const term = (searchInput?.value || "").trim().toLowerCase();
    let shown = 0;
    Array.from(tableBody.querySelectorAll("tr:not(.no-results)")).forEach(row => {
      const cells = row.children;
      const role  = cells[5].textContent.trim();
      const matchesRole   = !activeRole || role === activeRole;
      const matchesSearch = !term || Array.from(cells).some(
        (c, i) => i < 6 && c.textContent.toLowerCase().includes(term)
      );
      row.style.display = matchesRole && matchesSearch ? "" : "none";
      if (row.style.display === "") shown++;
    });
    noResultsRow.style.display = shown ? "none" : "";
  };

  // Handle edit button click, pre-fill modal fields and show edit mode
  const handleEditClick = e => {
    currentRow = e.currentTarget.closest("tr");
    const cells = currentRow.children;
    idNumberField.value      = cells[0].textContent.trim();
    firstNameField.value     = cells[1].textContent.trim();
    middleInitialField.value = cells[2].textContent.trim();
    lastNameField.value      = cells[3].textContent.trim();
    emailField.value         = cells[4].textContent.trim();
    roleField.value          = cells[5].textContent.trim();

    modalTitle.textContent   = "Manage Faculty";
    saveBtn.classList.remove("d-none");
    deleteBtn.classList.remove("d-none");
    addBtn.classList.add("d-none");
    modal.classList.remove("d-none");
  };

  // Attach edit listener to each edit button in the table
  targetTable.querySelectorAll(".edit-btn").forEach(btn =>
    btn.addEventListener("click", handleEditClick)
  );

  // Debounce and apply filters on search input
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      clearTimeout(debounceId);
      debounceId = setTimeout(applyFilters, 200);
    });
  }

  // Handle role filter button clicks and toggle active state
  roleFilterOptions.addEventListener("click", e => {
    const btn = e.target.closest("button");
    if (!btn) return;
    activeRole = btn.dataset.role || null;
    roleFilterOptions.querySelectorAll("button").forEach(b => {
      b.classList.toggle("btn-primary", b === btn && activeRole);
      b.classList.toggle("btn-outline-primary", !(b === btn && activeRole) && b.dataset.role);
    });
    applyFilters();
  });

  // Close modal on cancel button click
  closeBtn && closeBtn.addEventListener("click", () => modal.classList.add("d-none"));

  // Save edits to the selected row
  saveBtn && saveBtn.addEventListener("click", () => {
    if (!currentRow) return;
    const cells = currentRow.children;
    cells[0].textContent = idNumberField.value;
    cells[1].textContent = firstNameField.value;
    cells[2].textContent = middleInitialField.value;
    cells[3].textContent = lastNameField.value;
    cells[4].textContent = emailField.value;
    cells[5].textContent = roleField.value;
    modal.classList.add("d-none");
    applyFilters();
    rebuildRoleButtons();
  });

  // Delete the selected row
  deleteBtn && deleteBtn.addEventListener("click", () => {
    if (!currentRow) return;
    currentRow.remove();
    modal.classList.add("d-none");
    applyFilters();
    rebuildRoleButtons();
  });

  // Open modal for adding new faculty
  openAddBtn && openAddBtn.addEventListener("click", () => {
    currentRow = null;
    modalTitle.textContent = "Add Faculty";
    saveBtn.classList.add("d-none");
    deleteBtn.classList.add("d-none");
    addBtn.classList.remove("d-none");
    clearForm();
    modal.classList.remove("d-none");
  });

  // Add new row to the table
  addBtn && addBtn.addEventListener("click", () => {
    const newRow = document.createElement("tr");
    newRow.innerHTML = `
      <td>${idNumberField.value}</td>
      <td>${firstNameField.value}</td>
      <td>${middleInitialField.value}</td>
      <td>${lastNameField.value}</td>
      <td>${emailField.value}</td>
      <td>${roleField.value}</td>
      <td class='col-manage'>
        <button class='btn btn-sm btn-outline-primary edit-btn'>
          <i class='bi bi-pencil-square'></i>
        </button>
      </td>`;
    tableBody.appendChild(newRow);
    newRow.querySelector(".edit-btn").addEventListener("click", handleEditClick);

    modal.classList.add("d-none");
    applyFilters();
    rebuildRoleButtons();
  });

  // Run filters on initial load
  applyFilters();
});
