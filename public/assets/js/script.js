document.getElementById("loginForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent form submission

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    // Validate email and password (Here you can add more complex validation)
    if (email && password) {
        // Placeholder for login functionality, e.g. API call
        alert("Login successful!"); // Replace with actual login logic
    } else {
        alert("Please enter your email and password.");
    }
});
