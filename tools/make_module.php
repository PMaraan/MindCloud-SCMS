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
 *   2) PermissionKey (e.g., AccountViewing) or "-" for none
 *   3) Label (e.g., "Accounts") or omit to default to ModuleName
 *   --force (optional): overwrite existing files
 */

function studly(string $s): string {
  $s = preg_replace('/[^a-z0-9]+/i',' ',$s);
  $s = ucwords(strtolower(trim($s)));
  return str_replace(' ', '', $s);
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

$args = $argv;
array_shift($args);
if (count($args) < 1) {
  echo "Usage: php tools/make_module.php ModuleName [PermissionKey|-] [Label] [--force]\n";
  exit(1);
}

$moduleArg = $args[0] ?? '';
$permKey   = $args[1] ?? '-';
$label     = $args[2] ?? '';
$force     = in_array('--force', $args, true);

$Module = studly($moduleArg);
$module = strtolower($Module);

// Resolve paths
$root     = dirname(__DIR__);
$base     = $root . '/app/Modules/' . $Module;
$ctrlDir  = $base . '/Controllers';
$modelDir = $base . '/Models';
$viewsDir = $base . '/Views';
$partsDir = $viewsDir . '/partials';

// Create dirs
ensure_dir($ctrlDir);
ensure_dir($modelDir);
ensure_dir($viewsDir);
ensure_dir($partsDir);

$permPhp  = ($permKey !== '-' && $permKey !== '') ? var_export($permKey, true) : 'null';
$labelPhp = ($label !== '') ? var_export($label, true) : var_export($Module, true);

// Controller
$controller = <<<PHP
<?php
// app/Modules/{$Module}/Controllers/{$Module}Controller.php
declare(strict_types=1);

namespace App\\Modules\\{$Module}\\Controllers;

use App\\Interfaces\\StorageInterface;
use App\\Helpers\\FlashHelper;
use App\\Security\\RBAC;

final class {$Module}Controller
{
    private StorageInterface \$db;

    public function __construct(StorageInterface \$db) {
        \$this->db = \$db;
        if (session_status() !== \\PHP_SESSION_ACTIVE) session_start();
    }

    public function index(): string {
        // Gate with view permission if needed
        // (new RBAC(\$this->db))->require((string)\$_SESSION['user_id'], '{$permKey}');

        // Prepare data here...

        ob_start();
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    // public function create(): void {}
    // public function edit(): void {}
    // public function delete(): void {}
}
PHP;

// Model
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
    public function __construct(StorageInterface \$db) {
        \$this->pdo = \$db->getConnection();
    }

    // Add queries here...
}
PHP;

// index view
$index = <<<PHP
<?php /* app/Modules/{$Module}/Views/index.php */ ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">{$labelPhp}</h2>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="mb-0">{$Module} module scaffolded successfully.</p>
    </div>
  </div>
</div>
PHP;

// partials
$pagination = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/Pagination.php */ ?>
<!-- Pagination scaffold -->
PHP;

$table = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/Table.php */ ?>
<!-- Table scaffold -->
PHP;

$createModal = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/CreateModal.php */ ?>
<!-- Create modal scaffold -->
PHP;

$editModal = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/EditModal.php */ ?>
<!-- Edit modal scaffold -->
PHP;

$deleteModal = <<<PHP
<?php /* app/Modules/{$Module}/Views/partials/DeleteModal.php */ ?>
<!-- Delete modal scaffold -->
PHP;

// Write files
write_file("{$ctrlDir}/{$Module}Controller.php", $controller, $force);
write_file("{$modelDir}/{$Module}Model.php", $model, $force);
write_file("{$viewsDir}/index.php", $index, $force);
write_file("{$partsDir}/Pagination.php", $pagination, $force);
write_file("{$partsDir}/Table.php", $table, $force);
write_file("{$partsDir}/CreateModal.php", $createModal, $force);
write_file("{$partsDir}/EditModal.php", $editModal, $force);
write_file("{$partsDir}/DeleteModal.php", $deleteModal, $force);

// Suggest ModuleRegistry entry
echo PHP_EOL . "Add this to config/ModuleRegistry.php:" . PHP_EOL;
echo "--------------------------------------------------" . PHP_EOL;
echo "use App\\Modules\\{$Module}\\Controllers\\{$Module}Controller;" . PHP_EOL . PHP_EOL;
echo "return array_merge(require __DIR__ . '/ModuleRegistry.php', [" . PHP_EOL;
echo "  '{$module}' => [" . PHP_EOL;
echo "    'label'      => {$labelPhp}," . PHP_EOL;
echo "    'permission' => {$permPhp}," . PHP_EOL;
echo "    'controller' => {$Module}Controller::class," . PHP_EOL;
echo "  ]," . PHP_EOL;
echo "]);" . PHP_EOL;
echo "--------------------------------------------------" . PHP_EOL;

echo PHP_EOL . "Done." . PHP_EOL;
PHP;
