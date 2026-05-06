<?php

declare(strict_types=1);

namespace App\Services;

use Aws\S3\S3Client;
use Psr\Http\Message\StreamInterface;

final class ObjectStorageService
{
    public function __construct(
        private readonly S3Client $s3,
        private readonly array $settings
    ) {}

    public function put(string $key, StreamInterface $body, string $contentType): void
    {
        $this->s3->putObject([
            "Bucket" => $this->settings["minio"]["bucket"],
            "Key" => $key,
            "Body" => $body,
            "ContentType" => $contentType,
        ]);
    }

    public function delete(string $key): void
    {
        $this->s3->deleteObject([
            "Bucket" => $this->settings["minio"]["bucket"],
            "Key" => $key,
        ]);
    }

    public function presignedGetUrl(string $key): array
    {
        $cmd = $this->s3->getCommand("GetObject", [
            "Bucket" => $this->settings["minio"]["bucket"],
            "Key" => $key,
        ]);
        $request = $this->s3->createPresignedRequest($cmd, "+15 minutes");
        $url = (string) $request->getUri();
        $public = rtrim((string) $this->settings["minio"]["public_endpoint"], "/");
        $url = preg_replace("#^https?://[^/]+#", $public, $url) ?: $url;
        return ["url" => $url, "expires_at" => gmdate("c", time() + 900)];
    }
}
