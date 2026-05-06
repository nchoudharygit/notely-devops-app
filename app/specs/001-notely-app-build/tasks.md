# Tasks: Notely Full App Build

**Input**: Design documents from `/specs/001-notely-app-build/`
**Prerequisites**: `plan.md`, `spec.md`, `research.md`, `data-model.md`, `contracts/api.md`, `quickstart.md`

**Tests**: Automated test tasks are not explicitly requested by the spec; this plan uses independent scenario validation per user story.

**Organization**: Tasks are grouped by user story so each story can be implemented and validated independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: User story label (`[US1]`, `[US2]`, `[US3]`, `[US4]`)
- Every task includes a concrete file path

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Initialize repository structure and local stack wiring.

- [X] T001 Create local environment template values in `.env.example`
- [X] T002 Define 5-service local stack in `docker-compose.yml`
- [X] T003 [P] Configure API container build and PHP extensions in `backend/Dockerfile`
- [X] T004 [P] Configure frontend static container in `frontend/Dockerfile`
- [X] T005 [P] Configure reverse proxy and `/api` forwarding in `nginx/default.conf`
- [X] T006 Initialize backend package dependencies and scripts in `backend/composer.json`
- [X] T007 Configure migration environments and DB connection in `backend/phinx.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core platform capabilities required before any user story implementation.

**⚠️ CRITICAL**: Complete this phase before starting user story phases.

- [X] T008 Create Slim entrypoint and bootstrap wiring in `backend/public/index.php`
- [X] T009 Configure dependency injection container services in `backend/config/container.php`
- [X] T010 Register base routing groups and middleware pipeline in `backend/config/routes.php`
- [X] T011 [P] Implement uniform JSON error envelope mapping in `backend/src/ErrorHandler.php`
- [X] T012 [P] Implement Redis-backed authentication middleware in `backend/src/Middleware/AuthMiddleware.php`
- [X] T013 [P] Implement per-user Redis rate limiting middleware in `backend/src/Middleware/RateLimitMiddleware.php`
- [X] T014 Create initial schema migration for users/notes/tags/note_tags/attachments in `backend/migrations/202604080001_initial_schema.php`
- [X] T015 [P] Add backend runtime settings and service credentials in `backend/config/settings.php`
- [X] T016 [P] Implement shared API request wrapper with auth header support in `frontend/js/api.js`

**Checkpoint**: Foundation complete; user story delivery can begin.

---

## Phase 3: User Story 1 - Account Registration & Login (Priority: P1) 🎯 MVP

**Goal**: Let visitors register, log in, receive a 24h session token, and log out.

**Independent Test**: Register a new user, log in, call an authenticated endpoint, log out, and verify the same token is rejected.

### Implementation for User Story 1

- [X] T017 [P] [US1] Implement user persistence queries and email uniqueness lookup in `backend/src/Repositories/UserRepository.php`
- [X] T018 [P] [US1] Implement Redis session token create/read/delete methods in `backend/src/Repositories/SessionRepository.php`
- [X] T019 [US1] Implement registration/login/logout business logic with bcrypt and token TTL in `backend/src/Services/AuthService.php`
- [X] T020 [US1] Implement auth request validation and responses in `backend/src/Controllers/AuthController.php`
- [X] T021 [US1] Add auth endpoints (`/auth/register`, `/auth/login`, `/auth/logout`) in `backend/config/routes.php`
- [X] T022 [P] [US1] Build login/register/logout UI state flows in `frontend/js/auth.js`
- [X] T023 [US1] Wire authentication views and session state bootstrapping in `frontend/index.html`

**Checkpoint**: User Story 1 is independently functional and demo-ready as MVP.

---

## Phase 4: User Story 2 - Notes CRUD & Search (Priority: P2)

**Goal**: Allow authenticated users to create, list, view, update, search, and delete only their own notes.

**Independent Test**: Log in, create note, fetch by ID, update title/body, search by keyword, delete note, and verify subsequent fetch returns `404`.

### Implementation for User Story 2

- [X] T024 [P] [US2] Implement note and note-tag persistence queries with pagination/search in `backend/src/Repositories/NoteRepository.php`
- [X] T025 [P] [US2] Implement ownership-aware note read cache invalidation in `backend/src/Repositories/NoteCacheRepository.php`
- [X] T026 [US2] Implement note CRUD/search service rules (`limit` max 100, ownership checks) in `backend/src/Services/NoteService.php`
- [X] T027 [US2] Implement note endpoint handlers for list/create/get/put/patch/delete in `backend/src/Controllers/NoteController.php`
- [X] T028 [US2] Add note endpoints under `/notes` in `backend/config/routes.php`
- [X] T029 [P] [US2] Build notes list/editor/search interactions in `frontend/js/notes.js`
- [X] T030 [US2] Integrate note view rendering and authenticated navigation in `frontend/index.html`

**Checkpoint**: User Stories 1 and 2 both work independently end-to-end.

---

## Phase 5: User Story 3 - Tags (Priority: P3)

**Goal**: Let authenticated users create/rename/delete tags, attach them to notes, and filter notes by tag.

**Independent Test**: Create tag, attach it to notes, filter notes by tag, rename tag, delete tag, and verify it detaches from notes.

### Implementation for User Story 3

- [X] T031 [P] [US3] Implement user-scoped tag persistence and note-tag association queries in `backend/src/Repositories/TagRepository.php`
- [X] T032 [US3] Implement tag create/rename/delete and note-tag assignment rules in `backend/src/Services/TagService.php`
- [X] T033 [US3] Implement tag endpoint handlers for list/create/update/delete in `backend/src/Controllers/TagController.php`
- [X] T034 [US3] Add `/tags` endpoints and note tag-filter query support in `backend/config/routes.php`
- [X] T035 [P] [US3] Build tag management and note tag-picker interactions in `frontend/js/tags.js`
- [X] T036 [US3] Integrate tag filters with note listing UI in `frontend/js/notes.js`

**Checkpoint**: User Story 3 works independently alongside existing auth and notes flows.

---

## Phase 6: User Story 4 - File Attachments (Priority: P4)

**Goal**: Let authenticated users upload/list/download/delete note attachments using MinIO with 15-minute pre-signed URLs.

**Independent Test**: Upload allowed file to owned note, list attachments, fetch download URL and access file, delete attachment, verify list no longer contains it.

### Implementation for User Story 4

- [X] T037 [P] [US4] Implement attachment metadata persistence and note ownership checks in `backend/src/Repositories/AttachmentRepository.php`
- [X] T038 [P] [US4] Implement S3-compatible MinIO client wrapper with pre-signed URL generation in `backend/src/Services/ObjectStorageService.php`
- [X] T039 [US4] Implement attachment upload/list/download/delete business logic and validations in `backend/src/Services/AttachmentService.php`
- [X] T040 [US4] Implement attachment endpoint handlers for multipart upload and download URL response in `backend/src/Controllers/AttachmentController.php`
- [X] T041 [US4] Add note attachment routes in `backend/config/routes.php`
- [X] T042 [P] [US4] Build attachment upload/list/download/delete UI actions in `frontend/js/attachments.js`
- [X] T043 [US4] Integrate attachment panel into note details experience in `frontend/js/notes.js`

**Checkpoint**: User Story 4 is independently functional and complete.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Final hardening, docs, and full-stack verification across all stories.

- [X] T044 [P] Add API health endpoint and nginx exposure alignment in `backend/config/routes.php`
- [X] T045 [P] Refine shared frontend styling and responsive layout polish in `frontend/css/app.css`
- [X] T046 Document full local runbook and workflow verification steps in `README.md`
- [X] T047 Validate and refresh quickstart commands/ports/credentials in `specs/001-notely-app-build/quickstart.md`
- [X] T048 Add implementation progress and story verification checklist in `specs/001-notely-app-build/tasks.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies.
- **Phase 2 (Foundational)**: Depends on Phase 1; blocks all user stories.
- **Phase 3 (US1)**: Depends on Phase 2; establishes MVP authentication.
- **Phase 4 (US2)**: Depends on US1 for authenticated flows.
- **Phase 5 (US3)**: Depends on US2 note domain and can proceed after US2.
- **Phase 6 (US4)**: Depends on US2 note domain and can proceed in parallel with US3 after US2.
- **Phase 7 (Polish)**: Depends on completion of all selected user stories.

