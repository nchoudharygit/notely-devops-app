# Implementation Plan: Notely Full App Build

**Branch**: `001-notely-app-build` | **Date**: 2026-04-08 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-notely-app-build/spec.md`

## Summary

Build the complete Notely notes application — multi-user notes with tags and file
attachments — as a fully local Docker Compose stack. The API server is PHP 8.2 (Slim 4,
PDO, Predis, AWS SDK for PHP) backed by PostgreSQL 16, Redis 7, and MinIO. The frontend
is a vanilla JS SPA served by Nginx. A single `docker compose up --build -d` starts all
five services.

## Technical Context

**Language/Version**: PHP 8.2 (API server) · HTML5/CSS3/Vanilla JS ES2022 (frontend)
**Primary Dependencies**: Slim Framework 4, PHP-DI 7, Predis 2, AWS SDK for PHP 3,
Phinx 0.14 (migrations), PHPUnit 10
**Storage**: PostgreSQL 16 (structured data) · Redis 7 (sessions/cache/rate limiting)
· MinIO latest (object storage, S3-compatible)
**Testing**: PHPUnit 10
**Target Platform**: Linux containers via Docker Compose (local)
**Project Type**: Web application (REST API + SPA frontend)
**Performance Goals**: Standard local dev performance; rate limit enforced at 60 req/min/user
**Constraints**: Stateless API; PDO prepared statements only; Composer for PHP deps;
single `docker compose up` cold start; PDO + no raw SQL interpolation

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Pre-Design | Post-Design |
|-----------|------------|-------------|
| I. Stateless API Server | PASS — all state in PostgreSQL/Redis/MinIO | PASS |
| II. Layered Security | PASS — bcrypt (cost 12), Redis sessions (24h), private MinIO bucket, pre-signed URLs (15 min), env secrets, TLS upstream | PASS |
| III. Consistent Error Contract | PASS — uniform `{ error: { code, message, status } }` across all endpoints | PASS |
| IV. Resource Ownership Enforcement | PASS — ownership verified in service layer before every read/write/delete | PASS |
| V. Simplicity as Teaching Constraint | PASS — Slim 4 micro-framework, no extra services, ILIKE search, YAGNI | PASS |
| PHP backend mandate | PASS — PHP 8.2, PDO with prepared statements, Composer | PASS |

## Project Structure

### Documentation (this feature)

```text
specs/001-notely-app-build/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── api.md           # Phase 1 output — all endpoint contracts
└── tasks.md             # Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
backend/
├── public/
│   └── index.php                    # Slim 4 entry point
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── NoteController.php
│   │   ├── TagController.php
│   │   └── AttachmentController.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── NoteService.php
│   │   ├── TagService.php
│   │   └── AttachmentService.php
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── NoteRepository.php
│   │   ├── TagRepository.php
│   │   └── AttachmentRepository.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php       # Redis session validation
│   │   └── RateLimitMiddleware.php  # Redis INCR/EXPIRE per user/minute
│   └── ErrorHandler.php             # Maps exceptions → JSON error envelope
├── migrations/
│   └── *.php                        # Phinx migration classes
├── config/
│   ├── container.php                # PHP-DI service wiring
│   └── routes.php                   # Slim route definitions
├── tests/
│   ├── Unit/
│   └── Integration/
├── composer.json
├── phinx.php
└── Dockerfile

frontend/
├── index.html
├── css/
│   └── app.css
├── js/
│   ├── api.js                       # Fetch wrapper, token injection
│   ├── auth.js                      # Register/login/logout views
│   ├── notes.js                     # Note list, create, edit, delete
│   ├── tags.js                      # Tag management
│   └── attachments.js               # File upload/download/delete
└── Dockerfile                       # Nginx serving static files

nginx/
└── default.conf                     # Proxy /api → php:9000, serve / from frontend

docker-compose.yml
.env.example
```

**Structure Decision**: Web app split (backend/ + frontend/). The separation mirrors a
real-world deployment where the API server and web server are independent services.

## Complexity Tracking

No constitution violations. No complexity exceptions required.
