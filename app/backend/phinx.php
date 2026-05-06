<?php

declare(strict_types=1);

return [
    "paths" => [
        "migrations" => __DIR__ . "/migrations",
    ],
    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_environment" => "default",
        "default" => [
            "adapter" => "pgsql",
            "host" => getenv("DB_HOST") ?: "db",
            "name" => getenv("POSTGRES_DB") ?: "notely",
            "user" => getenv("POSTGRES_USER") ?: "notely",
            "pass" => getenv("POSTGRES_PASSWORD") ?: "",
            "port" => "5432",
            "charset" => "utf8",
        ],
    ],
    "version_order" => "creation",
];
