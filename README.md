# Task Manager

Task Manager is a Laravel API-first project with a companion dashboard frontend.

## Current State (February 2026)

- Backend is the primary focus and is functionally complete for core task flows.
- Frontend exists and works for demonstration, but it is still **rudimentary** and intended mainly to showcase API integration.
- API authentication and authorization are implemented with Passport, policies, and role middleware.
- A Postman collection and environments are included for end-to-end API testing.

## Tech Stack

### Backend
- PHP 8.2+
- Laravel 12
- Laravel Passport (token auth)
- Queue jobs for reminders/notifications
- MySQL or SQLite

### Frontend
- Vue 2 (Vue CLI app under `frontend/`)
- Dashboard template-based UI

## Architecture

The backend follows a layered structure:

- `Controllers` handle HTTP and authorization orchestration
- `Form Requests` validate input and return consistent API-friendly validation responses
- `Services` coordinate use-cases
- `Repositories` isolate query/persistence concerns
- `Policies` + role middleware enforce access control

API routes are versioned under:

- `/api/v1/*`

## Key API Areas

- Auth: register, login, me, logout
- Tasks: CRUD, reorder, reminders
- Task Engagement: follow/unfollow, comments, unread/read message state
- Users (admin-only): CRUD
- Dashboard summary

See `routes/api.php` for the canonical endpoint map.

## Postman Testing

Ready-to-import artifacts are available in `postman/`:

- `task-manager-api.postman_collection.json`
- `task-manager-local.postman_environment.json`
- `task-manager-herd.postman_environment.json`

## Quick Start

### 1) Backend setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### 2) Frontend setup

```bash
npm --prefix frontend install
```

### 3) Run backend

```bash
php artisan serve
```

### 4) Run frontend

```bash
npm --prefix frontend run dev
```

If your local Node/OpenSSL setup is strict, use:

```bash
npm --prefix frontend run build
```

(`build:app` already applies the legacy OpenSSL flag via script config.)

## Important Notes

- The frontend is intentionally simple and currently optimized for **demonstration**, not polished production UX.
- API behavior and security boundaries should be treated as the source of truth.
- Unauthenticated API requests are handled as API responses (no web login redirect).

## Suggested Improvements for Next Commit

1. **Frontend stabilization and cleanup**
   - Remove dead styles/components and align naming conventions.
   - Improve error/loading/empty states across task and engagement views.

2. **Frontend modernization path**
   - Decide whether to keep Vue 2 short-term or begin migration plan to Vue 3 + Vite.
   - Consolidate duplicated UI patterns into reusable components.

3. **Automated API coverage**
   - Add/update feature tests for auth, admin user management, and engagement flows.
   - Add CI checks for lint + tests.

4. **Developer experience hardening**
   - Add a single cross-platform `dev` workflow note (backend + frontend + queue).
   - Document known local run caveats (ports, OpenSSL, environment assumptions).

5. **Observability and safety improvements**
   - Add structured logging around reminders/notifications.
   - Add rate-limiting/auth hardening checks for critical endpoints.

## Documentation

- `CHANGES.md` — high-level change summary and next-commit plan
- `SETUP.md` — setup and deployment details
- `SERVICE_LAYER.md` — architecture notes
- `QUICKSTART.md` — fast project bootstrap

## License

MIT
