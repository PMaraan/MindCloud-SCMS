<link rel="stylesheet" href="account-details.css">

<!-- AccountDetails.php -->
<div class="container py-5">
  <h3 class="mb-4">Account Details</h3>

  <!-- 👤 Change Account Info -->
  <div class="card mb-4">
    <div class="card-header">Change Account Details</div>
    <div class="card-body">
      <form>
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control" id="username" placeholder="Enter new username">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" placeholder="Enter new email">
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- 🔒 Change Password -->
  <div class="card mb-4">
    <div class="card-header">Change Password</div>
    <div class="card-body">
      <form>
        <div class="mb-3">
          <label for="currentPassword" class="form-label">Current Password</label>
          <input type="password" class="form-control" id="currentPassword" placeholder="Enter current password">
        </div>
        <div class="mb-3">
          <label for="newPassword" class="form-label">New Password</label>
          <input type="password" class="form-control" id="newPassword" placeholder="Enter new password">
        </div>
        <div class="mb-3">
          <label for="confirmPassword" class="form-label">Confirm New Password</label>
          <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password">
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
      </form>
    </div>
  </div>

  <!-- 🖼️ Change Profile Picture -->
  <div class="card mb-4">
    <div class="card-header">Change Profile Picture</div>
    <div class="card-body">
      <form enctype="multipart/form-data">
        <div class="mb-3">
          <label for="profilePic" class="form-label">Upload New Profile Picture</label>
          <input class="form-control" type="file" id="profilePic" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
      </form>
    </div>
  </div>
</div>

<div class="d-flex justify-content-end mt-4">
  <button type="submit" class="btn btn-primary me-2">Save Changes</button>
  <button type="button" class="btn btn-secondary">Cancel</button>
</div>


<!-- Bootstrap JS + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
