<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AttachmentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class AttachmentController
{
    public function __construct(private readonly AttachmentService $attachments) {}

    public function list(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $result = $this->attachments->list((string) $request->getAttribute("user_id"), (string) $args["id"]);
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function upload(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $files = $request->getUploadedFiles();
        $file = $files["file"] ?? null;
        if ($file === null) {
            throw new RuntimeException("No file provided", 400);
        }
        $result = $this->attachments->upload((string) $request->getAttribute("user_id"), (string) $args["id"], $file);
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json")->withStatus(201);
    }

    public function download(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $result = $this->attachments->downloadUrl(
            (string) $request->getAttribute("user_id"),
            (string) $args["id"],
            (string) $args["attachmentId"]
        );
        $response->getBody()->write((string) json_encode($result));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->attachments->delete(
            (string) $request->getAttribute("user_id"),
            (string) $args["id"],
            (string) $args["attachmentId"]
        );
        return $response->withStatus(204);
    }
}
