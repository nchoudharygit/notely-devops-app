<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TagRepository;
use RuntimeException;

final class TagService
{
    public function __construct(private readonly TagRepository $tags) {}

    public function list(string $userId): array
    {
        return ["data" => $this->tags->listByUser($userId)];
    }

    public function create(string $userId, string $name): array
    {
        if (trim($name) === "") {
            throw new RuntimeException("Tag name is required", 400);
        }
        return $this->tags->create($userId, $name);
    }

    public function update(string $userId, string $id, string $name): array
    {
        $tag = $this->tags->update($id, $userId, $name);
        if (!$tag) {
            throw new RuntimeException("Tag not found", 404);
        }
        return $tag;
    }

    public function delete(string $userId, string $id): void
    {
        if (!$this->tags->delete($id, $userId)) {
            throw new RuntimeException("Tag not found", 404);
        }
    }
}
