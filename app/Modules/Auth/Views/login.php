<?php
// Make sure CSRF token exists (router/controller sets it, but safe to re-check)
if (empty($_SESSION['csrf_token_login'])) {
  $_SESSION['csrf_token_login'] = bin2hex(random_bytes(32));
}

// Optional: show flash message
$flash = \App\Helpers\FlashHelper::get();
?>
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
        <div class="background-wrapper"></div>

        <div class="col login-form-container">
          <div class="col login-form">

            <?php if (!empty($flash)): ?>
              <?php
                // Map your flash types to Bootstrap classes
                $type = $flash['type'] ?? 'info';
                $map = ['success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info','danger'=>'danger'];
                $class = $map[$type] ?? 'info';
              ?>
              <div class="alert alert-<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_PATH ?>/login" autocomplete="off">
              <!-- Anti-CSRF -->
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token_login'] ?? '', ENT_QUOTES) ?>">

              <!-- Email Input -->
              <div class="mb-3">
                <label for="email-input" class="form-label">Email Address</label>
                <input 
                  type="email" 
                  name="email"
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
                  name="password"
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
