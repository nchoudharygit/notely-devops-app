# Data Model: Notely Full App Build

**Branch**: `001-notely-app-build` | **Date**: 2026-04-08

## Database: PostgreSQL 16

All UUIDs use the `uuid` type. Timestamps use `TIMESTAMPTZ` (UTC).
The `pgcrypto` extension provides `gen_random_uuid()`.

---

## Table: `users`

```sql
CREATE TABLE users (
    id            UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash TEXT         NOT NULL,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users (LOWER(email));
```

**Constraints**:
- `email` MUST be stored in lower-case (normalised at insert/lookup) to enforce
  case-insensitive uniqueness (spec clarification: email uniqueness is case-insensitive).
- `password_hash` stores a bcrypt hash (cost 12). Never store plaintext.

---

## Table: `notes`

```sql
CREATE TABLE notes (
    id         UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id    UUID         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title      VARCHAR(255) NOT NULL,
    body       TEXT         NOT NULL DEFAULT '',
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_notes_user_id ON notes (user_id);
CREATE INDEX idx_notes_search  ON notes USING gin(to_tsvector('simple', title || ' ' || body));
```

**Constraints**:
- `title` is required (non-empty string).
- `body` defaults to empty string (nullable body is not meaningful for notes).
- `updated_at` MUST be updated on every PUT/PATCH via application logic (no DB trigger to
  keep the API server stateless and explicit).
- The GIN index supports fast ILIKE-equivalent `to_tsvector` lookups but the API uses
  `ILIKE` per constitution (full-text search out of scope); index kept for future use.

**Search query pattern** (ILIKE per constitution):
```sql
SELECT * FROM notes
WHERE user_id = :user_id
  AND (title ILIKE :q OR body ILIKE :q)
ORDER BY updated_at DESC
LIMIT :limit OFFSET :offset;
```

---

## Table: `tags`

```sql
CREATE TABLE tags (
    id      UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID         NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name    VARCHAR(100) NOT NULL,
    UNIQUE (user_id, name)
);

CREATE INDEX idx_tags_user_id ON tags (user_id);
```

**Constraints**:
- `(user_id, name)` uniqueness is enforced at DB level. Duplicate insert returns
  `23505` (unique violation) → API maps to `409 CONFLICT`.
- Tag names are stored as-provided (not normalised to lower-case) but uniqueness
  check is case-sensitive per the spec (no case-insensitivity requirement for tags).

---

## Table: `note_tags`

```sql
CREATE TABLE note_tags (
    note_id UUID NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
    tag_id  UUID NOT NULL REFERENCES tags(id)  ON DELETE CASCADE,
    PRIMARY KEY (note_id, tag_id)
);

CREATE INDEX idx_note_tags_tag_id ON note_tags (tag_id);
```

**Constraints**:
- Composite primary key prevents duplicate associations.
- `ON DELETE CASCADE` on both FKs: deleting a note removes its tag associations;
  deleting a tag detaches it from all notes.

---

## Table: `attachments`

```sql
CREATE TABLE attachments (
    id           UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    note_id      UUID         NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
    storage_key  TEXT         NOT NULL,
    filename     VARCHAR(255) NOT NULL,
    content_type VARCHAR(100) NOT NULL,
    size_bytes   BIGINT       NOT NULL,
    uploaded_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_attachments_note_id ON attachments (note_id);
```

**Constraints**:
- `storage_key` is the object path in MinIO (e.g., `attachments/{attachment_id}/{filename}`).
  Unique by design (UUID-prefixed path).
- `content_type` MUST be one of: `image/jpeg`, `image/png`, `image/gif`, `application/pdf`.
  Validated at the application layer before insert.
- `size_bytes` MUST be ≤ 10,485,760 (10 MB). Validated at application layer.
- `ON DELETE CASCADE` on `note_id`: deleting a note deletes all its attachment rows.
  Application MUST also delete the object from MinIO before (or after) deleting the DB row.

---

## Redis Key Schema

| Key Pattern | Value | TTL | Purpose |
|-------------|-------|-----|---------|
| `session:{token}` | `user_id` (UUID string) | 86400s (24h) | Auth session |
| `note:{id}` | JSON-encoded note object | 300s (5 min) | Note read cache |
| `ratelimit:{user_id}:{epoch_minute}` | Integer count | 60s | Rate limiting |

**Cache invalidation**:
- `note:{id}` MUST be deleted (`DEL`) immediately on PUT, PATCH, or DELETE of that note.
- `session:{token}` MUST be deleted on logout.

---

## Object Storage (MinIO) Bucket Schema

| Bucket | Access | Contents |
|--------|--------|----------|
| `static-assets` | Public read (via Nginx) | Frontend HTML/CSS/JS build artifacts |
| `attachments` | Private | User-uploaded files |

**Object key format** for user attachments: `{note_id}/{attachment_id}/{original_filename}`

This format ensures:
- Objects are logically grouped by note (useful for bulk deletion on note delete).
- UUID prefix prevents collisions even if two users upload files with the same name.

**Pre-signed URL generation** (AWS SDK for PHP 3):
- TTL: 900 seconds (15 minutes).
- Region: `us-east-1` (MinIO default; configurable via env).
- Endpoint: `http://minio:9000` (internal Docker network) for API-side operations;
  pre-signed URLs use the public-facing MinIO address for browser downloads.

---

## Entity Relationships (ERD Summary)

```
users ──< notes ──< note_tags >── tags
                └──< attachments
```

- One user → many notes, many tags.
- One note → many tags (via note_tags), many attachments.
- One tag → many notes (via note_tags).
- All user-owned entities cascade-delete when the user is deleted.
