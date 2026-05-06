<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NoteCacheRepository;
use App\Repositories\NoteRepository;
use App\Repositories\TagRepository;
use RuntimeException;

final class NoteService
{
    public function __construct(
        private readonly NoteRepository $notes,
        private readonly NoteCacheRepository $cache,
        private readonly TagRepository $tags
    ) {}

    public function list(string $userId, int $page, int $limit, ?string $q, ?string $tag): array
    {
        if ($limit > 100) {
            throw new RuntimeException("limit cannot exceed 100", 400);
        }
        return $this->notes->listByUser($userId, $page, $limit, $q, $tag);
    }

    public function create(string $userId, string $title, string $body, array $tagIds = []): array
    {
        if (trim($title) === "") {
            throw new RuntimeException("Title is required", 400);
        }
        $note = $this->notes->create($userId, $title, $body);
        $this->tags->syncForNote((string) $note["id"], $userId, $tagIds);
        $this->cache->set((string) $note["id"], $note);
        return $note;
    }

    public function get(string $userId, string $id): array
    {
        $cached = $this->cache->get($id);
        if ($cached && (string) ($cached["user_id"] ?? "") === $userId) {
            return $cached;
        }
        $note = $this->notes->findById($id);
        if (!$note) {
            throw new RuntimeException("Note not found", 404);
        }
        if ((string) $note["user_id"] !== $userId) {
            throw new RuntimeException("Forbidden", 403);
        }
        $this->cache->set($id, $note);
        return $note;
    }

    public function update(string $userId, string $id, array $fields, bool $partial): array
    {
        $existing = $this->get($userId, $id);
        $title = $partial ? ($fields["title"] ?? $existing["title"]) : ($fields["title"] ?? "");
        $body = $partial ? ($fields["body"] ?? $existing["body"]) : ($fields["body"] ?? "");
        $updated = $this->notes->update($id, ["title" => $title, "body" => $body]);
        if (!$updated) {
            throw new RuntimeException("Note not found", 404);
        }
        $tagIds = is_array($fields["tag_ids"] ?? null) ? $fields["tag_ids"] : [];
        if ($tagIds !== []) {
            $this->tags->syncForNote($id, $userId, $tagIds);
        }
        $this->cache->delete($id);
        return $updated;
    }

    public function delete(string $userId, string $id): void
    {
        $this->get($userId, $id);
        $this->notes->delete($id);
        $this->cache->delete($id);
    }
}
