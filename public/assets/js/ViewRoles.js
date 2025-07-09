document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchRolesInput");
  const table = document.getElementById("RolesTable");
  const tbody = table.querySelector("tbody");

  const noResultsRow = document.createElement("tr");
  noResultsRow.classList.add("no-results");
  noResultsRow.innerHTML = `
    <td colspan="3" class="text-center text-muted py-3">No results found.</td>
  `;
  tbody.appendChild(noResultsRow);
  noResultsRow.style.display = "none";

  searchInput.addEventListener("input", function () {
    const filter = searchInput.value.trim().toLowerCase();
    let matchCount = 0;

    const rows = tbody.querySelectorAll("tr:not(.no-results)");

    rows.forEach((row) => {
      const roleName = row.children[0].textContent.toLowerCase();
      const match = roleName.includes(filter);

      row.style.display = match ? "" : "none";
      if (match) matchCount++;
    });

    noResultsRow.style.display = matchCount === 0 ? "" : "none";
  });
});
