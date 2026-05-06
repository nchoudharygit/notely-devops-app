<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AttachmentRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(string $noteId, string $storageKey, string $filename, string $contentType, int $sizeBytes): array
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO attachments (note_id, storage_key, filename, content_type, size_bytes)
             VALUES (:note_id,:storage_key,:filename,:content_type,:size_bytes)
             RETURNING id, note_id, storage_key, filename, content_type, size_bytes, uploaded_at"
        );
        $stmt->execute([
            "note_id" => $noteId,
            "storage_key" => $storageKey,
            "filename" => $filename,
            "content_type" => $contentType,
            "size_bytes" => $sizeBytes,
        ]);
        return (array) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listByNote(string $noteId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, filename, content_type, size_bytes, uploaded_at, storage_key FROM attachments WHERE note_id=:note_id ORDER BY uploaded_at DESC");
        $stmt->execute(["note_id" => $noteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(string $attachmentId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, note_id, storage_key, filename, content_type, size_bytes, uploaded_at FROM attachments WHERE id=:id");
        $stmt->execute(["id" => $attachmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function delete(string $attachmentId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM attachments WHERE id=:id");
        $stmt->execute(["id" => $attachmentId]);
        return $stmt->rowCount() > 0;
    }
}
