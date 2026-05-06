<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TagService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TagController
{
    public function __construct(private readonly TagService $tags) {}

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->tags->list((string) $request->getAttribute("user_id"));
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $result = $this->tags->create((string) $request->getAttribute("user_id"), (string) ($data["name"] ?? ""));
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json")->withStatus(201);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $result = $this->tags->update((string) $request->getAttribute("user_id"), (string) $args["id"], (string) ($data["name"] ?? ""));
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->tags->delete((string) $request->getAttribute("user_id"), (string) $args["id"]);
        return $response->withStatus(204);
    }
}
