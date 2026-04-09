<?php

declare(strict_types=1);

use App\Repositories\AttachmentRepository;
use App\Repositories\NoteCacheRepository;
use App\Repositories\NoteRepository;
use App\Repositories\SessionRepository;
use App\Repositories\TagRepository;
use App\Repositories\UserRepository;
use App\Services\AttachmentService;
use App\Services\AuthService;
use App\Services\NoteService;
use App\Services\ObjectStorageService;
use App\Services\TagService;
use Aws\S3\S3Client;
use Predis\Client;

$settings = require __DIR__ . "/settings.php";

return [
    "settings" => $settings,
    PDO::class => static function () use ($settings): PDO {
        $db = $settings["db"];
        $dsn = sprintf("pgsql:host=%s;port=%d;dbname=%s", $db["host"], $db["port"], $db["name"]);
        $pdo = new PDO($dsn, $db["user"], $db["pass"], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return $pdo;
    },
    Client::class => static function () use ($settings): Client {
        return new Client([
            "scheme" => "tcp",
            "host" => $settings["redis"]["host"],
            "port" => $settings["redis"]["port"],
        ]);
    },
    S3Client::class => static function () use ($settings): S3Client {
        return new S3Client([
            "version" => "latest",
            "region" => $settings["minio"]["region"],
            "endpoint" => $settings["minio"]["endpoint"],
            "use_path_style_endpoint" => true,
            "credentials" => [
                "key" => $settings["minio"]["key"],
                "secret" => $settings["minio"]["secret"],
            ],
        ]);
    },
    UserRepository::class => static fn (PDO $pdo) => new UserRepository($pdo),
    SessionRepository::class => static fn (Client $redis) => new SessionRepository($redis),
    NoteRepository::class => static fn (PDO $pdo) => new NoteRepository($pdo),
    NoteCacheRepository::class => static fn (Client $redis) => new NoteCacheRepository($redis),
    TagRepository::class => static fn (PDO $pdo) => new TagRepository($pdo),
    AttachmentRepository::class => static fn (PDO $pdo) => new AttachmentRepository($pdo),
    ObjectStorageService::class => static fn (S3Client $s3) => new ObjectStorageService($s3, $settings),
    AuthService::class => static fn (UserRepository $u, SessionRepository $s) => new AuthService($u, $s),
    NoteService::class => static fn (NoteRepository $n, NoteCacheRepository $c, TagRepository $t) => new NoteService($n, $c, $t),
    TagService::class => static fn (TagRepository $t) => new TagService($t),
    AttachmentService::class => static fn (AttachmentRepository $a, NoteRepository $n, ObjectStorageService $o) => new AttachmentService($a, $n, $o),

    Psr\Http\Message\ResponseFactoryInterface::class => static function (): Psr\Http\Message\ResponseFactoryInterface {
        return new Slim\Psr7\Factory\ResponseFactory();
    },
    App\Middleware\AuthMiddleware::class => static fn (
        Predis\Client $redis,
        Psr\Http\Message\ResponseFactoryInterface $rf
    ) => new App\Middleware\AuthMiddleware($redis, $rf),
    App\Middleware\RateLimitMiddleware::class => static fn (
        Predis\Client $redis,
        Psr\Http\Message\ResponseFactoryInterface $rf
    ) => new App\Middleware\RateLimitMiddleware($redis, $rf),
    App\Controllers\AuthController::class => static fn (
        App\Services\AuthService $s
    ) => new App\Controllers\AuthController($s),
    App\Controllers\NoteController::class => static fn (
        App\Services\NoteService $s
    ) => new App\Controllers\NoteController($s),
    App\Controllers\TagController::class => static fn (
        App\Services\TagService $s
    ) => new App\Controllers\TagController($s),
    App\Controllers\AttachmentController::class => static fn (
        App\Services\AttachmentService $s
    ) => new App\Controllers\AttachmentController($s),
]; 