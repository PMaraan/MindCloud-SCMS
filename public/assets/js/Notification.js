document.addEventListener("DOMContentLoaded", () => {
    const tabButtons = document.querySelectorAll(".tab-btn");
    const notifications = document.querySelectorAll(".notification-card");

    // Tab filter functionality
    tabButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            tabButtons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const filter = btn.getAttribute("data-tab");
            notifications.forEach(note => {
                if (filter === "all" || note.classList.contains(filter)) {
                    note.style.display = "flex";
                } else {
                    note.style.display = "none";
                }
            });
        });
    });

    // Mark notification as read when clicked
    notifications.forEach(note => {
        note.addEventListener("click", () => {
            if (note.classList.contains("unread")) {
                note.classList.remove("unread");
                note.classList.add("read");
                const dot = note.querySelector(".unread-dot");
                if (dot) dot.remove();

                // Optional: send AJAX request to PHP to update DB
                // fetch("update_notification.php", {
                //     method: "POST",
                //     headers: { "Content-Type": "application/x-www-form-urlencoded" },
                //     body: "id=" + note.dataset.id
                // });
            }
        });
    });
});
