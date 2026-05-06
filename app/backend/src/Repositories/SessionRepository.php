<?php

declare(strict_types=1);

namespace App\Repositories;

use Predis\Client;

final class SessionRepository
{
    public function __construct(private readonly Client $redis) {}

    public function create(string $token, string $userId): void
    {
        $this->redis->setex("session:{$token}", 86400, $userId);
    }

    public function getUserId(string $token): ?string
    {
        $value = $this->redis->get("session:{$token}");
        return $value ? (string) $value : null;
    }

    public function delete(string $token): void
    {
        $this->redis->del(["session:{$token}"]);
    }
}
