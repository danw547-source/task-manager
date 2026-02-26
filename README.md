# Task Manager

Task Manager is a Laravel API-first project with a companion dashboard frontend.

## Current State (February 2026)

- Backend is the primary focus and is functionally complete for core task flows.
- Frontend exists and works for demonstration, but it is still **rudimentary** and intended mainly to showcase API integration.
- API authentication and authorization are implemented with Passport, policies, and role middleware.
- A Postman collection is included for end-to-end API testing.

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

Set a collection/environment variable such as `base_url` to your local API URL:

- Herd: `https://task-manager.test/api/v1`
- Artisan serve: `http://127.0.0.1:8000/api/v1`

## Quick Start (Recommended: Herd)

If you use Herd, this is the **recommended** and easiest local setup.

### Prerequisites

- Herd installed and running
- PHP 8.2+ selected in Herd
- Composer installed
- Node.js + npm installed
- MySQL available

### 1) Place the project in your Herd directory

If you downloaded this repo as a ZIP:

1. Extract it.
2. If the extracted folder is named `task-manager-main`, rename it to `task-manager`.
3. Move the folder into your Herd projects directory (for example: `C:\Users\<you>\Herd\task-manager`).
4. Open the folder in VS Code.

This avoids extra filename/path tweaking and keeps the default Herd URL as `https://task-manager.test`.

### 2) Install backend dependencies

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Update your `.env`:

- `APP_URL=https://task-manager.test`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Create the database if it does not exist (example name used by `.env.example`):

```sql
CREATE DATABASE task_manager;
```

Then run migrations:

```bash
php artisan migrate
```

### 3) Link and open with Herd

From the project root, ensure the app is linked in Herd:

```bash
herd link
```

Then open:

- `https://task-manager.test`

If the domain does not resolve, re-link the folder in Herd and verify Herd is running.

### 4) Install and run frontend

```bash
npm --prefix frontend install
npm --prefix frontend run dev
```

### 5) Postman + quick API test

Import `postman/task-manager-api.postman_collection.json` and set `base_url`
to `https://task-manager.test/api/v1`.

Quick verification flow:

1. `POST /auth/register`
2. `POST /auth/login`
3. Use the returned bearer token for authenticated endpoints (for example, `GET /tasks`)

## Quick Start (Alternative: artisan serve)

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

## License

MIT
