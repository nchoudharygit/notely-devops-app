<?php

declare(strict_types=1);

namespace App\Middleware;

use Predis\Client;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Client $redis,
        private readonly ResponseFactoryInterface $responseFactory
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine("Authorization");
        if (!preg_match("/^Bearer\s+(.+)$/i", $header, $matches)) {
            return $this->unauthorized();
        }
        $token = $matches[1];
        $userId = $this->redis->get("session:{$token}");
        if (!$userId) {
            return $this->unauthorized();
        }
        return $handler->handle($request->withAttribute("user_id", $userId)->withAttribute("token", $token));
    }

    private function unauthorized(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(401);
        $response->getBody()->write((string) json_encode([
            "error" => ["code" => "UNAUTHORIZED", "message" => "Invalid or missing token", "status" => 401],
        ]));
        return $response->withHeader("Content-Type", "application/json");
    }
}
