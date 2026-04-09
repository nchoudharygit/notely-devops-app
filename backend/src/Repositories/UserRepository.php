<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, email, password_hash FROM users WHERE LOWER(email)=LOWER(:email) LIMIT 1");
        $stmt->execute(["email" => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $email, string $passwordHash): array
    {
        $stmt = $this->pdo->prepare("INSERT INTO users (email, password_hash) VALUES (LOWER(:email), :hash) RETURNING id, email");
        $stmt->execute(["email" => $email, "hash" => $passwordHash]);
        return (array) $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
