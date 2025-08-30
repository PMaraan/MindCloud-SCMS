#!/usr/bin/env php
<?php
// tools/make_module.php
declare(strict_types=1);

/**
 * Usage:
 *   php tools/make_module.php Accounts AccountViewing "Accounts"
 *   php tools/make_module.php Syllabus SyllabusViewing "Syllabus" --force
 *
 * Args:
 *   1) ModuleName (StudlyCase recommended, e.g., Accounts)
 *   2) ViewPermissionKey (e.g., AccountViewing) or "-" for none -> will default to {Module}Viewing
 *   3) Label (e.g., "Accounts") or omit to default to ModuleName
 *   --force (optional): overwrite existing files
 *
 * This scaffolder follows the project's canonical flow:
 * - Modules live in /app/Modules/{Module}/...
 * - Controllers compute RBAC booleans and pass them to views
 * - Views use partials and never query RBAC/DB directly
 * - Pagination preserves ?page={module}&pg=
 * - Model includes cross-DB pagination helpers
 */

function studly(string $s): string {
  $s = preg_replace('/[^a-z0-9]+/i',' ',$s);
  $s = ucwords(strtolower(trim($s)));
  return str_replace(' ', '', $s);
}

function upper_snake(string $s): string {
  $s = preg_replace('/[^A-Za-z0-9]+/', '_', $s);
  $s = preg_replace('/([a-z])([A-Z])/', '$1_$2', $s);
  return strtoupper(trim($s, '_'));
}

function ensure_dir(string $path): void {
  if (!is_dir($path)) {
    if (!@mkdir($path, 0775, true) && !is_dir($path)) {
      fwrite(STDERR, "Failed to create dir: $path\n");
      exit(1);
    }
  }
}

function write_file(string $file, string $content, bool $force): void {
  if (file_exists($file) && !$force) {
    echo "SKIP (exists): $file\n";
    return;
  }
  if (@file_put_contents($file, $content) === false) {
    fwrite(STDERR, "Failed to write file: $file\n");
    exit(1);
  }
  echo "WROTE: $file\n";
}

// ---- args ----
$args = $argv;
array_shift($args);
if (count($args) < 1) {
  echo "Usage: php tools/make_module.php ModuleName [ViewPermissionKey|-] [Label] [--force]\n";
  exit(1);
}

$moduleArg = $args[0] ?? '';
$viewPermArg = $args[1] ?? '-';
$labelArg    = $args[2] ?? '';
$force       = in_array('--force', $args, true);

$Module = studly($moduleArg);
if ($Module === '') {
  fwrite(STDERR, "Invalid ModuleName.\n");
  exit(1);
}
$module = strtolower($Module);
$PREFIX = upper_snake($Module);

// Permission key strings
$defaultViewKey = $Module . 'Viewing';
$viewKey = ($viewPermArg !== '-' && $viewPermArg !== '') ? $viewPermArg : $defaultViewKey;

// Derive create/edit/delete strings if possible
$deriveBase = $viewKey;
if (str_ends_with($viewKey, 'Viewing')) {
  $createKey = substr($viewKey, 0, -7) . 'Creation';
  $editKey   = substr($viewKey, 0, -7) . 'Modification';
  $deleteKey = substr($viewKey, 0, -7) . 'Deletion';
} else {
  // fallback patterns
  $createKey = $Module . 'Creation';
  $editKey   = $Module . 'Modification';
  $deleteKey = $Module . 'Deletion';
}

$Label = ($labelArg !== '') ? $labelArg : $Module;

// ---- paths ----
$root     = dirname(__DIR__);
$base     = $root . '/app/Modules/' . $Module;
$ctrlDir  = $base . '/Controllers';
$modelDir = $base . '/Models';
$viewsDir = $base . '/Views';
$partsDir = $viewsDir . '/partials';

// ---- dirs ----
ensure_dir($ctrlDir);
ensure_dir($modelDir);
ensure_dir($viewsDir);
ensure_dir($partsDir);

// ---- controller ----
$controller = <<<PHP
<?php
// app/Modules/{$Module}/Controllers/{$Module}Controller.php
declare(strict_types=1);

namespace App\\Modules\\{$Module}\\Controllers;

use App\\Interfaces\\StorageInterface;
use App\\Security\\RBAC;
use App\\Helpers\\FlashHelper;

final class {$Module}Controller
{
    private StorageInterface \$db;

