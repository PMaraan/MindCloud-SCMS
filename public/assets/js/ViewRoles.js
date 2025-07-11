document.addEventListener("DOMContentLoaded", function () {
  // Element references
  const searchInput = document.getElementById("searchRolesInput");
  const table = document.getElementById("RolesTable");
  const tbody = table?.querySelector("tbody");
  const addRoleBtn = document.getElementById("openAddRoleModal");
  const modal = document.getElementById("addRoleModal");
  const confirmBtn = document.getElementById("confirmAddRole");
  const saveEditBtn = document.getElementById("saveEditRole");
  const deleteBtn = document.getElementById("deleteRole"); // 🆕 Delete button
  const closeModalBtn = document.querySelector(".close-add-role");
  const modalTitle = document.getElementById("roleModalTitle");

  let noResultsRow = null;
  let isEditMode = false;
  let editingRow = null;

  const permissionFields = {
    manageUsers: "permManageUsers",
    accessReports: "permAccessReports"
  };

  if (searchInput && table && tbody) {
    noResultsRow = document.createElement("tr");
    noResultsRow.classList.add("no-results");
    noResultsRow.innerHTML = `
      <td colspan="3" class="text-center text-muted py-3">No results found.</td>
    `;
    tbody.appendChild(noResultsRow);
    noResultsRow.style.display = "none";

    searchInput.addEventListener("input", function () {
      filterRoles(searchInput.value);
    });
  }

  function filterRoles(filterValue) {
    const filter = filterValue.trim().toLowerCase();
    let matchCount = 0;
    const rows = tbody.querySelectorAll("tr:not(.no-results)");

    rows.forEach((row) => {
      const roleName = row.children[0].textContent.toLowerCase();
      const match = roleName.includes(filter);
      row.style.display = match ? "" : "none";
      if (match) matchCount++;
    });

    if (noResultsRow) {
      noResultsRow.style.display = matchCount === 0 ? "" : "none";
    }
  }

  if (addRoleBtn && modal) {
    addRoleBtn.addEventListener("click", () => openModal("add"));
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  }

  if (closeModalBtn) {
    closeModalBtn.addEventListener("click", closeModal);
  }

  function closeModal() {
    modal.style.display = "none";
    editingRow = null;
    isEditMode = false;
  }

  function openModal(mode, row = null) {
    isEditMode = mode === "edit";
    editingRow = row;

    const roleNameInput = document.getElementById("roleName");
    roleNameInput.value = isEditMode && row
      ? row.querySelector(".role-name").textContent.trim()
      : "";

    Object.keys(permissionFields).forEach((key) => {
      document.getElementById(permissionFields[key]).checked = false;
    });

    if (isEditMode && row?.dataset.permissions) {
      try {
        const savedPermissions = JSON.parse(row.dataset.permissions);
        for (const key in savedPermissions) {
          const inputId = permissionFields[key];
          if (inputId) {
            document.getElementById(inputId).checked = savedPermissions[key];
          }
        }
      } catch (e) {
        console.warn("Invalid permission JSON:", e);
      }
    }

    modalTitle.textContent = isEditMode ? "Edit Role" : "Add New Role";
    confirmBtn.classList.toggle("d-none", isEditMode);
    saveEditBtn.classList.toggle("d-none", !isEditMode);
    deleteBtn.classList.toggle("d-none", !isEditMode); // 🆕 Show delete in edit mode
    modal.style.display = "flex";
  }

  if (confirmBtn) {
    confirmBtn.addEventListener("click", () => {
      const roleNameInput = document.getElementById("roleName");
      const roleName = roleNameInput.value.trim();
      if (roleName === "") return alert("Please enter a role name.");

      const permissions = {};
      Object.keys(permissionFields).forEach((key) => {
        permissions[key] = document.getElementById(permissionFields[key]).checked;
      });

      const newRow = document.createElement("tr");
      newRow.dataset.permissions = JSON.stringify(permissions);
      newRow.innerHTML = `
        <td class="role-name">${roleName}</td>
        <td class="role-count">0</td>
        <td class="role-manage text-center">
          <button class="btn btn-sm btn-outline-secondary edit-role-btn" data-role-name="${roleName}">
            <i class="bi bi-pencil-square"></i>
          </button>
        </td>
      `;
      tbody.appendChild(newRow);

      roleNameInput.value = "";
      Object.keys(permissionFields).forEach((key) => {
        document.getElementById(permissionFields[key]).checked = false;
      });

      if (noResultsRow) noResultsRow.style.display = "none";
      filterRoles(searchInput.value);
      closeModal();
    });
  }

  if (saveEditBtn) {
    saveEditBtn.addEventListener("click", () => {
      if (!editingRow) return;

      const roleNameInput = document.getElementById("roleName");
      const newRoleName = roleNameInput.value.trim();
      if (newRoleName === "") return alert("Role name cannot be empty.");

      const permissions = {};
      Object.keys(permissionFields).forEach((key) => {
        permissions[key] = document.getElementById(permissionFields[key]).checked;
      });

      editingRow.querySelector(".role-name").textContent = newRoleName;
      editingRow.querySelector(".edit-role-btn").dataset.roleName = newRoleName;
      editingRow.dataset.permissions = JSON.stringify(permissions);
      closeModal();
    });
  }

  // 🆕 Delete role logic
  if (deleteBtn) {
    deleteBtn.addEventListener("click", () => {
      if (!editingRow) return;
      const roleName = editingRow.querySelector(".role-name")?.textContent;
      const confirmDelete = confirm(`Are you sure you want to delete the role "${roleName}"?`);
      if (confirmDelete) {
        editingRow.remove();
        closeModal();
        filterRoles(searchInput.value);
      }
    });
  }

  document.addEventListener("click", function (e) {
    if (e.target.closest(".edit-role-btn")) {
      const btn = e.target.closest(".edit-role-btn");
      const row = btn.closest("tr");
      openModal("edit", row);
    }
  });
});
