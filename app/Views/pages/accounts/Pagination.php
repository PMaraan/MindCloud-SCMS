<?php
// expects $pager array
$pg   = $pager['page'];
$last = $pager['pages'];
$base = $pager['baseUrl'];
$q    = $pager['query'] ?? null;

// Build a helper for URLs that preserves q=
$u = function (int $p) use ($base, $q): string {
    $params = ['page' => 'accounts', 'pg' => $p];
    if ($q !== null && $q !== '') $params['q'] = $q;
    // We already have baseUrl with ?page=accounts, so we just append &...
    $tail = http_build_query(['pg' => $p] + ($q ? ['q' => $q] : []));
    return $base . (strpos($base, '?') === false ? '?' : '&') . $tail;
};

// Optional: compact page window (e.g., current ±2)
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