### User Story Dependency Graph

- `US1 -> US2 -> (US3 || US4) -> Polish`

### Within-Story Execution Rules

- Repositories before services.
- Services before controllers/routes.
- Backend endpoints before frontend integration for the same capability.
- Complete independent story validation before moving to the next priority slice.

### Parallel Opportunities

- Setup: `T003`, `T004`, `T005` can run concurrently after `T002`.
- Foundational: `T011`, `T012`, `T013`, `T015`, `T016` can run concurrently after `T010`.
- US1: `T017`, `T018`, `T022` can run in parallel.
- US2: `T024`, `T025`, `T029` can run in parallel.
- US3: `T031`, `T035` can run in parallel.
- US4: `T037`, `T038`, `T042` can run in parallel.
- After US2: US3 and US4 phases can be staffed in parallel.

---

## Parallel Example: User Story 3

```bash
# Parallel backend/frontend start for tags
Task: "T031 [US3] Implement user-scoped tag persistence and note-tag association queries in backend/src/Repositories/TagRepository.php"
Task: "T035 [US3] Build tag management and note tag-picker interactions in frontend/js/tags.js"
```

## Parallel Example: User Story 4

```bash
# Parallel repository, storage, and UI preparation
Task: "T037 [US4] Implement attachment metadata persistence and note ownership checks in backend/src/Repositories/AttachmentRepository.php"
Task: "T038 [US4] Implement S3-compatible MinIO client wrapper with pre-signed URL generation in backend/src/Services/ObjectStorageService.php"
Task: "T042 [US4] Build attachment upload/list/download/delete UI actions in frontend/js/attachments.js"
```

---

## Implementation Strategy

### MVP First (US1 only)

1. Complete Phase 1 and Phase 2.
2. Deliver Phase 3 (US1) and validate auth end-to-end.
3. Demo/deploy MVP authentication flow.

### Incremental Delivery

1. Add US2 for core note value.
2. Add US3 and US4 as independent post-core increments.
3. Finish with Polish for consistency and docs.

### Suggested Team Parallelization

1. Team aligns on Setup + Foundational together.
2. One owner drives US2 after US1 unlocks.
3. Split US3 and US4 across developers once US2 is stable.

## Implementation Progress Checklist

- [X] Setup and foundational backend/frontend scaffolding created
- [X] Authentication, notes, tags, and attachments API/frontend modules added
- [ ] Runtime integration verified via `docker compose up --build -d`
- [ ] Database migrations executed and validated
- [ ] End-to-end story checks completed against running stack
