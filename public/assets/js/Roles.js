console.log("✅ Roles.js LOADED");

// add event listener for modals
function initializeModals() {

  console.log("✅ Initializing modal. Setting up modal action listener.");

  document.addEventListener('click', function (e) {
    console.log("Clicked element: ",e.target);
    
    const button = e.target.closest('[data-action]');
    if (!button) return;

    const action = button.getAttribute('data-action');

    let modal;
    if (button.getAttribute('data-bs-toggle') === "modal"){
      const modalSelector = button.getAttribute('data-bs-target');
      modal = document.querySelector(modalSelector);
    }
    
    switch (action) {
      case 'edit':
        console.log("button: ",button,"\nmodal: ",modal);
        populateEditModal(button, modal);
        break;
      case 'delete':
        populateDeleteModal(button, modal);
        break;
      case 'saveAccountChangesToDb':
        // call api and send modal values using post
        break;
      default:
        console.warn(`Unhandled action type: ${action}`);
    }
  });
}

// initialize the modal
initializeModals();



// Helper function to fill in the edit modal
function populateEditModal(button, modal) {
  modal.querySelector('#editRoleId').value = button.getAttribute('data-role-id') || '';
  modal.querySelector('#editRoleName').value = button.getAttribute('data-role-name') || '';
  modal.querySelector('#editRoleLevel').value = button.getAttribute('data-role-level') || '';
}


// Example: for delete modals
function populateDeleteModal(button, modal) {
  modal.querySelector('#deleteIdNumber').value = button.getAttribute('data-id-no') || '';
  modal.querySelector('#deleteUserName').textContent =
    `${button.getAttribute('data-fname')} ${button.getAttribute('data-lname')}`;
}





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


console.log("Roles JS file is loading");