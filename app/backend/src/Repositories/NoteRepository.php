<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NoteRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(string $userId, string $title, string $body): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO notes (user_id, title, body) VALUES (:user_id,:title,:body) RETURNING id, user_id, title, body, created_at, updated_at");
        $stmt->execute(["user_id" => $userId, "title" => $title, "body" => $body]);
        return (array) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, user_id, title, body, created_at, updated_at FROM notes WHERE id=:id");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listByUser(string $userId, int $page, int $limit, ?string $q, ?string $tag): array
    {
        $offset = ($page - 1) * $limit;
        $params = ["user_id" => $userId, "limit" => $limit, "offset" => $offset];
        $sql = "SELECT n.id, n.user_id, n.title, n.body, n.created_at, n.updated_at
                FROM notes n
                WHERE n.user_id = :user_id";
        if ($q !== null && $q !== "") {
            $sql .= " AND (n.title ILIKE :q OR n.body ILIKE :q)";
            $params["q"] = "%{$q}%";
        }
        if ($tag !== null && $tag !== "") {
            $sql .= " AND EXISTS (
                SELECT 1 FROM note_tags nt
                JOIN tags t ON t.id = nt.tag_id
                WHERE nt.note_id = n.id AND t.user_id = :user_id AND t.name = :tag
            )";
            $params["tag"] = $tag;
        }
        $sql .= " ORDER BY n.updated_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $type = in_array($k, ["limit", "offset"], true) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":{$k}", $v, $type);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM notes WHERE user_id=:user_id");
        $countStmt->execute(["user_id" => $userId]);
        return ["data" => $data, "total" => (int) $countStmt->fetchColumn()];
    }

    public function update(string $id, array $fields): ?array
    {
        $stmt = $this->pdo->prepare("UPDATE notes SET title=:title, body=:body, updated_at=NOW() WHERE id=:id RETURNING id, user_id, title, body, created_at, updated_at");
        $stmt->execute(["id" => $id, "title" => $fields["title"], "body" => $fields["body"]]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM notes WHERE id=:id");
        $stmt->execute(["id" => $id]);
    }
}
