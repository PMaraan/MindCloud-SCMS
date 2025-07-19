console.log("✅ accscript.js LOADED");

function bindEditButtons() {
  document.querySelectorAll('.edit-btn2').forEach(btn => {
    btn.addEventListener('click', function () {
      const userId = this.getAttribute('data-id-no');
      console.log("📝 Edit button clicked. ID:", userId);

      const rawAttr = this.getAttribute('data-id-no');     // Safe string
    const datasetValue = this.dataset.id;              // Also safe string
    const directAttr = this.getAttributeNode('data-id-no').value;

    console.log("Raw attribute:", rawAttr);
    console.log("dataset.id:", datasetValue);
    console.log("Direct getAttributeNode:", directAttr);

      // Call the API endpoint using existing routing
      fetch(`/MindCloud-SCMS/public/api.php?action=get_user&id_no=${encodeURIComponent(userId)}`)
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${res.status}`);
          }
          return response.json();
        })
        .then(data => {
          console.log("✅ User data received:", data);

          if (data.error) {
            console.error("⚠️ Server error:", data.error);
            return; // don't proceed if there's an error
          }
          
          // Example of how you might populate modal (update to match your modal IDs)
          document.getElementById('editIdNumber').value = data.id_no || '';          
          document.getElementById('editFirstName').value = data.fname || '';
          document.getElementById('editMiddleInitial').value = data.mname || '';
          document.getElementById('editLastName').value = data.lname || '';
          document.getElementById('editEmail').value = data.email || '';          
          //document.getElementById('editCollege').value = data.college_short_name || '';
          //document.getElementById('editRole').value = data.role_name || '';

          // Check existence before assigning select values
          const collegeSelect = document.getElementById('editCollege');
          if (collegeSelect) collegeSelect.value = data.college_short_name ?? '';

          const roleSelect = document.getElementById('editRole');
          if (roleSelect) roleSelect.value = data.role_name ?? '';
          // You may need to set role and college dropdowns here too:
          /*
          if (data.role_name) {
            document.getElementById('edit-role').value = data.role_name;
          }
          if (data.college_short_name) {
            document.getElementById('edit-college').value = data.college_short_name;
          }
          */
          // Show the modal (depends on how you're handling modals)
          console.log('Modal element:', document.getElementById('editUserModal'));
          const editModal = new bootstrap.Modal(document.getElementById('editUserModal2'));
          editModal.show();
        })
        .catch(error => {
          console.error("❌ Error fetching user data:", error);
          alert("Error loading user info.");
        });
    });
  });
}



bindEditButtons();





// Search filter function
document.getElementById("search").addEventListener("input", function (e) {
  const value = e.target.value.toLowerCase();
  const rows = document.querySelectorAll("#table-body tr");

  rows.forEach(row => {
    const id = row.children[0].textContent.toLowerCase();
    const email = row.children[1].textContent.toLowerCase();
    const firstName = row.children[2].textContent.toLowerCase();
    const lastName = row.children[4].textContent.toLowerCase();

    const matches =
      id.includes(value) ||
      email.includes(value) ||
      firstName.includes(value) ||
      lastName.includes(value);

    row.style.display = matches ? "" : "none";
  });
});

let editMode = false;

document.addEventListener("DOMContentLoaded", () => {
  bindEditButton();
  
});

function bindEditButton() {
  const editBtn = document.getElementById("edit-btn");
  const editBtnMobile = document.getElementById("edit-btn-mobile");

  if (editBtn) editBtn.addEventListener("click", toggleEditMode);
  if (editBtnMobile) editBtnMobile.addEventListener("click", toggleEditMode);
}

function toggleEditMode() {
  editMode = !editMode;
  document.body.classList.toggle("editing", editMode);

  const editControls = document.getElementById("edit-controls");
  editControls.innerHTML = "";

  if (editMode) {
    editControls.innerHTML = `
      <div class="d-flex justify-content-md-end justify-content-start gap-2 flex-wrap w-100">
        <button id="cancel-edit" class="btn btn-outline-danger d-none d-md-inline-flex p-2">
          <i class="bi bi-x-lg"></i>
        </button>
        <button id="save-edit" class="btn btn-outline-success d-none d-md-inline-flex p-2">
          <i class="bi bi-check-lg"></i>
        </button>
        <button id="cancel-edit-mobile" class="btn btn-outline-danger d-flex d-md-none w-100">
          <i class="bi bi-x-lg me-1"></i> Cancel
        </button>
        <button id="save-edit-mobile" class="btn btn-outline-success d-flex d-md-none w-100">
          <i class="bi bi-check-lg me-1"></i> Save
        </button>
      </div>
    `;
  } else {
    editControls.innerHTML = `
      <button id="edit-btn" class="btn btn-outline-primary d-none d-md-inline-flex p-2">
        <i class="bi bi-pencil-square"></i>
      </button>
      <button id="edit-btn-mobile" class="btn btn-outline-primary d-flex d-md-none w-100">
        <i class="bi bi-pencil-square me-1"></i> Edit Mode
      </button>
    `;
  }

  attachEvents();
  bindEditButton();
}

function attachEvents() {
  const cancelBtn = document.getElementById("cancel-edit");
  const saveBtn = document.getElementById("save-edit");
  const cancelBtnMobile = document.getElementById("cancel-edit-mobile");
  const saveBtnMobile = document.getElementById("save-edit-mobile");

  if (cancelBtn) cancelBtn.addEventListener("click", cancelEdit);
  if (saveBtn) saveBtn.addEventListener("click", saveEdit);
  if (cancelBtnMobile) cancelBtnMobile.addEventListener("click", cancelEdit);
  if (saveBtnMobile) saveBtnMobile.addEventListener("click", saveEdit);
}

function cancelEdit() {
  window.location.reload();
}

function saveEdit() {
  alert("Changes saved (not connected to DB yet)");
}

function createRoleBadge(role) {
  const badge = document.createElement("span");
  badge.className = `role-badge ${role}`;
  badge.textContent = role;
  badge.onclick = function () {
    editRole(this);
  };
  return badge;
}

function editRole(span) {
  if (!editMode) return;

  const cell = span.closest(".role-cell");
  const button = cell.querySelector(".add-role-btn");

  if (cell.dataset.editing === "true") return;
  cell.dataset.editing = "true";

  if (button) button.classList.add("d-none");

  const currentRole = span.innerText.trim();
  const allRoles = ["Dean", "Chair", "Professor", "Academic Affairs"];
  const otherRoles = allRoles.filter(role => role !== currentRole);

  const select = document.createElement("select");
  select.className = "form-select form-select-sm d-inline w-auto me-2";
  select.style.minWidth = "150px";

  const placeholder = document.createElement("option");
  placeholder.disabled = true;
  placeholder.selected = true;
  placeholder.textContent = `${currentRole}`;
  select.appendChild(placeholder);

  otherRoles.forEach(role => {
    const opt = document.createElement("option");
    opt.value = role;
    opt.textContent = role;
    select.appendChild(opt);
  });

  span.replaceWith(select);

  select.addEventListener("change", function () {
    const newRole = this.value;

    [...cell.querySelectorAll(".role-badge")].forEach(badge => {
      if (badge.textContent.trim() === newRole) badge.remove();
    });

    const badge = createRoleBadge(newRole);
    select.replaceWith(badge);
    cell.dataset.editing = "false";

    const badgeCount = cell.querySelectorAll(".role-badge").length;
    const hasSelect = cell.querySelector("select");

    if (badgeCount < 3 && !hasSelect && button) {
      button.classList.remove("d-none");
    }
  });
}

function addRole(button) {
  const cell = button.closest(".role-cell");
  const roleBadges = [...cell.querySelectorAll(".role-badge")].map(b => b.textContent.trim());

  if (cell.dataset.editing === "true" || roleBadges.length >= 3) return;

  const availableRoles = ["Dean", "Chair", "Professor", "Academic Affairs"]
    .filter(role => !roleBadges.includes(role));

  if (availableRoles.length === 0) return;

  button.classList.add("d-none");
  cell.dataset.editing = "true";

  const select = document.createElement("select");
  select.className = "form-select form-select-sm d-inline w-auto me-2 mt-1";
  select.style.minWidth = "150px";

  const placeholder = document.createElement("option");
  placeholder.disabled = true;
  placeholder.selected = true;
  placeholder.textContent = "-- Select Role --";
  select.appendChild(placeholder);

  availableRoles.forEach(role => {
    const opt = document.createElement("option");
    opt.value = role;
    opt.textContent = role;
    select.appendChild(opt);
  });

  select.addEventListener("change", function () {
    const newRole = this.value;

    const badge = createRoleBadge(newRole);
    cell.insertBefore(badge, button);
    select.remove();
    cell.dataset.editing = "false";

    const badgeCount = cell.querySelectorAll(".role-badge").length;
    const hasSelect = cell.querySelector("select");

    if (badgeCount < 3 && !hasSelect) {
      button.classList.remove("d-none");
    }
  });

  cell.insertBefore(select, button);
}

//populate the fields in EditUserModal
document.addEventListener("DOMContentLoaded", () => {
  const editButtons = document.querySelectorAll(".edit-btn");

  editButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      document.getElementById("editIdNumber").value = btn.dataset.id_no;      
      document.getElementById("editFirstName").value = btn.dataset.fname;
      document.getElementById("editMiddleInitial").value = btn.dataset.mname;
      document.getElementById("editLastName").value = btn.dataset.lname;
      document.getElementById("editEmail").value = btn.dataset.email;
      document.getElementById("editCollege").value = btn.dataset.college;
      document.getElementById("editRole").value = btn.dataset.role;
      
    });
  });
});

//fill modal with table values
/*
document.addEventListener('DOMContentLoaded', () => {
    console.log("JS loaded");

    document.querySelectorAll('.edit-btn2').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id-no');

            fetch(`api.php?action=get_user&id_no=${id}`)
                .then(res => res.json())
                .then(data => {
                    console.log("Userdata received: ", data); //checking response

                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    
                    // Fill modal fields
                    document.getElementById('editIdNumber').value = data.id_no;
                    document.getElementById('editFirstName').value = data.fname;
                    document.getElementById('editMiddleInitial').value = data.mname || '';
                    document.getElementById('editLastName').value = data.lname;
                    document.getElementById('editEmail').value = data.email;
                    document.getElementById('editCollege').value = data.college_short_name || '';
                    document.getElementById('editRole').value = data.role_name || '';

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load user data');
                });
        });
    });

});
*/
/*
document.addEventListener('DOMContentLoaded', () => {
  const editButtons = document.querySelectorAll('.edit-btn');
  const modal = document.getElementById('editUserModal');

  editButtons.forEach(button => {
    button.addEventListener('click', () => {
      const id_no = button.getAttribute('data-id_no');
      const email = button.getAttribute('data-email');
      const fname = button.getAttribute('data-fname');
      const mname = button.getAttribute('data-mname');
      const lname = button.getAttribute('data-lname');

      modal.querySelector('[name="id_no"]').value = id_no;
      modal.querySelector('[name="email"]').value = email;
      modal.querySelector('[name="fname"]').value = fname;
      modal.querySelector('[name="mname"]').value = mname;
      modal.querySelector('[name="lname"]').value = lname;
    });
  });
});
*/
console.log("JS file is loading");

window.addEventListener('load', () => {
    console.log("Window loaded");
});

document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded");
});