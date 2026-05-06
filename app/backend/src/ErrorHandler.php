<?php

declare(strict_types=1);

namespace App;

final class ErrorHandler
{
    public static function toPayload(\Throwable $exception): array
    {
        $status = (int) $exception->getCode();
        if ($status < 400 || $status > 599) {
            $status = 500;
        }
        $code = match ($status) {
            400 => "VALIDATION_ERROR",
            401 => "UNAUTHORIZED",
            403 => "FORBIDDEN",
            404 => "NOT_FOUND",
            409 => "CONFLICT",
            413 => "FILE_TOO_LARGE",
            415 => "UNSUPPORTED_MEDIA_TYPE",
            429 => "RATE_LIMITED",
            default => "INTERNAL_ERROR",
        };
        return [
            "status" => $status,
            "body" => [
                "error" => [
                    "code" => $code,
                    "message" => $status === 500 ? "Internal server error" : $exception->getMessage(),
                    "status" => $status,
                ],
            ],
        ];
    }
}
