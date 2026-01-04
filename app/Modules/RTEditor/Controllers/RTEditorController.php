<?php
//  /app/Modules/RTEditor/Controllers/RTEditorController.php
declare(strict_types=1);
/**
 * RTEditorController
 * Path: /app/Modules/RTEditor/Controllers/RTEditorController.php
 *
 * Purpose:
 *  - Open a template/syllabus into the TipTap editor
 *  - Emit initial JSON payload/meta via the view (rt-initial-json / rt-meta)
 *  - Snapshot (save) TipTap JSON back to DB (templates/syllabi)
 *
 * Notes:
 *  - Uses RTEditorModel (DB-agnostic, PDO)
 *  - No inline <script id="rt-loaded-content"> is emitted anymore
 */
namespace App\Modules\RTEditor\Controllers;

use App\Interfaces\StorageInterface;
use App\Modules\RTEditor\Models\RTEditorModel;
use App\Helpers\FlashHelper;
use App\Security\RBAC;
use App\Config\Permissions;

final class RTEditorController
{
    private StorageInterface $db;

    public function __construct(StorageInterface $db)
    {
        $this->db = $db; // not used yet
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Minimal render: provide view variables and include the view.
     * No RBAC gating here to eliminate variables while we debug typing.
     * (Sidebar visibility is still driven by your ModuleRegistry+RBAC.)
     */
    public function index(): string
    {
        // 1) Page header & editability flag (same as before)
        $pageTitle = 'RT Editor (Clean Build)';
        $canEdit   = true; // force editable for now

        // 2) Resolve scope/id from URL (accepts templateId/syllabusId, or fallback id+scope)
        $rtScope = '';
        $rtId    = 0;
        $initialJsonRaw = ''; // raw TipTap doc JSON (string)

        try {
            $scopeParam = isset($_GET['scope']) ? (string)$_GET['scope'] : '';
            $tplId  = isset($_GET['templateId']) ? (int)$_GET['templateId'] : 0;
            $sylId  = isset($_GET['syllabusId']) ? (int)$_GET['syllabusId'] : 0;
            $qid    = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if ($tplId > 0)      { $rtScope = 'template'; $rtId = $tplId; }
            elseif ($sylId > 0)  { $rtScope = 'syllabus'; $rtId = $sylId; }
            elseif ($qid > 0)    { $rtScope = ($scopeParam === 'syllabus') ? 'syllabus' : 'template'; $rtId = $qid; }

            // 3) Fetch initial content using your existing model (DB-agnostic)
            if ($rtId > 0) {
                $model = new RTEditorModel($this->db);

                if ($rtScope === 'template') {
                    // getTemplateForEdit() already normalizes JSON to a string when needed
                    $row = $model->getTemplateForEdit($rtId);
                    if ($row && array_key_exists('content', $row)) {
                        $initialJsonRaw = is_string($row['content'])
                            ? $row['content']
                            : json_encode($row['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                } elseif ($rtScope === 'syllabus') {
                    $row = $model->getSyllabus($rtId);
                    if ($row && array_key_exists('content', $row)) {
                        $initialJsonRaw = is_string($row['content'])
                            ? $row['content']
                            : json_encode($row['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal hydrate error: leave $initialJsonRaw empty so the view falls back to a blank doc
            // You can log if you want: error_log('[RTEditor@index] ' . $e->getMessage());
        }

        // --- SANITIZE nested pageWrapper nodes in server payload (defensive) ---
        // This will remove nested pageWrapper wrappers by flattening their children into the outer node.
        function flatten_page_wrappers(array $node) : array {
            // only nodes with 'type' and 'content' are considered
            if (!isset($node['type']) || !is_array($node['content'] ?? null)) return $node;

            // If this node is a pageWrapper, inspect its children and flatten any child pageWrapper
            if ($node['type'] === 'pageWrapper' || $node['type'] === 'page-wrapper' || $node['type'] === 'page_wrapper') {
                $newContent = [];
                foreach ($node['content'] as $child) {
                    // If child is a pageWrapper, splice its content in instead of keeping the child wrapper
                    if (isset($child['type']) && ($child['type'] === 'pageWrapper' || $child['type'] === 'page-wrapper' || $child['type'] === 'page_wrapper') && is_array($child['content'] ?? null)) {
                        // recursively flatten child's children too
                        foreach ($child['content'] as $grand) {
                            $newContent[] = flatten_page_wrappers($grand);
                        }
                    } else {
                        $newContent[] = flatten_page_wrappers($child);
                    }
                }
                $node['content'] = $newContent;
                return $node;
            }

            // For non-pageWrapper nodes, recurse into content if present
            if (is_array($node['content'] ?? null)) {
                $nc = [];
                foreach ($node['content'] as $c) {
                    $nc[] = flatten_page_wrappers($c);
                }
                $node['content'] = $nc;
            }
            return $node;
        }

        try {
            // try parse JSON and flatten nested wrappers (non-fatal)
            $maybe = json_decode($initialJsonRaw, true);
            if (is_array($maybe) && isset($maybe['type']) && is_array($maybe['content'] ?? null)) {
                $flattened = flatten_page_wrappers($maybe);
                $initialJsonRaw = json_encode($flattened, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (\Throwable $e) {
            // non-fatal — keep original payload if anything goes wrong
        }

        // 4) Provide a safe fallback doc so the <script id="rt-initial-json"> is always present
        if (!is_string($initialJsonRaw) || trim($initialJsonRaw) === '') {
            $initialJsonRaw = '{"type":"doc","content":[{"type":"paragraph"}]}';
        }

        // 5) Render the view (these vars are picked up by your existing index.php)
        ob_start();
        // Expose to the included view:
        /** @var string $initialJsonRaw */
        /** @var string $rtScope */
        /** @var int    $rtId */
        require __DIR__ . '/../Views/index.php';
        return (string)ob_get_clean();
    }

    /**
     * Save (snapshot) TipTap JSON from the editor.
     * Accepts POST with:
     *  - scope: "template" | "syllabus"
     *  - id: template_id or syllabus_id (int)
     *  - json: TipTap JSON string
     *  - filename: optional string
     */
    public function snapshot(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
                return;
            }

            // If you have a CSRF helper, enforce it here:
            // \App\Helpers\CsrfHelper::assertJson(); // or your variant

            $raw  = file_get_contents('php://input');
            $data = json_decode($raw ?: 'null', true);
            if (!$data || !is_array($data)) {
                // fallback to form-encoded POST
                $data = $_POST;
            }

            $scope    = (string)($data['scope'] ?? '');
            $id       = (int)($data['id'] ?? 0);
            $filename = isset($data['filename']) ? trim((string)$data['filename']) : null;

            if ($id <= 0) {
                throw new \InvalidArgumentException('Missing or invalid id.');
            }

            // Accept TipTap JSON as object/array or string
            $content = $data['json'] ?? $data['content'] ?? null;
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $content = $decoded;
                }
            }
            if (!is_array($content) && !is_object($content)) {
                throw new \InvalidArgumentException('Missing or invalid TipTap JSON.');
            }

            // Use your existing model
            $model = new \App\Modules\RTEditor\Models\RTEditorModel($this->db);

            if ($scope === 'template') {
                $ok = $model->updateTemplate($id, ['content' => $content, 'filename' => $filename]);
            } elseif ($scope === 'syllabus') {
                $ok = $model->updateSyllabus($id, ['content' => $content, 'filename' => $filename]);
            } else {
                throw new \InvalidArgumentException('Invalid scope; must be "template" or "syllabus".');
            }

            if (!$ok) {
                throw new \RuntimeException('Save failed (no changes or record not found).');
            }

            echo json_encode(['ok' => true, 'saved' => ['scope' => $scope, 'id' => $id]]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function openTemplate(): string
    {
        // Gate by Syllabus Templates view permission
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::SYLLABUSTEMPLATES_VIEW);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            FlashHelper::set('danger', 'Invalid template id');
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/dashboard?page=syllabus-templates');
            exit;
        }

        // Normalize the query so index() will pick it up and hydrate:
        //  - index() reads templateId/syllabusId (or id+scope)
        $_GET['templateId'] = $id;
        unset($_GET['syllabusId'], $_GET['scope'], $_GET['id']);

        // Let index() do the fetch + view rendering (it embeds rt-initial-json / rt-meta)
        return $this->index();
    }

}
// --- END OF FILE ---
