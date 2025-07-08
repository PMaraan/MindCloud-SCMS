const toggleBtn = document.getElementById('toggleBtn');
const sidebar = document.getElementById('sidebar');

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
});


  document.addEventListener("DOMContentLoaded", function () {
    const currentPath = window.location.pathname.split("/").pop(); // Get the filename like "template_menu.php"
    const navLinks = document.querySelectorAll(".nav-link");

    navLinks.forEach((link) => {
      const linkPath = link.getAttribute("href");

      if (linkPath === currentPath) {
        link.classList.add("active");
      } else {
        link.classList.remove("active");
      }
    });
  });

document.addEventListener("DOMContentLoaded", () => {
  const urlParams = new URLSearchParams(window.location.search);
  const page = urlParams.get("page") || "index";
  const links = document.querySelectorAll(".nav-link.linkstyle");

  links.forEach(link => {
    if (link.href.includes("page=" + page)) {
      link.classList.add("active");
    } else {
      link.classList.remove("active");
    }
  });
});
