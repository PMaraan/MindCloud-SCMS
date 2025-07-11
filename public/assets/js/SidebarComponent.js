// Toggle sidebar collapsed class when the toggle button is clicked
const toggleBtn = document.getElementById('toggleBtn');
const sidebar = document.getElementById('sidebar');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed'); // Collapse or expand sidebar
});


// Highlight nav-link based on current file path
document.addEventListener("DOMContentLoaded", function () {
  const currentPath = window.location.pathname.split("/").pop(); // Get current filename (e.g., "template_menu.php")
  const navLinks = document.querySelectorAll(".nav-link"); // Select all sidebar nav links

  navLinks.forEach((link) => {
    const linkPath = link.getAttribute("href"); // Get link href
    if (linkPath === currentPath) {
      link.classList.add("active"); // Highlight if it matches current file
    } else {
      link.classList.remove("active"); // Remove highlight otherwise
    }
  });
});


// Highlight nav-link based on query parameter (e.g., ?page=index)
document.addEventListener("DOMContentLoaded", () => {
  const urlParams = new URLSearchParams(window.location.search); // Parse query string
  const page = urlParams.get("page") || "index"; // Get 'page' param, default to "index"
  const links = document.querySelectorAll(".nav-link.linkstyle"); // Target nav links with 'linkstyle' class

  links.forEach(link => {
    if (link.href.includes("page=" + page)) {
      link.classList.add("active"); // Add active class if URL matches
    } else {
      link.classList.remove("active"); // Otherwise remove it
    }
  });
});
