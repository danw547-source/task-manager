# Changes Log

This file tracks project-level changes and the current engineering direction.

## Current Snapshot (February 2026)

- Backend API is stable for core task and engagement workflows.
- Frontend is functional but still intentionally rudimentary.
- Role behavior is now more complete for real-world use:
  - non-admin users can browse all tasks
  - ownership and follow boundaries are enforced in actions
  - task scope filters support all, owned, and following views

## Recent updates

### Task filtering and ownership behavior
- Added first-class scope filtering for task lists: all, owned, following.
- Preserved legacy mine query compatibility while moving toward scope as the primary contract.
- Ensured owner filtering is available to all authenticated users through user options endpoint support.

### Role-based UX and action controls
- Non-admin users can complete their own tasks.
- Users cannot follow their own tasks in the UI.
- Non-admin users can still follow or unfollow and comment on tasks they do not own.

### Architecture and consistency improvements
- Added dedicated request validation for task list queries.
- Moved viewer-specific follow-state enrichment into service orchestration.
- Kept repositories focused on persistence and query concerns.
- Extracted task-comment presentation helpers from the larger task list page into shared utilities.

### Documentation and maintainability
- Added and refreshed natural code comments in core backend and frontend files.
- Added intent comments in tests so future reviewers understand why each test exists.
- Updated top-level docs to match current behavior and limitations.

## Known limitations

1) Frontend maturity
- The dashboard UI is still basic and should be treated as a functional API client.
- It demonstrates behavior correctly, but visual polish and UX depth are limited.

2) Frontend stack age
- Vue 2 + Vue CLI is workable but not modern by current ecosystem standards.

3) Coverage depth
- Core task behavior is covered, but additional scenarios around auth lifecycle and analytics are still worthwhile.

## Suggested next improvements

### Technical quality
- Add CI automation for tests and linting.
- Expand feature tests for auth edge cases and dashboard aggregation behavior.
- Continue removing legacy compatibility branches once all clients use scope-only filtering.

### Frontend evolution
- Decompose large page components further.
- Improve loading and empty states across dashboard modules.
- Decide and document a migration path to Vue 3 + Vite, or formally defer it with rationale.

### Operational readiness
- Add clearer queue and reminder observability.
- Improve contributor docs for local setup variations and troubleshooting.
