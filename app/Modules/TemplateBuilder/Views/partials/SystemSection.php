<?php
// /app/Modules/TemplateBuilder/Views/partials/SystemSection.php
if (!function_exists('renderSystemSection')) {
  function renderSystemSection(array $data, callable $esc): void {
    $global   = $data['global']   ?? [];
    $colleges = $data['colleges'] ?? [];

    // Global/General
    echo '<div class="card mb-4">';
    echo '  <div class="card-header"><strong>Global / General Templates</strong></div>';
    echo '  <div class="card-body">';
    renderTemplateGrid($global, $esc);
    echo '  </div>';
    echo '</div>';

    // Each college (includes its own general + programs)
    foreach ($colleges as $cSec) {
      renderCollegeSection($cSec, $esc);
    }
  }
}
