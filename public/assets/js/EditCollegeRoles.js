document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("facultyGeneralSearch");
  const table = document.getElementById("EditCollegeRolesTable");
  const tbody = table.querySelector("tbody");

  const totalCols = table.querySelectorAll("thead th").length;

  // Create no-results row if not present
  let noResultsRow = tbody.querySelector(".edit-college-no-results");
  if (!noResultsRow) {
    noResultsRow = document.createElement("tr");
    noResultsRow.classList.add("edit-college-no-results");
    const td = document.createElement("td");
    td.colSpan = totalCols;
    td.className = "text-center text-muted py-3";
    td.textContent = "No results found.";
    noResultsRow.appendChild(td);
    tbody.appendChild(noResultsRow);
  }
  noResultsRow.style.display = "none";

  function applySearchFilter() {
    const filterText = searchInput.value.trim().toLowerCase();
    let matchCount = 0;

    const rows = tbody.querySelectorAll("tr:not(.edit-college-no-results)");

    rows.forEach(row => {
      const cells = row.querySelectorAll("td");
      let matches = false;

      for (let i = 0; i < cells.length; i++) {
        if (cells[i].textContent.toLowerCase().includes(filterText)) {
          matches = true;
          break;
        }
      }

      row.style.display = matches ? "" : "none";
      if (matches) matchCount++;
    });

    noResultsRow.style.display = matchCount === 0 ? "" : "none";
  }

  searchInput.addEventListener("input", applySearchFilter);
});
