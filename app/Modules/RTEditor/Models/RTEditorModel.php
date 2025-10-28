<?php
// /app/Modules/RTEditor/Models/RTEditorModel.php
declare(strict_types=1);

namespace App\Modules\RTEditor\Models;

use App\Interfaces\StorageInterface;
use PDO;

/**
 * RTEditorModel
 *
 * Database-agnostic data access for:
 *   - Syllabus Templates (syllabus_templates)
 *   - Syllabi (syllabi)
 *
 * Notes:
 * - Constructor follows MindCloud standard (StorageInterface $db, $pdo via getConnection()).
 * - Uses app-side timestamps for DB-agnostic behavior.
 * - content is bound as JSON text (works for Postgres jsonb/MySQL JSON).
 * - No DB-specific UPSERT syntax; we do existence check -> INSERT/UPDATE.
 * - All write ops use transactions.
 */
final class RTEditorModel
{
    private StorageInterface $db;
    private PDO $pdo;

    public function __construct(StorageInterface $db)
    {
        $this->db  = $db;
        $this->pdo = $db->getConnection();
        // Safety defaults
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // ---------------------------
    // Helpers
    // ---------------------------
    private function now(): string
    {
        // App-side timestamp to remain DB-agnostic
        return (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    }

    private function encodeJson(mixed $v): ?string
    {
        if ($v === null) return null;
        if (is_string($v)) return $v; // allow pre-encoded JSON
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function existsById(string $table, string $pk, int $id): bool
    {
        $sql = "SELECT 1 FROM {$table} WHERE {$pk} = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    // =====================================================================
    // SYLLABUS TEMPLATES (table: syllabus_templates)
    // =====================================================================

    /**
     * Create a syllabus template.
     * Required (based on schema): title, created_by
     * Optional: scope, owner_program_id, owner_college_id, course_id, program_id,
     *           version, status, content, source_template_id, filename
     *
     * @param array{
     *   title:string,
     *   created_by:string,
     *   scope?:string,
     *   owner_program_id?:int|null,
     *   owner_college_id?:int|null,
     *   course_id?:int|null,
     *   program_id?:int|null,
     *   version?:string|null,
     *   status?:string|null,
     *   content?:mixed,
     *   source_template_id?:int|null,
     *   filename?:string|null
     * } $data
     * @return int Inserted template_id
     */
    public function createTemplate(array $data): int
    {
        $now = $this->now();

        $sql = <<<SQL
        INSERT INTO syllabus_templates
          (scope, owner_program_id, owner_college_id, course_id, program_id,
           title, version, status, content, source_template_id,
           created_by, created_at, updated_at, filename)
        VALUES
          (:scope, :owner_program_id, :owner_college_id, :course_id, :program_id,
           :title, :version, :status, :content, :source_template_id,
           :created_by, :created_at, :updated_at, :filename)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        $scope  = $data['scope'] ?? 'system';
        $status = $data['status'] ?? 'draft';

        $params = [
            ':scope'             => $scope,
            ':owner_program_id'  => $data['owner_program_id']  ?? null,
            ':owner_college_id'  => $data['owner_college_id']  ?? null,
            ':course_id'         => $data['course_id']         ?? null,
            ':program_id'        => $data['program_id']        ?? null,
            ':title'             => $data['title'],                // required
            ':version'           => $data['version']           ?? null,
            ':status'            => $status,
            ':content'           => $this->encodeJson($data['content'] ?? null),
            ':source_template_id'=> $data['source_template_id']?? null,
            ':created_by'        => $data['created_by'],           // required
            ':created_at'        => $now,
            ':updated_at'        => $now,
            ':filename'          => $data['filename']          ?? null,
        ];

        $this->pdo->beginTransaction();
        try {
            $stmt->execute($params);
            // DB-agnostic last insert id:
            $id = (int)$this->pdo->lastInsertId(); // Works in Postgres when PK is identity/sequence-backed and in MySQL auto_increment
            if ($id === 0) {
                // Fallback: fetch by natural key if needed (title+created_at) – rarely necessary
                $id = (int)$this->pdo->query("SELECT MAX(template_id) FROM syllabus_templates")->fetchColumn();
            }
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update a syllabus template by template_id.
     * Only updates provided fields.
     *
     * @param int $templateId
     * @param array $data (same keys as createTemplate; omit ones you won't change)
     * @return bool
     */
    public function updateTemplate(int $templateId, array $data): bool
    {
        if (!$this->existsById('syllabus_templates', 'template_id', $templateId)) {
            return false;
        }

        $fields = [];
        $params = [':template_id' => $templateId];

        $map = [
            'scope'             => 'scope',
            'owner_program_id'  => 'owner_program_id',
            'owner_college_id'  => 'owner_college_id',
            'course_id'         => 'course_id',
            'program_id'        => 'program_id',
            'title'             => 'title',
            'version'           => 'version',
            'status'            => 'status',
            'content'           => 'content',
            'source_template_id'=> 'source_template_id',
            'filename'          => 'filename',
        ];

        foreach ($map as $in => $col) {
            if (array_key_exists($in, $data)) {
                $param = ':' . $in;
                $val   = ($in === 'content') ? $this->encodeJson($data[$in]) : $data[$in];
                $fields[] = "{$col} = {$param}";
                $params[$param] = $val;
            }
        }

        // Always bump updated_at when updating
        $fields[] = "updated_at = :updated_at";
        $params[':updated_at'] = $this->now();

        if (!$fields) return true; // nothing to update

        $sql = "UPDATE syllabus_templates SET " . implode(', ', $fields) . " WHERE template_id = :template_id";

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($params);
            $this->pdo->commit();
            return $ok;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a template by id.
     */
    public function deleteTemplate(int $templateId): bool
    {
        $sql = "DELETE FROM syllabus_templates WHERE template_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $templateId]);
    }

    /**
     * Get one template.
     * @return array<string,mixed>|null
     */
    public function getTemplate(int $templateId): ?array
    {
        $sql = "SELECT * FROM syllabus_templates WHERE template_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $templateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * List templates with optional filters + pagination.
     *
     * @param array{
     *   scope?:string, status?:string, course_id?:int, program_id?:int,
     *   owner_college_id?:int, owner_program_id?:int, q?:string
     * } $filters
     * @param int $limit
     * @param int $offset
     * @return array{total:int, rows:list<array<string,mixed>>}
     */
    public function listTemplates(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = [];
        $params = [];

        $add = function(string $sql, string $param, mixed $val) use (&$where, &$params) {
            $where[] = $sql;
            $params[$param] = $val;
        };

        if (!empty($filters['scope']))            $add('scope = :scope', ':scope', $filters['scope']);
        if (!empty($filters['status']))           $add('status = :status', ':status', $filters['status']);
        if (!empty($filters['course_id']))        $add('course_id = :course_id', ':course_id', (int)$filters['course_id']);
        if (!empty($filters['program_id']))       $add('program_id = :program_id', ':program_id', (int)$filters['program_id']);
        if (!empty($filters['owner_college_id'])) $add('owner_college_id = :owner_college_id', ':owner_college_id', (int)$filters['owner_college_id']);
        if (!empty($filters['owner_program_id'])) $add('owner_program_id = :owner_program_id', ':owner_program_id', (int)$filters['owner_program_id']);
        if (!empty($filters['q']))                $add('(LOWER(title) LIKE :q)', ':q', '%'.mb_strtolower($filters['q']).'%');

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // total
        $sqlTotal = "SELECT COUNT(*) FROM syllabus_templates {$whereSql}";
        $stmtT = $this->pdo->prepare($sqlTotal);
        $stmtT->execute($params);
        $total = (int)$stmtT->fetchColumn();

        // rows (order by most recently updated)
        $sqlRows = "SELECT * FROM syllabus_templates {$whereSql} ORDER BY updated_at DESC LIMIT :lim OFFSET :off";
        $stmtR = $this->pdo->prepare($sqlRows);
        foreach ($params as $k => $v) $stmtR->bindValue($k, $v);
        $stmtR->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmtR->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmtR->execute();
        $rows = $stmtR->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * Clone an existing template to a new template (optionally changing scope/owners/title/version/status).
     *
     * @param int   $sourceTemplateId
     * @param array $overrides same keys as createTemplate() to override values
     * @return int new template_id
     */
    public function cloneTemplate(int $sourceTemplateId, array $overrides = []): int
    {
        $src = $this->getTemplate($sourceTemplateId);
        if (!$src) {
            throw new \RuntimeException('Source template not found: ' . $sourceTemplateId);
        }

        // Build new record from source + overrides
        $data = [
            'scope'             => $overrides['scope']            ?? $src['scope']            ?? 'system',
            'owner_program_id'  => $overrides['owner_program_id'] ?? $src['owner_program_id'] ?? null,
            'owner_college_id'  => $overrides['owner_college_id'] ?? $src['owner_college_id'] ?? null,
            'course_id'         => $overrides['course_id']        ?? $src['course_id']        ?? null,
            'program_id'        => $overrides['program_id']       ?? $src['program_id']       ?? null,
            'title'             => $overrides['title']            ?? (($src['title'] ?? 'Template') . ' (Copy)'),
            'version'           => $overrides['version']          ?? $src['version']          ?? null,
            'status'            => $overrides['status']           ?? 'draft',
            'content'           => $overrides['content']          ?? $src['content']          ?? null,
            'source_template_id'=> $src['template_id'], // keep lineage
            'created_by'        => $overrides['created_by']       ?? $src['created_by'],
            'filename'          => $overrides['filename']         ?? null,
        ];

        return $this->createTemplate($data);
    }

    // =====================================================================
    // SYLLABI (table: syllabi)
    // =====================================================================

    /**
     * Create a syllabus. Supports creating from a template by setting source_template_id.
     *
     * @param array{
     *   course_id:int,
     *   program_id:int,
     *   version?:string|null,
     *   content?:mixed,
     *   status?:string|null,
     *   noted_by?:string|null,
     *   approved_by?:string|null,
     *   source_template_id?:int|null,
     *   filename?:string|null
     * } $data
     * @return int Inserted syllabus_id
     */
    public function createSyllabus(array $data): int
    {
        $now = $this->now();

        $sql = <<<SQL
        INSERT INTO syllabi
          (course_id, program_id, version, content, created_at, updated_at, status,
           noted_by, approved_by, source_template_id, filename)
        VALUES
          (:course_id, :program_id, :version, :content, :created_at, :updated_at, :status,
           :noted_by, :approved_by, :source_template_id, :filename)
        SQL;

        $stmt = $this->pdo->prepare($sql);

        $params = [
            ':course_id'         => (int)$data['course_id'],
            ':program_id'        => (int)$data['program_id'],
            ':version'           => $data['version']           ?? null,
            ':content'           => $this->encodeJson($data['content'] ?? null),
            ':created_at'        => $now,
            ':updated_at'        => $now,
            ':status'            => $data['status']            ?? null,
            ':noted_by'          => $data['noted_by']          ?? null,
            ':approved_by'       => $data['approved_by']       ?? null,
            ':source_template_id'=> $data['source_template_id']?? null,
            ':filename'          => $data['filename']          ?? null,
        ];

        $this->pdo->beginTransaction();
        try {
            $stmt->execute($params);
            $id = (int)$this->pdo->lastInsertId();
            if ($id === 0) {
                $id = (int)$this->pdo->query("SELECT MAX(syllabus_id) FROM syllabi")->fetchColumn();
            }
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update a syllabus by syllabus_id. Only updates provided fields.
     */
    public function updateSyllabus(int $syllabusId, array $data): bool
    {
        if (!$this->existsById('syllabi', 'syllabus_id', $syllabusId)) {
            return false;
        }

        $fields = [];
        $params = [':syllabus_id' => $syllabusId];

        $map = [
            'course_id'          => 'course_id',
            'program_id'         => 'program_id',
            'version'            => 'version',
            'content'            => 'content',
            'status'             => 'status',
            'noted_by'           => 'noted_by',
            'approved_by'        => 'approved_by',
            'source_template_id' => 'source_template_id',
            'filename'           => 'filename',
        ];

        foreach ($map as $in => $col) {
            if (array_key_exists($in, $data)) {
                $param = ':' . $in;
                $val   = ($in === 'content') ? $this->encodeJson($data[$in]) : $data[$in];
                $fields[] = "{$col} = {$param}";
                $params[$param] = $val;
            }
        }

        // Always bump updated_at
        $fields[] = "updated_at = :updated_at";
        $params[':updated_at'] = $this->now();

        if (!$fields) return true;

        $sql = "UPDATE syllabi SET " . implode(', ', $fields) . " WHERE syllabus_id = :syllabus_id";

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($params);
            $this->pdo->commit();
            return $ok;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a syllabus by id.
     */
    public function deleteSyllabus(int $syllabusId): bool
    {
        $sql = "DELETE FROM syllabi WHERE syllabus_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $syllabusId]);
    }

    /**
     * Get one syllabus.
     * @return array<string,mixed>|null
     */
    public function getSyllabus(int $syllabusId): ?array
    {
        $sql = "SELECT * FROM syllabi WHERE syllabus_id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $syllabusId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * List syllabi with optional filters + pagination.
     *
     * @param array{ program_id?:int, course_id?:int, status?:string, q?:string } $filters
     * @param int $limit
     * @param int $offset
     * @return array{total:int, rows:list<array<string,mixed>>}
     */
    public function listSyllabi(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = [];
        $params = [];

        $add = function(string $sql, string $param, mixed $val) use (&$where, &$params) {
            $where[] = $sql;
            $params[$param] = $val;
        };

        if (!empty($filters['program_id'])) $add('program_id = :program_id', ':program_id', (int)$filters['program_id']);
        if (!empty($filters['course_id']))  $add('course_id = :course_id', ':course_id', (int)$filters['course_id']);
        if (!empty($filters['status']))     $add('status = :status', ':status', $filters['status']);
        if (!empty($filters['q']))          $add('(LOWER(filename) LIKE :q OR LOWER(version) LIKE :q)', ':q', '%'.mb_strtolower($filters['q']).'%');

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sqlTotal = "SELECT COUNT(*) FROM syllabi {$whereSql}";
        $stmtT = $this->pdo->prepare($sqlTotal);
        $stmtT->execute($params);
        $total = (int)$stmtT->fetchColumn();

        $sqlRows = "SELECT * FROM syllabi {$whereSql} ORDER BY updated_at DESC LIMIT :lim OFFSET :off";
        $stmtR = $this->pdo->prepare($sqlRows);
        foreach ($params as $k => $v) $stmtR->bindValue($k, $v);
        $stmtR->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmtR->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmtR->execute();
        $rows = $stmtR->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * Create a syllabus from an existing template (copies content + links lineage).
     *
     * @param int   $templateId
     * @param array{
     *   course_id:int,
     *   program_id:int,
     *   version?:string|null,
     *   status?:string|null,
     *   noted_by?:string|null,
     *   approved_by?:string|null,
     *   filename?:string|null
     * } $overrides
     * @return int new syllabus_id
     */
    public function createSyllabusFromTemplate(int $templateId, array $overrides): int
    {
        $tpl = $this->getTemplate($templateId);
        if (!$tpl) {
            throw new \RuntimeException('Template not found: ' . $templateId);
        }

        $data = [
            'course_id'         => (int)($overrides['course_id']  ?? $tpl['course_id']  ?? 0),
            'program_id'        => (int)($overrides['program_id'] ?? $tpl['program_id'] ?? 0),
            'version'           => $overrides['version']   ?? $tpl['version'] ?? null,
            'content'           => $tpl['content'] ?? null, // keep template content
            'status'            => $overrides['status']    ?? 'draft',
            'noted_by'          => $overrides['noted_by']  ?? null,
            'approved_by'       => $overrides['approved_by'] ?? null,
            'source_template_id'=> (int)$templateId,
            'filename'          => $overrides['filename']  ?? null,
        ];

        if ($data['course_id'] === 0 || $data['program_id'] === 0) {
            throw new \InvalidArgumentException('course_id and program_id are required to create a syllabus.');
        }

        return $this->createSyllabus($data);
    }
}
