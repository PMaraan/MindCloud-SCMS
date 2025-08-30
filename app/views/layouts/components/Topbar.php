<!-- header component -->
<nav class="navbar px-3 sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#">
      <img src="<?= $basePath . "/public/assets/images/logo_lpu.png" ?>" alt="LPU Icon" class="lpu-icon">
      <span class="brand-text">LPU-SCMS</span>
    </a>
    <ul class="navbar-nav ms-auto d-flex flex-row">
      <li class="nav-item me-4">
        <a class="header-link" href="/app/views/Notification.php" title="Notifications">
          <i class="bi bi-bell fs-4"></i>
        </a>
      </li>
      <li class="nav-item me-4">
        <a class="header-link" href="#" title="Settings">
          <i class="bi bi-gear fs-4"></i>
        </a>
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
<?php
/*
<li class="nav-item">
        <a class="header-link" href="<?= "$basePath/app/lib/logout.php" ?>" title="Logout">
          <i class="bi bi-box-arrow-right fs-4"></i>
        </a>
      </li>
*/
?>