<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TagRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listByUser(string $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, name FROM tags WHERE user_id=:user_id ORDER BY name");
        $stmt->execute(["user_id" => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(string $userId, string $name): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO tags (user_id, name) VALUES (:user_id,:name) RETURNING id, name");
        $stmt->execute(["user_id" => $userId, "name" => $name]);
        return (array) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(string $id, string $userId, string $name): ?array
    {
        $stmt = $this->pdo->prepare("UPDATE tags SET name=:name WHERE id=:id AND user_id=:user_id RETURNING id, name");
        $stmt->execute(["id" => $id, "user_id" => $userId, "name" => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function delete(string $id, string $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM tags WHERE id=:id AND user_id=:user_id");
        $stmt->execute(["id" => $id, "user_id" => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function syncForNote(string $noteId, string $userId, array $tagIds): void
    {
        $this->pdo->prepare("DELETE FROM note_tags WHERE note_id=:note_id")->execute(["note_id" => $noteId]);
        if ($tagIds === []) {
            return;
        }
        $insert = $this->pdo->prepare("
            INSERT INTO note_tags (note_id, tag_id)
            SELECT :note_id, id FROM tags WHERE id=:tag_id AND user_id=:user_id
        ");
        foreach ($tagIds as $tagId) {
            $insert->execute(["note_id" => $noteId, "tag_id" => $tagId, "user_id" => $userId]);
        }
    }
}
