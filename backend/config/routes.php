<?php

declare(strict_types=1);

use App\Controllers\AttachmentController;
use App\Controllers\AuthController;
use App\Controllers\NoteController;
use App\Controllers\TagController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimitMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $app->get("/health", static function ($request, $response) {
        $response->getBody()->write((string) json_encode(["status" => "ok"]));
        return $response->withHeader("Content-Type", "application/json");
    });
    $app->post("/api/v1/auth/register", [AuthController::class, "register"]);
    $app->post("/api/v1/auth/login", [AuthController::class, "login"]);

    $app->group("/api/v1", static function (RouteCollectorProxy $group): void {
        $group->post("/auth/logout", [AuthController::class, "logout"]);
        $group->get("/notes", [NoteController::class, "list"]);
        $group->post("/notes", [NoteController::class, "create"]);
        $group->get("/notes/{id}", [NoteController::class, "get"]);
        $group->put("/notes/{id}", [NoteController::class, "put"]);
        $group->patch("/notes/{id}", [NoteController::class, "patch"]);
        $group->delete("/notes/{id}", [NoteController::class, "delete"]);

        $group->get("/tags", [TagController::class, "list"]);
        $group->post("/tags", [TagController::class, "create"]);
        $group->put("/tags/{id}", [TagController::class, "update"]);
        $group->delete("/tags/{id}", [TagController::class, "delete"]);

        $group->get("/notes/{id}/attachments", [AttachmentController::class, "list"]);
        $group->post("/notes/{id}/attachments", [AttachmentController::class, "upload"]);
        $group->get("/notes/{id}/attachments/{attachmentId}/download", [AttachmentController::class, "download"]);
        $group->delete("/notes/{id}/attachments/{attachmentId}", [AttachmentController::class, "delete"]);
    })->add(RateLimitMiddleware::class)->add(AuthMiddleware::class);
};
