// Wait for the DOM to fully load before executing
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  const emailInput = document.getElementById("email-input");
  const passwordInput = document.getElementById("password-input");

  form.addEventListener("submit", function (e) {
    //e.preventDefault(); // this code prevents the login logic and should be removed

    // Trim and validate input values
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

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

    console.log("Submitting form with:", email, password);

    // If all validations pass
    if (!isValid) {
      e.preventDefault();
      // alert("Please fill out all of the required fields!"); // this line of code is blocking the login logic and must be removed
    }
  });
});


// --- Password visibility toggle (eye icon)
(function () {
  function togglePassword(btn) {
    const sel = btn.getAttribute('data-target');
    if (!sel) return;
    const input = document.querySelector(sel);
    if (!input) return;

    const icon = btn.querySelector('i');
    const isPassword = input.getAttribute('type') === 'password';
    input.setAttribute('type', isPassword ? 'text' : 'password');

    // Update icon + aria-label
    if (icon) {
      icon.classList.toggle('bi-eye', !isPassword);
      icon.classList.toggle('bi-eye-slash', isPassword);
    }
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');

    // Keep focus on the input for usability
    input.focus({ preventScroll: true });
  }

  document.addEventListener('click', function (ev) {
    const btn = ev.target.closest('.js-toggle-password');
    if (!btn) return;
    ev.preventDefault();
    togglePassword(btn);
  });
})();