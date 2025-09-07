<?php
// expects: $pager = ['total','page','limit','baseUrl','query']
$total = (int)($pager['total'] ?? 0);
$page  = max(1, (int)($pager['page'] ?? 1));
$limit = max(1, (int)($pager['limit'] ?? 10));
$pages = (int)ceil($total / $limit);
if ($pages <= 1) return;

$q = trim((string)($pager['query'] ?? ''));
$base = (string)$pager['baseUrl'];
$mk = function(int $p) use ($base, $q) {
    $url = $base . '&pg=' . $p;
    if ($q !== '') $url .= '&q=' . urlencode($q);
    return $url;
};
?>
<nav class="my-2" aria-label="Pagination">
  <ul class="pagination mb-0">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $page <= 1 ? '#' : $mk($page-1) ?>" tabindex="-1">Prev</a>
    </li>

    <?php
    // compact pagination window
    $window = 2;
    $start = max(1, $page - $window);
    $end   = min($pages, $page + $window);
    if ($start > 1) {
        echo '<li class="page-item"><a class="page-link" href="'.$mk(1).'">1</a></li>';
        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $page ? 'active' : '';
        echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$mk($i).'">'.$i.'</a></li>';
    }
    if ($end < $pages) {
        if ($end < $pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo '<li class="page-item"><a class="page-link" href="'.$mk($pages).'">'.$pages.'</a></li>';
    }
    ?>

    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
      <a class="page-link" href="<?= $page >= $pages ? '#' : $mk($page+1) ?>">Next</a>
    </li>
  </ul>
</nav>
