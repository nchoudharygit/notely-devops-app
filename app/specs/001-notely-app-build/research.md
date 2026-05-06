# Research: Notely Full App Build

**Branch**: `001-notely-app-build` | **Date**: 2026-04-08

## PHP Framework for REST API

**Decision**: Slim Framework 4

**Rationale**: Slim 4 is a micro-framework that provides HTTP routing and PSR-7/PSR-15
middleware support without hiding the underlying HTTP layer. It is ideal for a learning
vehicle because developers can see exactly how requests flow from route → middleware →
controller → response. Laravel would obscure too much; raw PHP would require reinventing
routing and DI from scratch.

**Alternatives considered**:
- Laravel 11: Full-stack framework; too opinionated, hides PDO behind Eloquent ORM
  (violates constitution PDO requirement), adds significant scaffolding overhead.
- Lumen: Laravel micro-framework; still couples to Laravel ecosystem unnecessarily.
- Vanilla PHP: Would require building routing, DI, and middleware manually — adds
  complexity with no educational benefit for the infra learning goal.

## PHP DI Container

**Decision**: PHP-DI 7

**Rationale**: Slim 4 integrates cleanly with PHP-DI via PSR-11. Provides autowiring for
services and repositories, keeping bootstrap code minimal. One of the most widely used
DI containers in the PHP ecosystem.

**Alternatives considered**:
- Pimple: Simpler but requires all wiring to be manual; verbose for larger projects.
- No container: Fine for very small apps but makes dependency injection messy at scale.

## PHP Redis Client

**Decision**: Predis 2

**Rationale**: Predis is a pure-PHP Redis client that requires no C extension, making it
trivially installable via Composer in any Docker image. Supports all Redis commands needed:
SET/GET/DEL (sessions), INCR/EXPIRE (rate limiting), and key expiry.

**Alternatives considered**:
- phpredis (C extension): Better performance but requires compiling the extension in the
  Docker image. Not worth the Docker complexity for a local learning project.

## Object Storage (Local)

**Decision**: MinIO (S3-compatible) + AWS SDK for PHP 3

**Rationale**: MinIO is the standard local S3 emulator. It exposes the same API as
Amazon S3, so the same PHP code works in both local and cloud deployments — directly
supporting the cloud infra learning objective. The AWS SDK for PHP 3 supports custom
endpoint URLs (pointing to MinIO) and can generate pre-signed download URLs with a
configurable TTL (15 minutes per spec clarification).

**Pre-signed URL TTL**: 900 seconds (15 minutes), as clarified in spec session 2026-04-08.

**Alternatives considered**:
- LocalStack: Full AWS emulator; far more than needed (only S3 is required).
- league/flysystem with S3 adapter: Adds abstraction that obscures the S3 API — against
  the learning objective.

## Database Migrations

**Decision**: Phinx 0.14

**Rationale**: Phinx is a standalone PHP migration tool that works independently of any
framework. Migrations can be run via `vendor/bin/phinx migrate` inside the container.
Supports rollback, seeding, and environment-specific configuration.

**Alternatives considered**:
- Flyway: Java-based, requires a JVM in the container.
- Raw SQL scripts: Fine for simple schemas but no rollback support.
- Doctrine Migrations: Coupled to Doctrine ORM which is not used here (PDO only).

## Frontend

**Decision**: Vanilla JavaScript (ES2022) + fetch API, served by Nginx

**Rationale**: The spec goal is cloud infra deployment learning, not frontend framework
learning. Vanilla JS with fetch keeps the frontend dependency-free, meaning no build step,
no Node.js in the Docker image, and maximum clarity. Nginx serves static `.html/.css/.js`
files with zero runtime overhead.

**Alternatives considered**:
- React/Vue/Svelte: Introduce build pipelines (Node.js, bundlers) that add Docker
  complexity without benefiting the infra learning goal.
- HTMX: Server-rendered approach; would change the API server's response format and
  conflict with the JSON REST API contract.

## Docker Compose Topology

**Decision**: 5-service Compose stack

| Service | Image | Purpose |
|---------|-------|---------|
| `nginx` | nginx:1.25-alpine | Serves frontend static files on port 80; proxies `/api` to `php` |
| `php` | php:8.2-fpm-alpine + Composer | PHP-FPM running Slim 4 API |
| `db` | postgres:16-alpine | PostgreSQL data store |
| `redis` | redis:7-alpine | Session store, cache, rate limiting |
| `minio` | minio/minio:latest | Local S3-compatible object storage |

**Rationale**: This topology mirrors a real cloud deployment (web server, app server, DB,
cache, object storage) while remaining runnable with a single `docker compose up` command.
All services communicate over an internal Docker network; only `nginx` and `minio` console
expose host ports (80, 9000, 9001).

## Password Hashing

**Decision**: PHP `password_hash()` with `PASSWORD_BCRYPT` and cost factor 12

**Rationale**: PHP's built-in `password_hash`/`password_verify` functions use bcrypt
natively. Cost factor 12 satisfies the constitution's requirement (≥ 12). No external
library required.

## Session Token Generation

**Decision**: `bin2hex(random_bytes(32))` → 64-character hex string

**Rationale**: `random_bytes()` is cryptographically secure. 32 bytes = 256 bits of
entropy, making brute-force infeasible. Stored in Redis as `session:{token} → user_id`
with a 86400-second (24h) TTL.

## Rate Limiting

**Decision**: Redis INCR + EXPIRE per `ratelimit:{user_id}:{epoch_minute}`

**Rationale**: Atomically increment a per-user per-minute counter. On first INCR, set
60-second TTL. If counter > 60, return 429. This approach uses O(1) Redis operations
per request and automatically cleans up via TTL.
