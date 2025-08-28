<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LPU-SCMS | Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/public/assets/css/login-styles.css">
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
            <!-- put anti csrf token... -->
            <form method="POST" action="<?= BASE_PATH ?>/login" autocomplete="off">

              <!-- Email Input -->
              <div class="mb-3">
                <label for="email-input" class="form-label">Email Address</label>
                <input 
                  type="email" 
                  name = "email"
                  class="form-control" 
                  id="email-input"
                  value="vpaa@lpunetwork.edu.ph" 
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
                  name = "password"
                  class="form-control password-input" 
                  id="password-input"
                  value="password"
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
  <script src="<?= BASE_PATH ?>/public/assets/js/login-script.js"></script>
</body>
</html>
