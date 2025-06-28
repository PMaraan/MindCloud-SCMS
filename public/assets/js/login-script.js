// Wait for the DOM to fully load before executing
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  const emailInput = document.getElementById("email-input");
  const passwordInput = document.getElementById("password-input");

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    // Trim and validate input values
    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();
    const emailPattern = /^[a-zA-Z0-9._%+-]+@lpunetwork\.edu\.ph$/;

    // Reset previous error states
    emailInput.classList.remove("is-invalid");
    passwordInput.classList.remove("is-invalid");

    let isValid = true;

    // Validate email format
    if (!emailPattern.test(email)) {
      emailInput.classList.add("is-invalid");
      isValid = false;
    }

    // Validate password is not empty
    if (password === "") {
      passwordInput.classList.add("is-invalid");
      isValid = false;
    }

    // If all validations pass
    if (isValid) {
      alert("Successfully logged in!");
    }
  });
});
