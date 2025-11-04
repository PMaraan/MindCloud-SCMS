<?php
/**
 * /app/Modules/Syllabi/Views/partials/SystemSection.php
 * Renders a top-level section: a global/all-syllabi grid (if provided),
 * followed by per-college sections (each with college-wide + per-program syllabi).
 *
 * Expects $data shaped like:
 * [
 *   'global'   => list<syllabus rows>,            // optional
 *   'colleges' => list<[
 *       'college'  => array{ short_name, college_name, ... },
 *       'general'  => list<syllabus rows>,        // college-wide
 *       'programs' => list<['program'=>{...}, 'syllabi'=>list<rows>>]
 *   ]>
 * ]
 *
 * Also expects: $esc (callable) HTML escaper
 */

if (!function_exists('renderSyllabiGrid')) {
  function renderSyllabiGrid(array $rows, callable $esc): void {
    // Reuse the Syllabi grid partial; it accepts $rows_local
    $rows_local = $rows;
    include __DIR__ . '/Grid.php';
  }
}

if (!function_exists('renderSyllabiCollegeSection')) {
  function renderSyllabiCollegeSection(array $section, callable $esc): void {
    // CollegeSection.php expects $college, $general, $programs
    $college  = $section['college']  ?? [];
    $general  = $section['general']  ?? [];
    $programs = $section['programs'] ?? [];
    include __DIR__ . '/CollegeSection.php';
  }
}

if (!function_exists('renderSyllabiSystemSection')) {
  function renderSyllabiSystemSection(array $data, callable $esc): void {
    $global   = $data['global']   ?? [];
    $colleges = $data['colleges'] ?? [];

    // Global / All Syllabi (optional)
    if (!empty($global)) {
      echo '<div class="card mb-4">';
      echo '  <div class="card-header"><strong>All Syllabi</strong></div>';
      echo '  <div class="card-body">';
      renderSyllabiGrid($global, $esc);
      echo '  </div>';
      echo '</div>';
    }

    // Each college (includes its own college-wide + per-program syllabi)
    foreach ($colleges as $cSec) {
      renderSyllabiCollegeSection($cSec, $esc);
    }
  }
}
