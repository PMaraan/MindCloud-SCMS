<?php /* app/Modules/Notifications/Views/index.php */
$csrf   = $_SESSION['csrf_token'] ?? '';
$status = $pager['status'] ?? 'all';
$pg     = (int)($pager['pg'] ?? 1);
$pages  = (int)($pager['pages'] ?? 1);
$base   = (string)($pager['baseUrl'] ?? (BASE_PATH . '/dashboard?page=notifications'));

function mc_url_with(string $base, array $qs): string {
    $q = [];
    foreach ($qs as $k => $v) $q[] = urlencode((string)$k) . '=' . urlencode((string)$v);
    return $base . '&' . implode('&', $q);
}
$globalPagination = dirname(__DIR__, 3) . '/Views/partials/Pagination.php';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Notifications</h2>

    <div class="btn-group" role="group" aria-label="Filter">
      <a class="btn btn-outline-secondary<?= $status==='all'?' active':'' ?>"
         href="<?= htmlspecialchars(mc_url_with($base, ['status'=>'all','pg'=>1])) ?>">All</a>
      <a class="btn btn-outline-secondary<?= $status==='unread'?' active':'' ?>"
         href="<?= htmlspecialchars(mc_url_with($base, ['status'=>'unread','pg'=>1])) ?>">Unread</a>
      <a class="btn btn-outline-secondary<?= $status==='read'?' active':'' ?>"
         href="<?= htmlspecialchars(mc_url_with($base, ['status'=>'read','pg'=>1])) ?>">Read</a>
    </div>
  </div>

  <?php include $globalPagination; ?>

  <div class="card shadow-sm">
    <div class="list-group list-group-flush">
      <?php if (!empty($rows)): ?>
        <?php foreach ($rows as $n): ?>
          <?php
            $isUnread = empty($n['is_read']) || $n['is_read'] === false || $n['is_read'] === 'f';
            $url = (string)($n['url'] ?? '#');
          ?>
          <div class="list-group-item d-flex justify-content-between align-items-start <?= $isUnread ? 'bg-light' : '' ?>">
            <div class="me-3 flex-grow-1">
              <div class="d-flex align-items-center gap-2">
                <a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" class="fw-semibold text-decoration-none text-body">
                  <?= htmlspecialchars((string)($n['title'] ?? '(no title)'), ENT_QUOTES) ?>
                </a>
                <?php if ($isUnread): ?>
                  <span class="badge bg-danger">New</span>
                <?php endif; ?>
                <span class="text-muted small ms-auto">
                  <?= htmlspecialchars((string)($n['created_at'] ?? ''), ENT_QUOTES) ?>
                </span>
              </div>
              <?php if (!empty($n['body'])): ?>
                <div class="text-muted small mt-1">
                  <?= htmlspecialchars((string)$n['body'], ENT_QUOTES) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="list-group-item text-center text-muted py-5">
          No notifications to show.
        </div>
      <?php endif; ?>
    </div>
  </div>

  
</div>

<?php include $globalPagination; ?>
