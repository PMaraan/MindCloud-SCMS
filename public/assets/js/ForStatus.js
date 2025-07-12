/* ---------- state & element references ---------- */
let files = [];                  // current syllabus list
let sortByKey = null;            // "title" | "date" | null
let sortDir  = "asc";            // "asc" | "desc"

const fileList   = document.getElementById("fileList");
const searchInput = document.getElementById("searchInput");
const titleSort  = document.getElementById("titleSort");
const dateSort   = document.getElementById("dateSort");

/* ---------- utility: compare two items ---------- */
function compare(a, b, key) {
  if (key === "date") return new Date(a.date) - new Date(b.date);
  return a.title.localeCompare(b.title);
}

/* ---------- compute filtered + sorted array ---------- */
function getFilteredSorted() {
  const query = searchInput.value.trim().toLowerCase();
  return files
    .filter(f => f.title.toLowerCase().includes(query))
    .sort((a, b) => {
      if (!sortByKey) return 0;
      const diff = compare(a, b, sortByKey);
      return sortDir === "asc" ? diff : -diff;
    });
}

/* ---------- render list to the DOM ---------- */
function renderFiles() {
  fileList.innerHTML = "";
  const data = getFilteredSorted();

  if (data.length === 0) {                     // empty‑state message
    fileList.innerHTML = `
      <div class="no-results text-muted text-center py-4">
        📂 No syllabus found for approval.
      </div>`;
    return;
  }

  data.forEach(f => {                          // create clickable rows
    const a = document.createElement("a");
    a.href = `ViewSyllabusTemplate.php?code=${encodeURIComponent(f.code || f.title)}`;
    a.className = "file-item text-decoration-none text-dark";
    a.innerHTML = `
      <div class="file-info">
        <span class="icon">📘</span>
        <span class="title-text">${f.title}</span>
      </div>
      <div class="file-date">${f.date}</div>`;
    fileList.appendChild(a);
  });

  /* update sort arrows */
  titleSort.textContent = sortByKey === "title" ? (sortDir === "asc" ? "↑" : "↓") : "↕";
  dateSort.textContent  = sortByKey === "date"  ? (sortDir === "asc" ? "↑" : "↓") : "↕";
}

/* ---------- toggle sorting ---------- */
function sortBy(key) {
  if (sortByKey === key) {
    sortDir = sortDir === "asc" ? "desc" : "asc";
  } else {
    sortByKey = key;
    sortDir   = "asc";
  }
  renderFiles();
}

/* ---------- event listeners ---------- */
searchInput.addEventListener("input", renderFiles);
document.querySelector("[data-sort='title']").addEventListener("click", () => sortBy("title"));
document.querySelector("[data-sort='date']").addEventListener("click", () => sortBy("date"));

/* ---------- public API to load data ---------- */
function setFiles(data) {
  files = Array.isArray(data) ? data : [];
  renderFiles();
}

/* ---------- initial demo data ---------- */
setFiles([
  { title: "IT101 - Introduction to Computing", date: "2025-07-10", code: "IT101" },
  { title: "ENG102 - Academic Writing",         date: "2025-07-11", code: "ENG102" },
  { title: "MATH201 - Calculus 1",              date: "2025-07-12", code: "MATH201" }
]);
