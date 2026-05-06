# Feature Specification: Notely — Full Application Build

**Feature Branch**: `001-notely-app-build`
**Created**: 2026-04-08
**Status**: Draft
**Input**: User description: "Refer @notely-app-spec.md and generate application. App must be fully functional locally"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Account Registration & Login (Priority: P1)

A new visitor arrives at the app, creates an account with an email address and password,
then logs in to receive a session token they can use for subsequent requests. They can also
log out to invalidate that session.

**Why this priority**: Nothing else in the app is accessible without an authenticated
identity. This is the foundational gate for all other stories.

**Independent Test**: Create an account, log in, verify a session token is returned, make
an authenticated request, log out, verify that same token is rejected.

**Acceptance Scenarios**:

1. **Given** a new email address not yet registered, **When** the user submits a valid
   email and password (≥ 8 characters), **Then** an account is created and a `201`
   response with the new user's id and email is returned.
2. **Given** a registered account, **When** the user submits correct credentials,
   **Then** a session token and expiry timestamp are returned (`200`).
3. **Given** a registered account, **When** the user submits an incorrect password,
   **Then** the request is rejected with `401 UNAUTHORIZED`.
4. **Given** an active session, **When** the user logs out, **Then** the token is
   invalidated and subsequent requests with that token receive `401 UNAUTHORIZED`.
5. **Given** an already-registered email, **When** a second registration is attempted,
   **Then** the request is rejected with `409 CONFLICT`.

---

### User Story 2 - Create, Read, Update & Delete Notes (Priority: P2)

An authenticated user can create notes with a title and body, view a paginated list of
their notes, open a single note, edit it, and delete it. They can also search their notes
by keyword.

**Why this priority**: Notes are the core value of the app. Without CRUD on notes,
all other functionality (tags, attachments) has no foundation.

**Independent Test**: Log in, create a note, fetch it by ID, update its title, fetch again
to confirm the change, then delete it and verify a subsequent fetch returns `404`.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they create a note with a title and body,
   **Then** the note is persisted and a `201` response with the note object is returned.
2. **Given** an authenticated user with existing notes, **When** they request their note
   list, **Then** a paginated response (default 20 per page, max 100) with total count
   is returned, containing only that user's notes.
3. **Given** an authenticated user, **When** they request a note by ID that belongs to
   them, **Then** the full note object is returned (`200`).
4. **Given** an authenticated user, **When** they request a note by ID that belongs to
   another user, **Then** `403 FORBIDDEN` is returned.
5. **Given** an authenticated user, **When** they update a note's title or body,
   **Then** the updated note (with refreshed `updated_at`) is returned and persisted.
6. **Given** an authenticated user, **When** they delete a note, **Then** `204` is
   returned and the note (along with any attachments) is removed.
7. **Given** an authenticated user, **When** they search with a keyword, **Then** only
   notes whose title or body contains that keyword (case-insensitive) are returned.

---

### User Story 3 - Tags (Priority: P3)

An authenticated user can create tags, attach one or more tags to notes, filter their
note list by tag, and rename or delete tags.

**Why this priority**: Tags add organisation value on top of the core notes experience
but are not required for basic note-taking.

**Independent Test**: Create a tag, attach it to two notes, filter the note list by that
tag and verify only those two notes appear, then delete the tag and verify it is detached
from both notes.

**Acceptance Scenarios**:

1. **Given** an authenticated user, **When** they create a tag with a name, **Then** the
   tag is persisted and returned (`201`); tag names MUST be unique per user.
2. **Given** an authenticated user, **When** they attach tag IDs when creating or
   updating a note, **Then** those tags appear on the note object.
3. **Given** an authenticated user, **When** they filter the note list with `?tag=name`,
   **Then** only notes carrying that tag are included in the response.
4. **Given** an authenticated user, **When** they rename a tag, **Then** all notes with
   that tag reflect the new name immediately.
5. **Given** an authenticated user, **When** they delete a tag, **Then** the tag is
   removed from all notes it was attached to (`204`).

---

### User Story 4 - File Attachments on Notes (Priority: P4)

An authenticated user can upload files (images or PDFs) to a specific note, list the
attachments on that note, obtain a time-limited download link, and delete attachments.

**Why this priority**: Attachments extend notes with rich media, adding meaningful
value once the core note and tag experience is stable.

**Independent Test**: Upload a JPEG to a note, list attachments and confirm it appears,
request a download URL and verify it resolves to the file, then delete the attachment
and confirm it no longer appears in the list.

**Acceptance Scenarios**:

1. **Given** an authenticated user and a note they own, **When** they upload a file ≤ 10 MB
   of an allowed MIME type (`image/jpeg`, `image/png`, `image/gif`, `application/pdf`),
   **Then** the attachment metadata is returned (`201`) and the file is stored securely.
2. **Given** an authenticated user, **When** they upload a file exceeding 10 MB,
   **Then** `413 FILE_TOO_LARGE` is returned and nothing is stored.
3. **Given** an authenticated user, **When** they upload a file with a disallowed MIME
   type, **Then** `415 UNSUPPORTED_MEDIA_TYPE` is returned.
