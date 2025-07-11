document.addEventListener("DOMContentLoaded", function () {
  // Get references to the search input and college roles table
  const searchInput = document.getElementById("searchInput");
  const table = document.getElementById("CollegeRolesTable");
  const tbody = table.querySelector("tbody");

  // Create and append a hidden "no results found" row
  const noResultsRow = document.createElement("tr");
  noResultsRow.classList.add("no-results");
  noResultsRow.innerHTML = `
    <td colspan="3" class="text-center text-muted py-3">No results found.</td>
  `;
  tbody.appendChild(noResultsRow);
  noResultsRow.style.display = "none";

  // Add input event listener to filter table rows on search
  searchInput.addEventListener("input", function () {
    const filter = searchInput.value.trim().toLowerCase(); // Normalize input
    let matchCount = 0; // Track number of matches

    const rows = tbody.querySelectorAll("tr:not(.no-results)"); // Exclude results row

    rows.forEach((row) => {
      const college = row.children[0].textContent.toLowerCase(); // First column
      const code = row.children[1].textContent.toLowerCase();    // Second column

      const match = college.includes(filter) || code.includes(filter); // Check match
      row.style.display = match ? "" : "none"; // Toggle row visibility

      if (match) matchCount++; // Count matches
    });

    noResultsRow.style.display = matchCount === 0 ? "" : "none"; // Show message if no matches
  });
});
