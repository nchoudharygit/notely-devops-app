<?php

declare(strict_types=1);

namespace App\Middleware;

use Predis\Client;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Client $redis,
        private readonly ResponseFactoryInterface $responseFactory
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = (string) $request->getAttribute("user_id");
        if ($userId === "") {
            return $handler->handle($request);
        }
        $key = "ratelimit:{$userId}:" . (string) floor(time() / 60);
        $count = (int) $this->redis->incr($key);
        if ($count === 1) {
            $this->redis->expire($key, 60);
        }
        if ($count > 60) {
            $response = $this->responseFactory->createResponse(429);
            $response->getBody()->write((string) json_encode([
                "error" => ["code" => "RATE_LIMITED", "message" => "Too many requests", "status" => 429],
            ]));
            return $response->withHeader("Content-Type", "application/json");
        }
        return $handler->handle($request);
    }
}