4. **Given** an authenticated user, **When** they request the attachment list for a note
   they own, **Then** all attachment metadata records for that note are returned.
5. **Given** an authenticated user, **When** they request a download URL for an
   attachment, **Then** a URL valid for exactly 15 minutes is returned that resolves
   to the file; requests after expiry MUST be rejected by the storage layer.
6. **Given** an authenticated user, **When** they delete an attachment, **Then** `204`
   is returned, the metadata record is removed, and the stored file is deleted.

---

### Edge Cases

- What happens when a non-existent note ID is requested? → `404 NOT_FOUND`.
- What happens when an unauthenticated request is made to a protected endpoint?
  → `401 UNAUTHORIZED`.
- What happens when pagination `limit` exceeds 100? → Return `400 VALIDATION_ERROR`;
  the request is rejected and no results are returned.
- What happens when a note is deleted that still has attachments? → Attachments are
  deleted along with the note (cascade).
- What happens when a tag name collides with an existing tag for the same user?
  → `409 CONFLICT`.
- What happens when a rate-limited user (> 60 req/min) makes another request?
  → `429 RATE_LIMITED`.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST allow a visitor to register with a unique email and a
  password of at least 8 characters.
- **FR-002**: The system MUST authenticate registered users and issue a session token
  valid for 24 hours.
- **FR-003**: The system MUST invalidate a user's session token upon logout.
- **FR-004**: Users MUST only be able to read, modify, or delete resources they own
  (notes, tags, attachments).
- **FR-005**: Users MUST be able to create, read, update, and delete their own notes.
- **FR-006**: All note list endpoints MUST support pagination (`page`, `limit`; default
  20, maximum 100) and keyword search (`q`). A `limit` value exceeding 100 MUST be
  rejected with `400 VALIDATION_ERROR`.
- **FR-007**: Users MUST be able to create, rename, and delete tags scoped to their
  account.
- **FR-008**: Users MUST be able to attach tags to notes and filter their note list by
  tag.
- **FR-009**: Users MUST be able to upload files (images and PDFs ≤ 10 MB) to notes
  and retrieve download URLs valid for 15 minutes.
- **FR-010**: The system MUST enforce a rate limit of 60 requests per user per minute
  and return `429` when exceeded.
- **FR-011**: All API errors MUST be returned in the standard error envelope
  `{ "error": { "code", "message", "status" } }`.
- **FR-012**: The system MUST serve a frontend that allows users to perform all the
  above actions through a web browser without direct API calls.

### Key Entities

- **User**: Owns all other resources. Identified by email; authenticated via session
  token. Attributes: id, email, password (hashed), created_at.
- **Note**: A piece of text content belonging to a user. Attributes: id, user_id, title,
  body, created_at, updated_at. Can have many Tags and many Attachments.
- **Tag**: A user-scoped label. Attributes: id, user_id, name (unique per user). Can be
  attached to many Notes.
- **Attachment**: Binary file metadata linked to a Note. Attributes: id, note_id,
  storage_key, filename, content_type, size_bytes, uploaded_at. Actual bytes stored
  in object storage.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A new user can register, log in, create a note, and view it in under 2
  minutes from a cold start with no prior knowledge of the app.
- **SC-002**: All four core workflows (authentication, notes, tags, attachments) are
  independently demonstrable in a single local environment without external dependencies.
- **SC-003**: Every authenticated user action is correctly scoped — no user can access
  or modify another user's data under any tested scenario.
- **SC-004**: File upload, retrieval, and deletion work end-to-end without data loss
  for all permitted file types and sizes up to 10 MB.
- **SC-005**: The app remains responsive (no errors, no hangs) when a single user
  generates 60 requests within one minute; the 61st request is rejected gracefully.
- **SC-006**: All API error scenarios (invalid input, missing resources, ownership
  violations, rate limiting) return consistent, human-readable error messages.

## Assumptions

- The app runs fully locally via Docker Compose; a single `docker compose up` command
  MUST start all services (database, cache, object storage, API server, web server).
  No cloud account is required.
- A local object storage emulator is acceptable for attachment storage; the interface MUST
  match the production-equivalent bucket API.
- The frontend is a single-page application served by a dedicated web server; it
  communicates with the API server directly from the browser.
- Multi-user isolation is enforced at the API layer. There is no admin or super-user
  role in scope.
- Email uniqueness is case-insensitive at registration.
- Deleted notes cascade-delete their attachments (both metadata and stored files).
- The local development setup MUST be documented with a quickstart covering Docker
  Compose prerequisites and the single command needed to bring up the full stack.

## Clarifications

### Session 2026-04-08

- Q: How long should attachment download URLs remain valid? → A: 15 minutes (short-lived, standard security practice).
- Q: Should a `limit` value exceeding 100 be silently clamped or rejected? → A: Return `400 VALIDATION_ERROR` — reject the request explicitly.
- Q: How should the local development environment be set up? → A: Docker Compose — single `docker compose up` starts all services.
