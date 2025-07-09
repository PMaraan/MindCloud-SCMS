document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("facultyGeneralSearch");
  const table = document.getElementById("FacultyRolesTable");
  const tbody = table.querySelector("tbody");
  const roleFilterOptions = document.getElementById("facultyRoleFilterOptions");

  const totalCols = table.querySelectorAll("thead th").length;

  // Create no-results row if not present
  let noResultsRow = tbody.querySelector(".faculty-no-results");
  if (!noResultsRow) {
    noResultsRow = document.createElement("tr");
    noResultsRow.classList.add("faculty-no-results");
    const td = document.createElement("td");
    td.colSpan = totalCols;
    td.className = "text-center text-muted py-3";
    td.textContent = "No results found.";
    noResultsRow.appendChild(td);
    tbody.appendChild(noResultsRow);
  }
  noResultsRow.style.display = "none";

  let activeRole = null;

  function loadRoleOptions() {
    const rolesSet = new Set();
    const rows = tbody.querySelectorAll("tr:not(.faculty-no-results)");
    rows.forEach(row => {
      const roleCell = row.children[5];
      if (roleCell) rolesSet.add(roleCell.textContent.trim());
    });

    roleFilterOptions.innerHTML = "";

    rolesSet.forEach(role => {
      const btn = document.createElement("button");
      btn.className = "btn btn-outline-primary btn-sm w-100";
      btn.textContent = role;
      btn.dataset.role = role;

      btn.addEventListener("click", function () {
        if (activeRole === role) {
          activeRole = null;
        } else {
          activeRole = role;
        }

        Array.from(roleFilterOptions.children).forEach(button => {
          button.classList.remove("active", "btn-primary");
          button.classList.add("btn-outline-primary");
        });

        if (activeRole) {
          btn.classList.add("active", "btn-primary");
          btn.classList.remove("btn-outline-primary");
        }

        applyFilters();
      });

      roleFilterOptions.appendChild(btn);
    });

    const resetBtn = document.createElement("button");
    resetBtn.className = "btn btn-outline-secondary btn-sm w-100 mt-1";
    resetBtn.textContent = "Show All";
    resetBtn.addEventListener("click", () => {
      activeRole = null;
      applyFilters();
      Array.from(roleFilterOptions.children).forEach(button => {
        button.classList.remove("active", "btn-primary");
        button.classList.add("btn-outline-primary");
      });
    });
    roleFilterOptions.appendChild(resetBtn);
  }

  function applyFilters() {
    const filterText = searchInput.value.trim().toLowerCase();

    let matchCount = 0;
    const rows = tbody.querySelectorAll("tr:not(.faculty-no-results)");

    rows.forEach(row => {
      const cells = row.querySelectorAll("td");
      const role = cells[5]?.textContent.trim();
      let matchesSearch = false;

      for (let i = 0; i <= 5; i++) {
        if (cells[i].textContent.toLowerCase().includes(filterText)) {
          matchesSearch = true;
          break;
        }
      }

      const matchesRole = !activeRole || role === activeRole;
      const shouldShow = matchesSearch && matchesRole;

      row.style.display = shouldShow ? "" : "none";
      if (shouldShow) matchCount++;
    });

    noResultsRow.style.display = matchCount === 0 ? "" : "none";
  }

  loadRoleOptions();
  searchInput.addEventListener("input", applyFilters);
});
