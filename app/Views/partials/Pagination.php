<?php
// /app/Views/partials/Pagination.php
// Expects $pager with keys:
//   required: 'total', 'pg', 'perpage', 'baseUrl'
//   optional: 'query' (string), 'extra' (assoc array), 'from', 'to'

$total   = (int)($pager['total']   ?? 0);
$pg      = max(1, (int)($pager['pg']      ?? 1));
$perpage = max(1, (int)($pager['perpage'] ?? 10));
$pages   = (int)ceil($total / $perpage);

$base  = (string)($pager['baseUrl'] ?? '');
$query = trim((string)($pager['query'] ?? ''));
$extra = (isset($pager['extra']) && is_array($pager['extra'])) ? $pager['extra'] : [];

// Compute X–Y if not provided
$from = isset($pager['from']) ? (int)$pager['from'] : ($total > 0 ? (($pg - 1) * $perpage + 1) : 0);
$to   = isset($pager['to'])   ? (int)$pager['to']   : ($total > 0 ? min($total, $pg * $perpage) : 0);

// URL builder (uses &pg=... to avoid clashing with your router's ?page=module)
$mk = function (int $p) use ($base, $query, $extra) {
    $url = $base . '&pg=' . $p;
    if ($query !== '') $url .= '&q=' . urlencode($query);
    if (!empty($extra)) {
        foreach ($extra as $k => $v) {
            $url .= '&' . urlencode((string)$k) . '=' . urlencode((string)$v);
        }
    }
    return $url;
};

// Compact window for page numbers
$window = 2;
$start  = max(1, $pg - $window);
$end    = min($pages, $pg + $window);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 my-2">
  <div class="text-muted small">
    Showing <strong><?= $from ?></strong>–<strong><?= $to ?></strong>
    of <strong><?= $total ?></strong>
  </div>

  <?php if ($pages > 1): ?>
    <nav aria-label="Pagination">
      <ul class="pagination mb-0">
        <li class="page-item <?= $pg <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $pg <= 1 ? '#' : $mk($pg - 1) ?>" tabindex="-1">Prev</a>
        </li>

        <?php if ($start > 1): ?>
          <li class="page-item"><a class="page-link" href="<?= $mk(1) ?>">1</a></li>
          <?php if ($start > 2): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i === $pg ? 'active' : '' ?>">
            <a class="page-link" href="<?= $mk($i) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($end < $pages): ?>
          <?php if ($end < $pages - 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
          <?php endif; ?>
          <li class="page-item"><a class="page-link" href="<?= $mk($pages) ?>"><?= $pages ?></a></li>
        <?php endif; ?>

        <li class="page-item <?= $pg >= $pages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $pg >= $pages ? '#' : $mk($pg + 1) ?>">Next</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>
