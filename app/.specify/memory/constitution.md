<!--
  SYNC IMPACT REPORT
  ==================
  Version change: 1.0.0 → 1.1.0
  Modified principles: None renamed or removed
  Added sections: None
  Removed sections: None
  Modified sections:
    Technology Stack & Constraints — added mandatory backend language (PHP) and
    PHP-specific constraints (PDO for DB access, Composer for dependencies).
  Templates requiring updates:
    ✅ .specify/templates/plan-template.md — Technical Context / Language field now has a
       concrete value; Constitution Check should flag non-PHP backend implementations as FAIL.
    ✅ .specify/templates/spec-template.md — no structural change required.
    ✅ .specify/templates/tasks-template.md — no structural change required.
  Deferred items: None.
-->

# Notely Constitution

## Core Principles

### I. Stateless API Server

The API server MUST remain stateless. No application-level in-memory state is permitted.
All persistent state MUST reside in PostgreSQL (structured data), Redis (sessions, cache,
rate-limit counters), or object storage (binary files). This ensures horizontal scalability
and makes the system predictable under infrastructure churn — a core goal of Notely as a
cloud deployment learning vehicle.

### II. Layered Security

Security controls MUST be applied at every tier:

- Passwords MUST be stored as bcrypt hashes (cost factor ≥ 12); plaintext passwords MUST
  never appear in logs, responses, or storage.
- Session tokens MUST expire after 24 hours. Every authenticated request MUST validate the
  token against Redis before proceeding.
- User file attachments MUST be stored in a private object storage bucket. Direct public
  access MUST be disabled; downloads MUST go through time-limited pre-signed URLs generated
  by the API server.
- Credentials (database passwords, storage keys, secrets) MUST be injected at runtime via a
  secrets store. Hardcoded credentials in source code or configuration files are forbidden.
- All traffic MUST be encrypted in transit. TLS termination is handled upstream of the API
  server; the API server MUST assume HTTPS context.

### III. Consistent Error Contract

All API error responses MUST conform to the standard error envelope:

```json
{ "error": { "code": "SCREAMING_SNAKE_CASE", "message": "Human-readable detail.", "status": 4xx|5xx } }
```

The canonical error code set (VALIDATION_ERROR, UNAUTHORIZED, FORBIDDEN, NOT_FOUND,
CONFLICT, FILE_TOO_LARGE, UNSUPPORTED_MEDIA_TYPE, RATE_LIMITED, INTERNAL_ERROR) MUST be
used. No ad-hoc error shapes are permitted. This principle ensures client developers have a
single reliable contract regardless of which endpoint fails.

### IV. Resource Ownership Enforcement

Every API endpoint that reads or mutates a resource MUST verify that the authenticated user
owns that resource. An authenticated user accessing another user's note, tag, or attachment
MUST receive `403 FORBIDDEN` (not `404 NOT_FOUND`). This boundary MUST be enforced at the
service layer, not only at the route level.

### V. Simplicity as a Teaching Constraint

Notely is a learning vehicle for cloud infrastructure deployment. Implementation choices
MUST favour clarity over cleverness. The following MUST NOT be introduced without explicit
justification and documented rationale:

- Additional services or infrastructure components beyond those specified (Web Server, API
  Server, PostgreSQL, Redis, Object Storage).
- Abstractions or design patterns that exist solely for anticipated future requirements
  (YAGNI applies).
- Full-text search engines — keyword search MUST use PostgreSQL `ILIKE` queries until a
  documented, justified requirement demands otherwise.

## Technology Stack & Constraints

**Runtime components**: Web Server · REST API Server · PostgreSQL · Redis · Object Storage

**Backend language**: The API server MUST be implemented in PHP. Third-party dependencies
MUST be managed via Composer. Database access MUST use PDO with prepared statements;
raw string interpolation into SQL queries is forbidden.

**API surface**: JSON REST, base URL `/api/v1`. All list endpoints MUST support `page` and
`limit` query parameters (default limit 20, maximum 100).

**Rate limiting**: 60 requests per user per minute. Exceeded limit MUST return `429
RATE_LIMITED`. Counters MUST be tracked in Redis with a 60-second TTL.

**File constraints**: Maximum attachment size is 10 MB. Permitted MIME types are
`image/jpeg`, `image/png`, `image/gif`, and `application/pdf`. Violations MUST return
`413 FILE_TOO_LARGE` or `415 UNSUPPORTED_MEDIA_TYPE` respectively.

**Search scope**: Keyword search on notes uses PostgreSQL `ILIKE` on `title` and `body`
columns. Full-text search is explicitly out of scope for this project.

## Development Workflow

Feature work MUST follow the speckit workflow:

1. Specification (`/speckit-specify`) — agree on user stories and acceptance scenarios.
2. Planning (`/speckit-plan`) — produce branch, research, data model, and API contracts.
3. Tasks (`/speckit-tasks`) — decompose into independently deliverable increments.
4. Implementation (`/speckit-implement`) — implement per task list, one user story at a time.

Each user story MUST be independently testable and deployable. Work MUST NOT begin on a
higher-priority story while a lower-priority story is in a partially broken state.

All implementations MUST pass the Constitution Check gate in the plan template before Phase 0
research begins, and again after Phase 1 design is complete.

## Governance

This constitution supersedes all other project conventions and guidelines. Any practice that
conflicts with a principle above MUST be brought into compliance before merging.

**Amendment procedure**: Amendments require a pull request that (a) increments the version
per the semantic versioning policy below, (b) updates the Sync Impact Report comment at the
top of this file, and (c) propagates necessary changes to templates and guidance docs before
or in the same PR.

**Versioning policy**:
- MAJOR: Backward-incompatible principle removal or fundamental redefinition.
- MINOR: New principle or section added; material expansion of existing guidance.
- PATCH: Clarifications, wording, or non-semantic refinements.

**Compliance review**: Every plan's Constitution Check section MUST list each principle with
a PASS / FAIL / N/A verdict. A FAIL blocks implementation unless a justified exception is
documented in the plan's Complexity Tracking table.

**Version**: 1.1.0 | **Ratified**: 2026-04-08 | **Last Amended**: 2026-04-08
