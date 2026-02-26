# Changes Log

This document summarizes the current state of the project and the most relevant recent updates.

## Current Snapshot (February 2026)

- Backend API is the primary stable surface.
- Frontend is functional but **rudimentary** and intended mainly for demonstration.
- Auth, authorization, and engagement flows are wired and testable through API endpoints.
- Postman exports are included for quick API verification.

## Recent Completed Work

### 1) API testing artifacts

- Added import-ready Postman collection:
  - `postman/task-manager-api.postman_collection.json`
- Added environments:
  - `postman/task-manager-local.postman_environment.json`
  - `postman/task-manager-herd.postman_environment.json`
- Included token capture flow and request defaults for easier endpoint testing.

### 2) API guest handling fix

- Updated bootstrap guest redirect behavior for API/JSON requests.
- Result: unauthenticated API calls no longer fail by trying to redirect to a non-existent web login route.

### 3) Admin user-management authorization fix

- Added explicit abilities in `UserPolicy` for:
  - `viewAny`, `view`, `create`, `update`, `delete`
- Result: admin-only Users endpoints now authorize correctly via policy checks.

### 4) Task engagement usage confirmation

- Confirmed `TaskEngagementService` is actively used in backend controllers.
- Confirmed engagement-related data is consumed by frontend service/views.

### 5) Documentation/comment pass in Laravel layers

- Added concise class-level comments across:
  - Controllers
  - Services
  - Repositories + interfaces
  - Requests
  - Policies
  - Middleware
  - Models
- Goal: make code intent and Laravel pattern usage easier to scan for maintainers.

### 6) Docs refresh

- Rewrote `README.md` and `CHANGES.md` to reflect the actual current stack/state.
- Corrected outdated assumptions (framework versions, frontend maturity claims, and status wording).

## Known Limitations

1. Frontend quality level
   - Current UI is suitable for demonstrating API behavior, not polished product UX.

2. Stack consistency
   - Frontend stack is still Vue 2 + Vue CLI template-era structure.

3. Test depth
   - Additional end-to-end/feature coverage is still needed for critical auth/admin/engagement paths.

## Proposed Improvements/Fixes for Next Commit

1. **Frontend reliability pass**
   - Tighten loading/error/empty states.
   - Remove stale styles/components and reduce template debt.

2. **Authentication UX hardening**
   - Standardize token lifecycle handling in frontend service layer.
   - Improve unauthorized/session-expired recovery behavior.

3. **API test expansion**
   - Add focused feature tests for:
     - admin user management
     - engagement comments/read receipts
     - reminder scheduling behavior

4. **CI and quality gates**
   - Add lint + test workflow automation on PRs.
   - Fail fast on policy/route regressions.

5. **Frontend modernization decision**
   - Create a migration plan (or explicit deferral decision) for Vue 3 + Vite.

6. **Ops/dev ergonomics**
   - Improve docs around local run caveats (ports/OpenSSL/workflow) and one-command dev startup guidance.
