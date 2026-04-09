<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AttachmentRepository;
use App\Repositories\NoteRepository;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class AttachmentService
{
    private const MAX_SIZE = 10485760;
    private const ALLOWED = ["image/jpeg", "image/png", "image/gif", "application/pdf"];

    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly NoteRepository $notes,
        private readonly ObjectStorageService $storage
    ) {}

    public function list(string $userId, string $noteId): array
    {
        $note = $this->notes->findById($noteId);
        if (!$note) {
            throw new RuntimeException("Note not found", 404);
        }
        if ((string) $note["user_id"] !== $userId) {
            throw new RuntimeException("Forbidden", 403);
        }
        return ["data" => $this->attachments->listByNote($noteId)];
    }

    public function upload(string $userId, string $noteId, UploadedFileInterface $file): array
    {
        $note = $this->notes->findById($noteId);
        if (!$note) {
            throw new RuntimeException("Note not found", 404);
        }
        if ((string) $note["user_id"] !== $userId) {
            throw new RuntimeException("Forbidden", 403);
        }
        if (($file->getSize() ?? 0) > self::MAX_SIZE) {
            throw new RuntimeException("File too large", 413);
        }
        $type = $file->getClientMediaType() ?: "application/octet-stream";
        if (!in_array($type, self::ALLOWED, true)) {
            throw new RuntimeException("Unsupported media type", 415);
        }
        $id = bin2hex(random_bytes(16));
        $key = "{$noteId}/{$id}/" . ($file->getClientFilename() ?: "upload.bin");
        $this->storage->put($key, $file->getStream(), $type);
        return $this->attachments->create(
            $noteId,
            $key,
            (string) ($file->getClientFilename() ?: "upload.bin"),
            $type,
            (int) ($file->getSize() ?? 0)
        );
    }

    public function downloadUrl(string $userId, string $noteId, string $attachmentId): array
    {
        $data = $this->list($userId, $noteId)["data"];
        $row = null;
        foreach ($data as $item) {
            if ((string) $item["id"] === $attachmentId) {
                $row = $item;
                break;
            }
        }
        if (!$row) {
            throw new RuntimeException("Attachment not found", 404);
        }
        return $this->storage->presignedGetUrl((string) $row["storage_key"]);
    }

    public function delete(string $userId, string $noteId, string $attachmentId): void
    {
        $data = $this->list($userId, $noteId)["data"];
        $row = null;
        foreach ($data as $item) {
            if ((string) $item["id"] === $attachmentId) {
                $row = $item;
                break;
            }
        }
        if (!$row) {
            throw new RuntimeException("Attachment not found", 404);
        }
        $this->storage->delete((string) $row["storage_key"]);
        $this->attachments->delete($attachmentId);
    }
}
