# Task Manager

Task Manager is an API-first Laravel project with a lightweight Vue dashboard.

The backend is the strongest part of the project and contains the core business logic, validation, authorization, and persistence patterns. The frontend is intentionally very simple and still fairly rudimentary; it exists to demonstrate and exercise API workflows, not to represent a polished production UI.

## What the app does

- Manages tasks with ownership, due dates, status, reordering, and reminders.
- Supports engagement features: follow or unfollow tasks, comments, threaded replies, and unread/read message tracking.
- Applies role-aware behavior:
  - admin users can manage all users and tasks
  - non-admin users can still browse/filter all tasks, but only edit or delete their own tasks
- Provides multi-scope task filtering for all user types:
  - all tasks
  - tasks I own
  - tasks I follow

## Current stack

### Backend
- PHP 8.2+
- Laravel 12
- Laravel Passport for bearer-token auth
- Queue jobs for reminders and notifications
- MySQL or SQLite

### Frontend
- Vue 2 (Vue CLI)
- Dashboard-template styling

## Backend architecture at a glance

The API follows a layered Laravel structure:

- Controllers: HTTP entry points and policy checks
- Form Requests: input validation and request-shape rules
- Services: orchestration and use-case logic
- Repositories: query and persistence operations
- Policies + middleware: role and ownership authorization

Routes are versioned under /api/v1.

## Main API surfaces

- Auth: register, login, me, logout
- Tasks: list, create, update, delete, reorder, reminders
- Engagement: follow, unfollow, comments, unread and mark-read endpoints
- Dashboard summary
- User options endpoint for task-owner filtering
- Admin-only user CRUD

See routes/api.php for the canonical endpoint map.

## Quick Start

This project includes an idempotent Herd bootstrap command so reviewers can get running fast.

The local setup flow is intended to work on both Windows and macOS.

### 1) Clone and enter project

- Place the repo inside your Herd projects directory (for example `C:/Users/<you>/Herd/task-manager`).
- Open terminal in the project root.

### 2) Install backend dependencies

- composer install
- (optional) You can skip this if you run `composer run herd:setup` or `composer run herd:setup:all`, which now install backend dependencies automatically.

### 3) Run Herd bootstrap

- composer run herd:setup

What this does automatically:
- installs backend Composer dependencies if missing
- creates `.env` if missing
- generates `APP_KEY` if missing
- runs migrations (auto-falls back to SQLite if MySQL is unavailable)
- creates Passport keys if missing
- creates a Passport personal access client if missing (non-interactive)
- seeds demo/test data when default test accounts are not present
- creates the storage symlink if missing

By default, local setup uses SQLite (`DB_CONNECTION=sqlite`) so MySQL is optional for first run.

### 4) Install frontend dependencies

- npm --prefix frontend install

### 5) Run app

- Backend API: php artisan serve
- Frontend: npm --prefix frontend run dev

If you see `{"message":"Frontend build assets are not available yet. Run npm --prefix frontend run build."}` in the browser, run:

- npm --prefix frontend install
- npm --prefix frontend run build

Then refresh the page.

Optional one-liner for steps 2 + 3 + 4:
- composer run herd:setup:all

If your Node/OpenSSL setup is strict, npm --prefix frontend run build is also supported.

## Test accounts & data (seeded)

- php artisan db:seed

Default seeded accounts:

- Admin account
  - email: admin@example.com
  - password: admin
  - role: admin
- Standard user account
  - email: user@example.com
  - password: user
  - role: user

These accounts are defined in `database/seeders/DatabaseSeeder.php`.

## Pre-share checklist

Before sharing with a reviewer or employer, run:

- composer run herd:setup
- composer run test
- npm --prefix frontend install
- npm --prefix frontend run build

Expected outcome:
- setup completes without prompts and can be re-run safely
- backend tests pass
- frontend build completes successfully

## Notes for reviewers and employers

- The codebase has had several recent consistency improvements:
  - dedicated task-list request validation
  - clearer service vs repository responsibilities
  - role-aware filtering and scope handling
  - focused feature and unit tests around task behavior
- The frontend is deliberately basic and should be read as a functional API client, not as final UX quality.
- The backend design and test coverage are a better signal of engineering direction than visual polish.

## Current validation snapshot

As of February 26, 2026:

- Local setup flow (`composer run herd:setup`) is intended to work on both Windows and macOS.
- Backend tests pass via `composer run test`.
- Frontend production build succeeds via `npm --prefix frontend run build`.
- Frontend build currently reports non-blocking warnings from legacy dashboard Sass usage and bundle-size hints.
- `npm --prefix frontend install` reports dependency vulnerabilities in the template dependency tree; this is known and currently does not block local setup or build.

## Future improvements

### Near-term
- Add more feature tests for auth edge cases, dashboard summary scenarios, and reminder scheduling.
- Add CI automation for linting and test execution.
- Reduce remaining legacy compatibility paths after clients fully adopt scope-based filtering.

### Mid-term
- Split remaining large frontend components into smaller view modules.
- Improve loading, error, and empty states in the dashboard UI.
- Add structured logging around reminder dispatch and engagement events.

### Longer-term
- Decide on Vue modernization path (Vue 3 + Vite) or explicitly freeze Vue 2 with maintenance boundaries.
- Improve cross-database portability in analytical dashboard queries.

## Supporting docs

- CHANGES.md: current snapshot and notable updates
- postman/task-manager-api.postman_collection.json: API request collection
