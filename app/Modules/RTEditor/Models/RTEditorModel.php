<?php
declare(strict_types=1);

/**
 * RTEditorModel
 * Path: /app/Modules/RTEditor/Models/RTEditorModel.php
 *
 * Uses public.syllabus_templates to store metadata and an optional Yjs snapshot
 * inside the JSONB "content" column:
 *   content -> {
 *     "yjs_snapshot_b64": "<base64>",
 *     "yjs_snapshot_saved_at": "2025-10-14T07:15:00Z",
 *     "... other doc json as needed ..."
 *   }
 */
namespace App\Modules\RTEditor\Models;

use App\Models\Postgres;
use PDO;

final class RTEditorModel
{
    public function __construct(private Postgres $db) {}

    /** Load template metadata by numeric ID */
    public function findById(int $templateId): ?array
    {
        $sql = "SELECT template_id, title, version, status, content, created_by, created_at, updated_at
                  FROM public.syllabus_templates
                 WHERE template_id = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([':id' => $templateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Minimal create (you can extend: program/course relations, filename, etc.) */
    public function createTemplate(string $title, string $createdBy): ?int
    {
        $sql = "INSERT INTO public.syllabus_templates (title, status, created_by, content)
                VALUES (:title, 'draft', :created_by, '{}'::jsonb)
                RETURNING template_id";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([
            ':title'      => $title,
            ':created_by' => $createdBy,
        ]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function updateTitle(int $templateId, string $title): bool
    {
        $sql  = "UPDATE public.syllabus_templates SET title = :title WHERE template_id = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':title' => $title, ':id' => $templateId]);
    }

    /** Save/merge Yjs snapshot into JSONB "content" */
    public function saveSnapshot(int $templateId, string $b64): bool
    {
        $sql = "UPDATE public.syllabus_templates
                SET content = COALESCE(content, '{}'::jsonb)
                                || jsonb_build_object(
                                    'yjs_snapshot_b64', :b64,
                                    'yjs_snapshot_saved_at', to_char((now() at time zone 'UTC'), 'YYYY-MM-DD\"T\"HH24:MI:SS\"Z\"')
                                )
                WHERE template_id = :id";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([':b64' => $b64, ':id' => $templateId]);
    }
}
