---
name: laravel-xlsx-export-architecture
description: >-
  Standardize XLSX export architecture in Laravel APIs using Maatwebsite/Laravel-Excel with two allowed REST
  endpoint strategies, shared backend abstractions, sync-vs-async rules, policy enforcement, and required
  Pest coverage for authorization, headers, format validation, and async lifecycle.
---

# Laravel XLSX Export Architecture

Use this skill when adding or refactoring API XLSX exports.

## Endpoint Strategy (Only Two Allowed)

1. Representation-based, synchronous:
   - `GET /api/v1/{resource}?format=xlsx`
   - Or `Accept` content negotiation for XLSX.
2. Resource-based, async lifecycle:
   - `POST /api/v1/{resource}-exports`
   - `GET /api/v1/{resource}-exports/{id}`
   - `GET /api/v1/{resource}-exports/{id}?format=xlsx`
   - Or `Accept` content negotiation on `GET /api/v1/{resource}-exports/{id}`.

Action endpoints like `/{resource}/export` are disallowed unless an ADR documents a hard constraint.

## Backend Standards (Laravel + Laravel-Excel)

1. Use `maatwebsite/excel` for XLSX generation.
2. Keep export orchestration in a shared service/base abstraction.
3. Keep resource-specific column mapping and query setup in resource-specific classes.
4. Define and document defaults:
   - Chunk size default (for large datasets).
   - Sync vs async threshold (row count or execution-time heuristic).
5. Apply consistent filename convention:
   - `{resource}-export-{YYYYMMDD-HHmmss}.xlsx` (ASCII-safe).
6. Return correct headers for XLSX download responses, including content type and content disposition.
7. Enforce authorization through policies/gates for both creation and download.

## Async Lifecycle Rules

1. `POST /{resource}-exports` creates an export record/job and returns lifecycle metadata.
2. Status endpoint exposes finite states (for example: `pending`, `processing`, `completed`, `failed`).
3. File representation is only valid when state is `completed`.
4. Failures must produce a consistent API error shape (Problem Details when applicable).

## Testing Matrix (Required)

Use Pest feature tests. Minimum required coverage:

1. Authorization:
   - Forbidden user cannot create export.
   - Forbidden user cannot download export.
2. Format validation:
   - Unsupported format is rejected.
3. Headers:
   - Download response has expected download semantics and XLSX content type.
4. Async lifecycle (if async pattern is used):
   - Job dispatch/creation.
   - Status transition visibility.
   - Download allowed only after completion.

## Composition With Other Skills

1. Endpoint and API envelope semantics:
   - Use `mini-rest-http-api-spec`.
2. Resource payload conventions:
   - Use `laravel-api-resource-conventions`.
3. Test style and assertions:
   - Use `pest-testing`.
4. Vue frontend behavior for async export actions:
   - Use `inertia-vue-development`.

## Documentation Rule

Do not create per-issue docs files.  
If endpoint style, threshold policy, or lifecycle semantics change, add or update an ADR under `docs/adr/` with:

1. Context
2. Decision
3. Constraints
4. Consequences
