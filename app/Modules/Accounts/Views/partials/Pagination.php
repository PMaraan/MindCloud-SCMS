<?php
// /app/Modules/Accounts/Views/partials/Pagination.php
// expects $pager array with keys:
// page, pages, hasPrev, hasNext, prev, next, baseUrl, query

$pg   = (int)$pager['page'];
$last = (int)$pager['pages'];
$base = (string)$pager['baseUrl']; // e.g. BASE_PATH . '/dashboard?page=accounts'
$q    = $pager['query'] ?? null;

// Helper: build URL preserving page=accounts and q, changing only pg
$u = function (int $p) use ($base, $q): string {
    // $base should already include '?page=accounts'
    // We only add/replace pg and optionally q
    $params = ['pg' => $p];
    if ($q !== null && $q !== '') {
        $params['q'] = $q;
    }
    // Decide separator
    $sep = (str_contains($base, '?') ? '&' : '?');
    return $base . $sep . http_build_query($params);
};

// Optional compact window
$window = 2;
$start  = max(1, $pg - $window);
$end    = min($last, $pg + $window);
?>
<nav aria-label="Accounts pagination">
  <ul class="pagination mb-0">
    <li class="page-item <?= $pager['hasPrev'] ? '' : 'disabled' ?>">
      <a class="page-link" href="<?= $pager['hasPrev'] ? $u($pager['prev']) : '#' ?>" tabindex="-1" aria-disabled="<?= $pager['hasPrev'] ? 'false' : 'true' ?>">«</a>
    </li>

    <?php if ($start > 1): ?>
      <li class="page-item"><a class="page-link" href="<?= $u(1) ?>">1</a></li>
      <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
      <li class="page-item <?= $i === $pg ? 'active' : '' ?>">
        <a class="page-link" href="<?= $u($i) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>

    <?php if ($end < $last): ?>
      <?php if ($end < $last - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <li class="page-item"><a class="page-link" href="<?= $u($last) ?>"><?= $last ?></a></li>
    <?php endif; ?>

    <li class="page-item <?= $pager['hasNext'] ? '' : 'disabled' ?>">
      <a class="page-link" href="<?= $pager['hasNext'] ? $u($pager['next']) : '#' ?>">»</a>
    </li>
  </ul>
</nav>
