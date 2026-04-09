<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\NoteService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NoteController
{
    public function __construct(private readonly NoteService $notes) {}

    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $q = $request->getQueryParams();
        $userId = (string) $request->getAttribute("user_id");
        $result = $this->notes->list(
            $userId,
            (int) ($q["page"] ?? 1),
            (int) ($q["limit"] ?? 20),
            isset($q["q"]) ? (string) $q["q"] : null,
            isset($q["tag"]) ? (string) $q["tag"] : null
        );
        $response->getBody()->write((string) json_encode([
            "data" => $result["data"],
            "total" => $result["total"],
            "page" => (int) ($q["page"] ?? 1),
            "limit" => (int) ($q["limit"] ?? 20),
        ]));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $userId = (string) $request->getAttribute("user_id");
        $result = $this->notes->create(
            $userId,
            (string) ($data["title"] ?? ""),
            (string) ($data["body"] ?? ""),
            (array) ($data["tag_ids"] ?? [])
        );
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json")->withStatus(201);
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (string) $request->getAttribute('id');
        $result = $this->notes->get((string) $request->getAttribute("user_id"), $id);
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function put(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $id = (string) $request->getAttribute('id');
        $result = $this->notes->update((string) $request->getAttribute("user_id"), $id, $data, false);
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function patch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $id = (string) $request->getAttribute('id');
        $result = $this->notes->update((string) $request->getAttribute("user_id"), $id, $data, true);
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (string) $request->getAttribute('id');
        $this->notes->delete((string) $request->getAttribute("user_id"), $id);
        return $response->withStatus(204);
    }
}
