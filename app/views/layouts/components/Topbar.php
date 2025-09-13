<!-- header component -->
<nav class="navbar px-3 sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#">
      <img src="<?= $basePath . "/public/assets/images/logo_lpu.png" ?>" alt="LPU Icon" class="lpu-icon">
      <span class="brand-text">LPU-SCMS</span>
    </a>

    <ul class="navbar-nav ms-auto d-flex flex-row">
      <li class="nav-item dropdown">
        <a
          class="header-link position-relative"
          href="#"
          id="notifDropdown"
          role="button"
          data-bs-toggle="dropdown"
          data-bs-auto-close="outside"
          data-bs-display="static"
          aria-expanded="false"
          title="Notifications"
          data-base-path="<?= $basePath ?>"
        >
          <i class="bi bi-bell fs-4"></i>
          <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end p-0 shadow" aria-labelledby="notifDropdown" style="width: 360px;">
          <li id="notif-loading" class="py-3 text-center text-muted small">Loading…</li>
          <li id="notif-items" class=""></li>
          <li class="border-top">
            <a class="dropdown-item text-center fw-semibold" href="<?= $basePath ?>/dashboard?page=notifications">
              Show all notifications
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item dropdown">
        <a
          class="header-link dropdown-toggle d-flex align-items-center"
          href="#"
          id="settingsDropdown"
          role="button"
          data-bs-toggle="dropdown"
          data-bs-auto-close="outside"
          aria-expanded="false"
          title="Settings"
        >
          <i class="bi bi-gear fs-4"></i>
        </a>

        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
          <li>
            <button class="dropdown-item d-flex justify-content-between align-items-center" id="toggleDarkMode" type="button">
              <span>Dark Mode</span>
              <i class="bi bi-moon-stars"></i>
            </button>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <form method="POST" action="<?= $basePath ?>/logout" class="d-inline">
          <button class="header-link btn btn-link p-0 m-0 border-0" type="submit" title="Logout">
            <i class="bi bi-box-arrow-right fs-4"></i>
          </button>
        </form>
      </li>

    </ul>
  </div>
</nav>

<script>window.__BASE_PATH__ = '<?= rtrim($basePath ?? '', '/') ?>';</script>
<script src="<?= rtrim($basePath ?? '', '/') ?>/public/assets/js/settings.js" defer></script>
