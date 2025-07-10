document.addEventListener("DOMContentLoaded", function () {
  // --- DOM Elements ---
  const searchInput = document.getElementById("searchRolesInput");
  const table = document.getElementById("RolesTable");
  const tbody = table?.querySelector("tbody");

  const addRoleBtn = document.getElementById("openAddRoleModal");
  const modal = document.getElementById("addRoleModal");
  const confirmBtn = document.getElementById("confirmAddRole");
  const closeModalBtn = document.querySelector(".close-add-role");

  let noResultsRow = null;

  // --- Search Logic ---
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

  // --- Modal Open ---
  if (addRoleBtn && modal) {
    addRoleBtn.addEventListener("click", () => {
      modal.style.display = "flex";
    });

    modal.addEventListener("click", (e) => {
      if (e.target === modal) modal.style.display = "none";
    });
  }

  // --- Modal Close ---
  if (closeModalBtn) {
    closeModalBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });
  }

  // --- Confirm Add Role ---
  if (confirmBtn) {
    confirmBtn.addEventListener("click", () => {
      const roleNameInput = document.getElementById("roleName");
      const roleName = roleNameInput.value.trim();

      if (roleName === "") {
        alert("Please enter a role name.");
        return;
      }

      const permissions = {
        manageUsers: document.getElementById("permManageUsers")?.checked || false,
        accessReports: document.getElementById("permAccessReports")?.checked || false,
      };

      console.log("Adding Role:", roleName, permissions);

      // Add to table with 0 members
      const newRow = document.createElement("tr");
      newRow.innerHTML = `
        <td class="role-name">${roleName}</td>
        <td class="role-count">0</td>
        <td class="role-manage">
          <a href="WorkspaceComponent.php?page=edit_role&name=${encodeURIComponent(roleName)}" title="Edit">
            <i class="bi bi-pencil-square"></i>
          </a>
        </td>
      `;
      tbody.appendChild(newRow);

      // Reset form inputs
      roleNameInput.value = "";
      document.getElementById("permManageUsers").checked = false;
      document.getElementById("permAccessReports").checked = false;

      // Hide "no results" message
      if (noResultsRow) noResultsRow.style.display = "none";

      // Re-run search to reflect newly added item
      filterRoles(searchInput.value);

      // Close modal
      modal.style.display = "none";
    });
  }
});
