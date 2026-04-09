<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class AuthController
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $result = $this->auth->register((string) ($data["email"] ?? ""), (string) ($data["password"] ?? ""));
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json")->withStatus(201);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $result = $this->auth->login((string) ($data["email"] ?? ""), (string) ($data["password"] ?? ""));
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $token = (string) $request->getAttribute("token");
        if ($token === "") {
            throw new RuntimeException("Unauthorized", 401);
        }
        $this->auth->logout($token);
        return $response->withStatus(204);
    }
}
