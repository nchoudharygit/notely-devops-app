# Quickstart: Notely Local Development

**Branch**: `001-notely-app-build`

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) ≥ 4.x (includes Docker Compose v2)
- Git

Verify with:
```bash
docker --version        # Docker version 24+
docker compose version  # Docker Compose version v2+
```

## Start the Stack

```bash
# 1. Clone and enter the repo
git clone <repo-url> notely
cd notely

# 2. Copy environment config
cp .env.example .env

# 3. Start all services (first run pulls images and builds containers)
docker compose up --build -d

# 4. Run database migrations
docker compose exec php vendor/bin/phinx migrate

# 5. Open the app
open http://localhost       # or navigate in your browser
```

That's it. The full stack is running.

## Services & Ports

| Service | URL | Credentials |
|---------|-----|-------------|
| App (frontend) | http://localhost | — |
| API | http://localhost/api/v1 | — |
| MinIO Console | http://localhost:9001 | admin / password (see .env) |
| PostgreSQL | localhost:5432 | see .env |
| Redis | localhost:6379 | — |

## Environment Variables (.env.example)

```dotenv
# PostgreSQL
POSTGRES_DB=notely
POSTGRES_USER=notely
POSTGRES_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# MinIO
MINIO_ROOT_USER=admin
MINIO_ROOT_PASSWORD=password
MINIO_BUCKET_ATTACHMENTS=attachments
MINIO_ENDPOINT=http://minio:9000
MINIO_PUBLIC_ENDPOINT=http://localhost:9000

# App
APP_ENV=local
APP_SECRET=change-me-in-production
```

> Credentials are for local development only. Never use these values in production.

## Stopping the Stack

```bash
docker compose down          # Stop containers (data persists in volumes)
docker compose down -v       # Stop and delete all data volumes
```

## Re-running Migrations

```bash
docker compose exec php vendor/bin/phinx migrate       # Apply new migrations
docker compose exec php vendor/bin/phinx rollback      # Roll back last migration
docker compose exec php vendor/bin/phinx status        # Show migration status
```

## Logs

```bash
docker compose logs -f php      # API server logs
docker compose logs -f nginx    # Web server / access logs
docker compose logs -f db       # PostgreSQL logs
```

## Running Tests

```bash
docker compose exec php vendor/bin/phpunit
```

## Rebuilding After Code Changes

PHP files are mounted as a volume — changes take effect immediately (no rebuild needed).

For Composer dependency changes:
```bash
docker compose exec php composer install
```

For Docker image changes (Dockerfile edits):
```bash
docker compose up --build -d
```
