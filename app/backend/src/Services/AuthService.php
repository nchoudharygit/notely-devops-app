<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly SessionRepository $sessions
    ) {}

    public function register(string $email, string $password): array
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new RuntimeException("Invalid registration payload", 400);
        }
        if ($this->users->findByEmail($email)) {
            throw new RuntimeException("Email already registered", 409);
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ["cost" => 12]);
        return $this->users->create($email, $hash);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, (string) $user["password_hash"])) {
            throw new RuntimeException("Invalid credentials", 401);
        }
        $token = bin2hex(random_bytes(32));
        $this->sessions->create($token, (string) $user["id"]);
        return [
            "token" => $token,
            "expires_at" => gmdate("c", time() + 86400),
        ];
    }

    public function logout(string $token): void
    {
        $this->sessions->delete($token);
    }
}
