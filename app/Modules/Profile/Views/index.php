<?php
/** @var array $profileData */
$p = $profileData ?? [];
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
$fullName = trim(($p['fname'] ?? '') . ' ' . ($p['lname'] ?? ''));
$hasCollege = isset($p['college']) && trim((string)$p['college']) !== '';
$hasProgram = isset($p['program']) && trim((string)$p['program']) !== '';
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10 col-xl-8">
      <div class="card shadow-sm rounded-3">
        <div class="card-body">

          <!-- Header: Avatar + Title -->
          <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
            <div class="position-relative pf-avatar-wrap">
              <img id="pf-avatar"
                   src="<?= $e($p['avatar'] ?? BASE_PATH . '/assets/images/user-default.svg') ?>"
                   alt="Avatar"
                   class="rounded-circle object-fit-cover pf-avatar">
              <button type="button" class="btn btn-light btn-sm rounded-circle pf-avatar-edit"
                      title="Change photo" id="pf-avatar-edit-btn">
                <i class="bi bi-pencil"></i>
              </button>
              <input id="pf-avatar-input" type="file" accept="image/*" class="d-none">
            </div>

            <div class="flex-grow-1">
              <h3 class="mb-1">User Profile</h3>
              <div class="text-muted"><?= $e($fullName ?: '—') ?></div>
            </div>

            <div class="ms-auto">
                <a href="<?= BASE_PATH ?>/dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle"></i> Back to Dashboard
                </a>
            </div>
          </div>

          <!-- Form (rows: label left, input right) -->
          <form id="pf-form" novalidate>

            <!-- ID Number -->
            <div class="row g-2 align-items-center mb-3">
              <div class="col-md-4 text-md-end fw-semibold">ID Number</div>
              <div class="col-md-8">
                <input type="text" class="form-control pf-readonly" value="<?= $e($p['id_no'] ?? '') ?>" disabled>
              </div>
            </div>

            <!-- First Name -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-4 text-md-end fw-semibold">First Name</div>
                <div class="col-md-8">
                    <input name="fname" type="text" class="form-control" placeholder="First name" value="<?= $e($p['fname'] ?? '') ?>">
                </div>
            </div>

            <!-- Middle Name -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-4 text-md-end fw-semibold">Middle Name</div>
                <div class="col-md-8">
                    <input name="mname" type="text" class="form-control" placeholder="Middle name" value="<?= $e($p['mname'] ?? '') ?>">
                </div>
            </div>

            <!-- Last Name -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-4 text-md-end fw-semibold">Last Name</div>
                <div class="col-md-8">
                    <input name="lname" type="text" class="form-control" placeholder="Last name" value="<?= $e($p['lname'] ?? '') ?>">
                </div>
            </div>

            <!-- Email -->
            <div class="row g-2 align-items-center mb-3">
              <div class="col-md-4 text-md-end fw-semibold">Email</div>
              <div class="col-md-8">
                <input name="email" type="email" class="form-control pf-readonly" value="<?= $e($p['email'] ?? '') ?>" disabled>
              </div>
            </div>

            <!-- Role -->
            <div class="row g-2 align-items-center mb-3">
              <div class="col-md-4 text-md-end fw-semibold">Role</div>
              <div class="col-md-8">
                <input type="text" class="form-control pf-readonly" value="<?= $e($p['role'] ?? '—') ?>" disabled>
              </div>
            </div>

            <!-- College -->
            <?php if ($hasCollege): ?>
            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-4 text-md-end fw-semibold">College</div>
                <div class="col-md-8">
                    <input type="text" class="form-control pf-readonly" value="<?= $e($p['college']) ?>" disabled>
                </div>
            </div>
            <?php endif; ?>

            <!-- Program -->
            <?php if ($hasProgram): ?>
            <div class="row g-2 align-items-center mb-4">
                <div class="col-md-4 text-md-end fw-semibold">Program</div>
                <div class="col-md-8">
                    <input type="text" class="form-control pf-readonly" value="<?= $e($p['program']) ?>" disabled>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-outline-secondary" id="pf-btn-cancel">Cancel</button>
              <button type="button" class="btn btn-primary" id="pf-btn-save">
                <i class="bi bi-save"></i> Save Changes
              </button>
              <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#pfChangePassModal" id="pf-btn-change-pass">
                <i class="bi bi-shield-lock"></i> Change Password
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="pfChangePassModal" tabindex="-1" aria-labelledby="pfChangePassModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="pfChangePassModalLabel">Change Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input id="pf-new-pass" type="password" class="form-control" placeholder="At least 6 characters">
        </div>
        <div class="mb-1">
          <label class="form-label">Repeat Password</label>
          <input id="pf-new-pass2" type="password" class="form-control" placeholder="Repeat new password">
        </div>
        <div class="form-text">This is a demo dialog. No backend call yet.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="pf-btn-submit-pass">Update Password</button>
      </div>
    </div>
  </div>
</div>

<!-- Module assets -->
<link rel="stylesheet" href="<?= BASE_PATH ?>/public/assets/css/Profile.css">
<script src="<?= BASE_PATH ?>/public/assets/js/Profile.js"></script>
