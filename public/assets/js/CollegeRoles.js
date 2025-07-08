document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const table = document.getElementById("CollegeRolesTable");
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
      const college = row.children[0].textContent.toLowerCase();
      const code = row.children[1].textContent.toLowerCase();

      const match = college.includes(filter) || code.includes(filter);
      row.style.display = match ? "" : "none";

      if (match) matchCount++;
    });

    noResultsRow.style.display = matchCount === 0 ? "" : "none";
  });
});
