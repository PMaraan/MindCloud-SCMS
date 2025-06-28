<!DOCTYPE html>

<?php
/*
// public/index.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/lib/Database.php';

if (USE_MOCK) {
    require_once __DIR__ . '/../app/models/MockUserModel.php';
    $userModel = new MockUserModel();
} else {
    require_once __DIR__ . '/../app/models/UserModel.php';
    $db = new Database(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
    $pdo = $db->connect();
    $userModel = new UserModel($pdo);
}

// Example login check (replace with real routing)
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$user = $userModel->authenticate($username, $password);

if ($user) {
    echo "Login successful!";
} else {
    echo "Invalid credentials.";
}
    */
?>

<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LPU-SCMS | Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="login-styles.css">
</head>
<body>
  <div class="container-fluid">
    <div class="row full-height">

      <!-- Left Logo Panel -->
      <div class="col-4 left"></div>

      <!-- Right Content Area -->
      <div class="col-8 right">

        <!-- Background Layer -->
        <div class="background-wrapper"></div>

        <!-- Login Form Container -->
        <div class="col login-form-container">
          <div class="col login-form">
            <form>

              <!-- Email Input -->
              <div class="mb-3">
                <label for="email-input" class="form-label">Email Address</label>
                <input 
                  type="email" 
                  class="form-control" 
                  id="email-input" 
                  required>
                <div class="invalid-feedback">
                  Email must be a valid @lpunetwork.edu.ph address.
                </div>
              </div>

              <!-- Password Input -->
              <div class="mb-3">
                <label for="password-input" class="form-label">Password</label>
                <input 
                  type="password" 
                  class="form-control password-input" 
                  id="password-input" 
                  required>
                <div class="invalid-feedback">
                  Password is required.
                </div>
                <div class="text-start">
                  <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
              </div>

              <!-- Submit Button -->
              <button type="submit" class="btn btn-primary login-button">Login</button>

            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/login-script.js"></script>
</body>
</html>
