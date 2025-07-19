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

//pupulate the fields in EditUserModal
document.addEventListener("DOMContentLoaded", () => {
  const editButtons = document.querySelectorAll(".edit-btn");

  editButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      document.getElementById("editIdNumber").value = btn.dataset.id_no;      
      document.getElementById("editFirstName").value = btn.dataset.fname;
      document.getElementById("editMiddleInitial").value = btn.dataset.mname;
      document.getElementById("editLastName").value = btn.dataset.lname;
      document.getElementById("editEmail").value = btn.dataset.email;

      // You can also set college and role dropdowns here
      // Example:
      // document.getElementById("editCollege").value = btn.dataset.college;
      // document.getElementById("editRole").value = btn.dataset.role;
    });
  });
});

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