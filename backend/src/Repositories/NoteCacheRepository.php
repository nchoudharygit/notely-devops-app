<?php

declare(strict_types=1);

namespace App\Repositories;

use Predis\Client;

final class NoteCacheRepository
{
    public function __construct(private readonly Client $redis) {}

    public function get(string $id): ?array
    {
        $raw = $this->redis->get("note:{$id}");
        return $raw ? json_decode((string) $raw, true) : null;
    }

    public function set(string $id, array $note): void
    {
        $this->redis->setex("note:{$id}", 300, (string) json_encode($note));
    }

    public function delete(string $id): void
    {
        $this->redis->del(["note:{$id}"]);
    }
}