    public function __construct(StorageInterface \$db) {
        \$this->db = \$db;
        if (session_status() !== \\PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function index(): string {
        // Resolve registry (for permission keys & actions)
        \$registryPath = dirname(__DIR__, 4) . '/config/ModuleRegistry.php';
        \$registry = is_file(\$registryPath) ? require \$registryPath : [];
        \$def = \$registry['{$module}'] ?? [];

        // Gate view permission if configured
        if (!empty(\$def['permission'])) {
            (new RBAC(\$this->db))->require((string)\$_SESSION['user_id'], (string)\$def['permission']);
        }

        // Compute action gates (DB each request; no caching)
        \$uid = (string)(\$_SESSION['user_id'] ?? '');
        \$rbac = new RBAC(\$this->db);
        \$actions = (array)(\$def['actions'] ?? []);
        \$canCreate = isset(\$actions['create']) && \$rbac->has(\$uid, (string)\$actions['create']);
        \$canEdit   = isset(\$actions['edit'])   && \$rbac->has(\$uid, (string)\$actions['edit']);
        \$canDelete = isset(\$actions['delete']) && \$rbac->has(\$uid, (string)\$actions['delete']);

        // Optional search + pagination skeleton (you can remove if not needed)
        \$rawQ   = isset(\$_GET['q']) ? trim((string)\$_GET['q']) : null;
        \$search = (\$rawQ !== null && \$rawQ !== '') ? mb_strtolower(\$rawQ) : null;
        \$page    = max(1, (int)(\$_GET['pg'] ?? 1));
        \$perPage = 10;
        \$offset  = (\$page - 1) * \$perPage;

        // TODO: call your model here when ready
        // \$model = new \\App\\Modules\\{$Module}\\Models\\{$Module}Model(\$this->db);
        // \$result = \$model->getPage(\$search, \$perPage, \$offset);
        // \$rows   = \$result['rows'];
        // \$total  = \$result['total'];

        // Placeholder empty list
        \$rows  = [];
        \$total = 0;

        \$pages = max(1, (int)ceil(\$total / \$perPage));
        \$pager = [
            'page'     => \$page,
            'perPage'  => \$perPage,
            'total'    => \$total,
            'pages'    => \$pages,
            'hasPrev'  => \$page > 1,
            'hasNext'  => \$page < \$pages,
            'prev'     => max(1, \$page - 1),
            'next'     => min(\$pages, \$page + 1),
            // Preserve module key in all links:
            'baseUrl'  => BASE_PATH . '/dashboard?page={$module}',
            'query'    => \$rawQ,
        ];

        // Expose to view
        \$data = [
            'rows'      => \$rows,
            'pager'     => \$pager,
            'canCreate' => \$canCreate,
            'canEdit'   => \$canEdit,
            'canDelete' => \$canDelete,
        ];
        extract(\$data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    // Example stubs (uncomment/implement when you need them):
    // public function create(): void {}
    // public function edit(): void {}
    // public function delete(): void {}
}
PHP;

// ---- model (with cross-DB pagination helper) ----
$model = <<<PHP
<?php
// app/Modules/{$Module}/Models/{$Module}Model.php
declare(strict_types=1);

namespace App\\Modules\\{$Module}\\Models;

use App\\Interfaces\\StorageInterface;
use PDO;

final class {$Module}Model
{
    private PDO \$pdo;
    private string \$driver;

    public function __construct(StorageInterface \$db) {
        \$this->pdo    = \$db->getConnection();
        \$this->driver = (string)\$this->pdo->getAttribute(\\PDO::ATTR_DRIVER_NAME); // 'pgsql','mysql','sqlsrv','oci',...
    }

    private function limitOffsetClause(int \$limit, int \$offset): string
    {
        \$limit  = max(1, (int)\$limit);
        \$offset = max(0, (int)\$offset);

        return match (\$this->driver) {
            'pgsql','mysql' => "LIMIT {\$limit} OFFSET {\$offset}",
            'sqlsrv','oci'  => "OFFSET {\$offset} ROWS FETCH NEXT {\$limit} ROWS ONLY",
            default         => "LIMIT {\$limit} OFFSET {\$offset}",
        };
    }

    /**
     * Example paged query skeleton; change the FROM/SELECT for your module.
     * Returns ['rows'=>array, 'total'=>int].
     */
    public function getPage(?string \$q, int \$limit, int \$offset): array
    {
        \$limit  = max(1, (int)\$limit);
        \$offset = max(0, (int)\$offset);

        \$where  = ' WHERE 1=1 ';
        \$params = [];

        if (\$q !== null && \$q !== '') {
            \$where .= " AND (LOWER(t.name) LIKE :q OR LOWER(t.code) LIKE :q)";
            \$params[':q'] = '%' . \$q . '%';
        }

        // Count
        \$sqlCount = "SELECT COUNT(*) FROM your_table t" . \$where;
        \$stmt = \$this->pdo->prepare(\$sqlCount);
        foreach (\$params as \$k => \$v) \$stmt->bindValue(\$k, \$v);
        \$stmt->execute();
        \$total = (int)\$stmt->fetchColumn();

        // Rows
        \$pageClause = \$this->limitOffsetClause(\$limit, \$offset);
        \$sqlList = "
            SELECT t.*
            FROM your_table t
            " . \$where . "
            ORDER BY t.name ASC
            " . \$pageClause;

        \$stmt2 = \$this->pdo->prepare(\$sqlList);
        foreach (\$params as \$k => \$v) \$stmt2->bindValue(\$k, \$v);
        \$stmt2->execute();

        \$rows = \$stmt2->fetchAll(\\PDO::FETCH_ASSOC) ?: [];
        return ['rows' => \$rows, 'total' => \$total];
    }
}
PHP;

// ---- views ----
$index = <<<PHP
<?php /* app/Modules/{$Module}/Views/index.php */ ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">{$Label}</h2>

    <form class="d-flex" method="GET" action="<?= BASE_PATH ?>/dashboard">
      <input type="hidden" name="page" value="{$module}">
      <input class="form-control me-2" type="search" name="q" placeholder="Search..." aria-label="Search"
             value="<?= htmlspecialchars(\$pager['query'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn btn-outline-primary" type="submit">Search</button>
    </form>

    <?php if (!empty(\$canCreate)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        + Create
      </button>
    <?php endif; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">
      <?php
        \$from = \$pager['total'] === 0 ? 0 : ((\$pager['page'] - 1) * \$pager['perPage'] + 1);
        \$to   = min(\$pager['total'], \$pager['page'] * \$pager['perPage']);
      ?>
      Showing <?= \$from ?>-<?= \$to ?> of <?= (int)\$pager['total'] ?>
      <?php if (!empty(\$pager['query'])): ?>
        for “<?= htmlspecialchars(\$pager['query'], ENT_QUOTES, 'UTF-8') ?>”
      <?php endif; ?>
    </div>
    <div>
      <?php include __DIR__ . '/partials/Pagination.php'; ?>
    </div>
  </div>

  <?php include __DIR__ . '/partials/Table.php'; ?>

  <div class="d-flex justify-content-end mt-3">
    <?php include __DIR__ . '/partials/Pagination.php'; ?>
  </div>

  <?php if (!empty(\$canCreate)) include __DIR__ . '/partials/CreateModal.php'; ?>
  <?php if (!empty(\$canEdit))   include __DIR__ . '/partials/EditModal.php'; ?>
  <?php if (!empty(\$canDelete)) include __DIR__ . '/partials/DeleteModal.php'; ?>
</div>
PHP;

$pagination = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/Pagination.php */ ?>
<?php
// expects \$pager = ['page','pages','hasPrev','hasNext','prev','next','baseUrl','query']
\$pg   = (int)\$pager['page'];
\$last = (int)\$pager['pages'];
\$base = (string)\$pager['baseUrl'];
\$q    = \$pager['query'] ?? null;

\$u = function (int \$p) use (\$base, \$q): string {
  \$params = ['pg' => \$p];
  if (\$q !== null && \$q !== '') \$params['q'] = \$q;
  \$sep = (str_contains(\$base, '?') ? '&' : '?');
  return \$base . \$sep . http_build_query(\$params);
};

\$window = 2;
\$start  = max(1, \$pg - \$window);
\$end    = min(\$last, \$pg + \$window);
?>
<nav aria-label="Pagination">
  <ul class="pagination mb-0">
    <li class="page-item <?= \$pager['hasPrev'] ? '' : 'disabled' ?>">
      <a class="page-link" href="<?= \$pager['hasPrev'] ? \$u(\$pager['prev']) : '#' ?>" tabindex="-1">«</a>
    </li>

    <?php if (\$start > 1): ?>
      <li class="page-item"><a class="page-link" href="<?= \$u(1) ?>">1</a></li>
      <?php if (\$start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
    <?php endif; ?>

    <?php for (\$i = \$start; \$i <= \$end; \$i++): ?>
      <li class="page-item <?= \$i === \$pg ? 'active' : '' ?>">
        <a class="page-link" href="<?= \$u(\$i) ?>"><?= \$i ?></a>
      </li>
    <?php endfor; ?>

    <?php if (\$end < \$last): ?>
      <?php if (\$end < \$last - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <li class="page-item"><a class="page-link" href="<?= \$u(\$last) ?>"><?= \$last ?></a></li>
    <?php endif; ?>

    <li class="page-item <?= \$pager['hasNext'] ? '' : 'disabled' ?>">
      <a class="page-link" href="<?= \$pager['hasNext'] ? \$u(\$pager['next']) : '#' ?>">»</a>
    </li>
  </ul>
</nav>
PHP;

$table = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/Table.php */ ?>
<?php
// expects: \$rows (array), \$canEdit (bool), \$canDelete (bool)
?>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Code</th>
        <th style="width:140px;" class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!empty(\$rows)): ?>
      <?php foreach (\$rows as \$r): ?>
        <tr>
          <td><?= htmlspecialchars((string)(\$r['id'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)(\$r['name'] ?? ''), ENT_QUOTES) ?></td>
          <td><?= htmlspecialchars((string)(\$r['code'] ?? ''), ENT_QUOTES) ?></td>
          <td class="text-end">
            <?php if (\$canEdit): ?>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="bi bi-pencil"></i> Edit
              </button>
            <?php endif; ?>
            <?php if (\$canDelete): ?>
              <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="bi bi-trash"></i> Delete
              </button>
            <?php endif; ?>
            <?php if (!\$canEdit && !\$canDelete): ?>
              <span class="text-muted">No action</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="4" class="text-center">No records found.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
PHP;

$createModal = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/CreateModal.php */ ?>
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page={$module}&action=create" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
        <div class="modal-header">
          <h5 class="modal-title">Create</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- form fields -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Create</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
PHP;

$editModal = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/EditModal.php */ ?>
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page={$module}&action=edit" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
        <div class="modal-header">
          <h5 class="modal-title">Edit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- form fields -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
PHP;

$deleteModal = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/DeleteModal.php */ ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="<?= BASE_PATH ?>/dashboard?page={$module}&action=delete">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(\$_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
        <div class="modal-header">
          <h5 class="modal-title">Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0">Are you sure you want to delete this record?</p>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Delete</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
PHP;

// ---- write files ----
write_file("{$ctrlDir}/{$Module}Controller.php", $controller, $force);
write_file("{$modelDir}/{$Module}Model.php", $model, $force);
write_file("{$viewsDir}/index.php", $index, $force);
write_file("{$partsDir}/Pagination.php", $pagination, $force);
write_file("{$partsDir}/Table.php", $table, $force);
write_file("{$partsDir}/CreateModal.php", $createModal, $force);
write_file("{$partsDir}/EditModal.php", $editModal, $force);
write_file("{$partsDir}/DeleteModal.php", $deleteModal, $force);

// ---- output registry + permission snippets ----
$permConstView   = "{$PREFIX}_VIEW";
$permConstCreate = "{$PREFIX}_CREATE";
$permConstEdit   = "{$PREFIX}_EDIT";
$permConstDelete = "{$PREFIX}_DELETE";

echo PHP_EOL;
echo "Add this to config/ModuleRegistry.php (inside the returned array):" . PHP_EOL;
echo "--------------------------------------------------" . PHP_EOL;
echo "use App\\Modules\\{$Module}\\Controllers\\{$Module}Controller;" . PHP_EOL;
echo "use App\\Config\\Permissions;" . PHP_EOL . PHP_EOL;
echo "  '{$module}' => [" . PHP_EOL;
echo "    'label'      => " . var_export($Label, true) . "," . PHP_EOL;
echo "    'permission' => Permissions::{$permConstView}," . PHP_EOL;
echo "    'controller' => {$Module}Controller::class," . PHP_EOL;
echo "    'actions'    => [" . PHP_EOL;
echo "      'create' => Permissions::{$permConstCreate}," . PHP_EOL;
echo "      'edit'   => Permissions::{$permConstEdit}," . PHP_EOL;
echo "      'delete' => Permissions::{$permConstDelete}," . PHP_EOL;
echo "    ]," . PHP_EOL;
echo "  ]," . PHP_EOL;
echo "--------------------------------------------------" . PHP_EOL;

echo PHP_EOL;
echo "Add these constants to /app/Config/Permissions.php (and map to your DB permission_name):" . PHP_EOL;
echo "--------------------------------------------------" . PHP_EOL;
echo "public const {$permConstView}   = " . var_export($viewKey, true) . ";" . PHP_EOL;
echo "public const {$permConstCreate} = " . var_export($createKey, true) . ";" . PHP_EOL;
echo "public const {$permConstEdit}   = " . var_export($editKey, true) . ";" . PHP_EOL;
echo "public const {$permConstDelete} = " . var_export($deleteKey, true) . ";" . PHP_EOL;
echo "--------------------------------------------------" . PHP_EOL;

echo PHP_EOL . "Done." . PHP_EOL;
