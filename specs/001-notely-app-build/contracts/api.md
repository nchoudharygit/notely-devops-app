# API Contracts: Notely

**Base URL**: `/api/v1`
**Auth header**: `Authorization: Bearer {session_token}` (all endpoints except register/login)
**Content-Type**: `application/json` for all request/response bodies
**Error envelope** (all errors):
```json
{ "error": { "code": "SCREAMING_SNAKE_CASE", "message": "...", "status": 4xx } }
```

---

## Authentication

### POST /api/v1/auth/register

**Auth required**: No

**Request body**:
```json
{ "email": "user@example.com", "password": "min8chars" }
```

**Responses**:

| Status | Body |
|--------|------|
| 201 | `{ "id": "uuid", "email": "user@example.com" }` |
| 400 | `VALIDATION_ERROR` — missing/invalid fields or password < 8 chars |
| 409 | `CONFLICT` — email already registered |

---

### POST /api/v1/auth/login

**Auth required**: No

**Request body**:
```json
{ "email": "user@example.com", "password": "mypassword" }
```

**Responses**:

| Status | Body |
|--------|------|
| 200 | `{ "token": "64-char-hex", "expires_at": "2026-04-09T10:00:00Z" }` |
| 400 | `VALIDATION_ERROR` — missing fields |
| 401 | `UNAUTHORIZED` — wrong credentials |

---

### POST /api/v1/auth/logout

**Auth required**: Yes

**Request body**: None

**Responses**:

| Status | Body |
|--------|------|
| 204 | (empty) |
| 401 | `UNAUTHORIZED` — missing or invalid token |

---

## Notes

### GET /api/v1/notes

**Auth required**: Yes

**Query parameters**:

| Param | Type | Default | Max | Description |
|-------|------|---------|-----|-------------|
| `page` | integer | 1 | — | Page number |
| `limit` | integer | 20 | 100 | Results per page; >100 returns 400 |
| `q` | string | — | — | Keyword search (ILIKE on title + body) |
| `tag` | string | — | — | Filter by tag name |

**Response 200**:
```json
{
  "data": [
    {
      "id": "uuid",
      "title": "My Note",
      "body": "Note content",
      "tags": [{ "id": "uuid", "name": "work" }],
      "created_at": "2026-04-08T10:00:00Z",
      "updated_at": "2026-04-08T10:00:00Z"
    }
  ],
  "total": 42,
  "page": 1,
  "limit": 20
}
```

**Error responses**: 400 (`VALIDATION_ERROR` for limit > 100), 401

---

### POST /api/v1/notes

**Auth required**: Yes

**Request body**:
```json
{
  "title": "My Note",
  "body": "Optional content",
  "tag_ids": ["uuid", "uuid"]
}
```

**Responses**:

| Status | Body |
|--------|------|
| 201 | Full note object (see GET /notes format, single item) |
| 400 | `VALIDATION_ERROR` — missing title |
| 401 | `UNAUTHORIZED` |

---

### GET /api/v1/notes/:id

**Auth required**: Yes

**Response 200**:
```json
{
  "id": "uuid",
  "title": "My Note",
  "body": "Content",
  "tags": [{ "id": "uuid", "name": "work" }],
  "attachments": [
    {
      "id": "uuid",
      "filename": "photo.jpg",
      "content_type": "image/jpeg",
      "size_bytes": 204800,
      "uploaded_at": "2026-04-08T10:00:00Z"
    }
  ],
  "created_at": "2026-04-08T10:00:00Z",
  "updated_at": "2026-04-08T10:00:00Z"
}
```

**Error responses**: 401, 403 (`FORBIDDEN` — note belongs to another user), 404

---

### PUT /api/v1/notes/:id

**Auth required**: Yes

**Request body** (all fields required):
```json
{ "title": "New Title", "body": "New body", "tag_ids": [] }
```

**Responses**: 200 (full note object), 400, 401, 403, 404

---

### PATCH /api/v1/notes/:id

**Auth required**: Yes

**Request body** (all fields optional, at least one required):
```json
{ "title": "New Title", "body": "New body", "tag_ids": ["uuid"] }
```

**Responses**: 200 (full note object), 400, 401, 403, 404

---

### DELETE /api/v1/notes/:id

**Auth required**: Yes

**Response**: 204 (empty). Cascade-deletes all attachments (DB rows + MinIO objects).

**Error responses**: 401, 403, 404

---

## Tags

### GET /api/v1/tags

**Auth required**: Yes

**Response 200**:
```json
{ "data": [{ "id": "uuid", "name": "work" }, { "id": "uuid", "name": "personal" }] }
```

---

### POST /api/v1/tags

**Auth required**: Yes

**Request body**: `{ "name": "work" }`

**Responses**:

| Status | Body |
|--------|------|
| 201 | `{ "id": "uuid", "name": "work" }` |
| 400 | `VALIDATION_ERROR` — missing name |
| 409 | `CONFLICT` — tag name already exists for this user |

---

### PUT /api/v1/tags/:id

**Auth required**: Yes

**Request body**: `{ "name": "new-name" }`

**Responses**: 200 (`{ "id": "uuid", "name": "new-name" }`), 400, 401, 403, 404, 409

---

### DELETE /api/v1/tags/:id

**Auth required**: Yes

**Response**: 204. Detaches tag from all notes (cascade via `note_tags`).

**Error responses**: 401, 403, 404

---

## Attachments

### GET /api/v1/notes/:id/attachments

**Auth required**: Yes

**Response 200**:
```json
{
  "data": [
    {
      "id": "uuid",
      "filename": "report.pdf",
      "content_type": "application/pdf",
      "size_bytes": 1048576,
      "uploaded_at": "2026-04-08T10:00:00Z"
    }
  ]
}
```

**Error responses**: 401, 403 (note belongs to another user), 404 (note not found)

---

### POST /api/v1/notes/:id/attachments

**Auth required**: Yes

**Request body**: `multipart/form-data` with field `file`

**Responses**:

| Status | Body |
|--------|------|
| 201 | Single attachment object (see GET format) |
| 400 | `VALIDATION_ERROR` — no file provided |
| 401 | `UNAUTHORIZED` |
| 403 | `FORBIDDEN` — note belongs to another user |
| 404 | Note not found |
| 413 | `FILE_TOO_LARGE` — file exceeds 10 MB |
| 415 | `UNSUPPORTED_MEDIA_TYPE` — MIME type not allowed |

---

### GET /api/v1/notes/:id/attachments/:attachmentId/download

**Auth required**: Yes

**Response 200**:
```json
{
  "url": "http://localhost:9000/attachments/...?X-Amz-Expires=900&...",
  "expires_at": "2026-04-08T10:15:00Z"
}
```

URL is valid for **15 minutes** (900 seconds) from time of generation.

**Error responses**: 401, 403, 404

---

### DELETE /api/v1/notes/:id/attachments/:attachmentId

**Auth required**: Yes

**Response**: 204. Deletes the DB row and the MinIO object.

**Error responses**: 401, 403, 404

---

## Health Check

### GET /health

**Auth required**: No

**Response 200**: `{ "status": "ok" }`

Served by the web (Nginx) layer, not the API server.
