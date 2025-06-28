document.getElementById("search").addEventListener("input", function (e) {
  const value = e.target.value.toLowerCase();
  const rows = document.querySelectorAll("#table-body tr");
  rows.forEach(row => {
    const email = row.children[0].textContent.toLowerCase();
    row.style.display = email.includes(value) ? "" : "none";
  });
});
