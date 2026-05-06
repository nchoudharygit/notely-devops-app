<?php

declare(strict_types=1);

return [
    "db" => [
        "host" => getenv("DB_HOST") ?: "db",
        "name" => getenv("POSTGRES_DB") ?: "notely",
        "user" => getenv("POSTGRES_USER") ?: "notely",
        "pass" => getenv("POSTGRES_PASSWORD") ?: "",
        "port" => 5432,
    ],
    "redis" => [
        "host" => getenv("REDIS_HOST") ?: "redis",
        "port" => (int) (getenv("REDIS_PORT") ?: 6379),
    ],
    "minio" => [
        "endpoint" => getenv("MINIO_ENDPOINT") ?: "http://minio:9000",
        "public_endpoint" => getenv("MINIO_PUBLIC_ENDPOINT") ?: "http://localhost:9000",
        "key" => getenv("MINIO_ROOT_USER") ?: "",
        "secret" => getenv("MINIO_ROOT_PASSWORD") ?: "",
        "bucket" => getenv("MINIO_BUCKET_ATTACHMENTS") ?: "attachments",
        "region" => "us-east-1",
    ],
];
