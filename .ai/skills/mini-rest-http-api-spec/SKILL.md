---
name: mini-rest-http-api-spec
description: >-
  Define and enforce a minimal, consistent REST HTTP API contract for this
  codebase. Use when designing or reviewing API endpoints, query parameters
  (pagination/filter/sort/include/fields/embed/extend), response envelopes, and
  error shapes; especially when mapping camelCase public API contracts to
  snake_case persistence/Eloquent at boundaries with strict allow-lists.
---

# Mini REST HTTP API Spec

This is a framework-agnostic mini spec designed to be Laravel-friendly.

## Overview (Principles)

- **Public API contract is camelCase**: response attributes and all query keys (controls + criteria).
- **Persistence is snake_case**: DB columns, Eloquent attributes, relationship keys.
- **Explicit boundary mapping only**:
  - Request/query parser maps camelCase API names -> internal neutral Query DTO (camelCase).
  - Repository adapters map Query DTO -> Eloquent (snake_case, relations).
  - Services never see snake_case; models never see camelCase.
- **All criteria MUST be under `filter[...]`**, except `q` (see “`q` Handling”).
- **Allow-lists per resource**: deny unknowns by default, return standardized errors.

## Response Shapes

### Collection Response

```json
{
  "data": [
    { "id": 123, "fullName": "Ada Lovelace" }
  ],
  "meta": {
    "page": 1,
    "perPage": 15,
    "total": 42
  },
  "links": {
    "first": "https://example.test/api/v1/customers?page=1",
    "prev": null,
    "next": "https://example.test/api/v1/customers?page=2",
    "last": "https://example.test/api/v1/customers?page=3"
  }
}
```

### Single Resource Response

```json
{
  "data": { "id": 123, "fullName": "Ada Lovelace" }
}
```

### Error Response (RFC 9457 Problem Details)

Content-Type: `application/problem+json`

```json
{
  "type": "https://example.test/problems/invalid-query",
  "title": "Invalid query parameters",
  "status": 400,
  "detail": "One or more query parameters are invalid.",
  "instance": "/api/v1/vehicles",
  "errors": [
    { "source": "filter[customer.name][like]", "code": "unknown_filter", "message": "Filter is not allowed." },
    { "source": "include", "code": "unknown_include", "message": "Relationship include is not allowed." }
  ]
}
```

Validation failures use the same Problem Details envelope (status `422`) with field paths in `errors[*].source`.

## Query Parameters

### Reserved Params (Not Valid Filter Keys)

Reserved params are **controls**, not criteria:

- `page`
- `perPage`
- `sort`
- `include`
- `fields`
- `embed`
- `extend`
- `q` (special top-level search expression; not a filter key)

These MUST NOT appear inside `filter[...]` (reject with `invalid_query`).

Naming note:

- Control params are also camelCase (for example `perPage`, not `per_page`).
- Reject snake_case query keys by default. If legacy aliases are needed, normalize
  them at the boundary only and treat them as deprecated.

### Pagination

- `page` (default: `1`, min: `1`)
- `perPage` (default per resource, max per resource)

### Filtering (All Criteria Under `filter[...]`)

Grammar (conceptual):

```text
filter[<fieldPath>]=<value>                         // eq (default)
filter[<fieldPath>][<operator>]=<value>
```

Rules:

- **All criteria keys are camelCase**.
- `<fieldPath>` MUST refer to an allow-listed field on the resource (or allow-listed relation dot-path).
  - Do not invent synthetic keys that are not part of the resource/relations being filtered.
- `<fieldPath>` supports dot paths for nested relations:
  - `customer.name`
  - `customer.documentNumber`
- **Do NOT support wildcard/index paths**:
  - Reject `customers.*.name`
  - Reject `customers.2.name`
- For to-many relations, nested filters use **ANY/EXISTS** semantics by default.
- Unknown filters/operators are rejected (400) unless explicitly allow-listed.

#### Operators (Minimum Set)

- `eq` (default if no operator)
- `ne`
- `in` (comma-separated values)
- `lt`, `lte`, `gt`, `gte`
- `like` (case-insensitive by spec)
- `between` (two values, comma-separated)
- `is_null` (`true`/`false`)

**Case-insensitive searching**:
- `like` is case-insensitive by contract.
- Implementation is DB-dependent (collation, lowercasing, or driver-specific operator), but the API semantics must be consistent.

### `q` Handling

**Allowed as a single exception**: `q=<expression>`

- `q` is a free-text search expression, not a resource attribute.
- `q` is the only allowed “loose criteria” key. All other criteria stay under `filter[...]`.
- Services should receive `q` as a neutral Query DTO field (camelCase), and repository adapters decide how `q` maps to DB fields (allow-list the searchable fields).
- Do NOT accept `filter[q]` (reject or normalize at the boundary only during migrations).

Justification: `q` is used to represent a cross-field search expression, and keeping it as a single top-level key avoids implying it is a real resource attribute.

### Sorting

`sort=field,-field2`

- Field names are camelCase.
- Leading `-` means DESC.
- Unknown sorts are rejected unless allow-listed.

### Includes (Relationships Only)

`include=customer,customer.vehicles`

Rules:

- Only relationships. No computed blocks.
- Supports dotted include paths.
- Enforce max include depth and allow-list includes per resource.

### Sparse Fields

`fields[customers]=id,fullName,documentNumber`

Rules:

- `fields[<resourceName>]` is a comma-separated allow-list.
- Unknown fields are rejected.
- Relationship sparse fields use the relationship resource name:
  - `fields[customers]=id,fullName`
  - `fields[vehicles]=id,plate`

### Embed (Computed / Derived Sections)

`embed=summary,stats`

- Embed is for computed/derived sections that are not relationships.
- Only allow-listed keys; unknown embeds rejected.

### Extend (Expensive Optional Blocks)

`extend=auditTrail,pricingBreakdown`

- Extend is for expensive computed fields/blocks.
- Only allow-listed keys; unknown extends rejected.

## Allow-Lists And Error Behavior

Each resource must define:

- `allowedFilters` (fieldPath + allowed operators)
- `allowedSorts`
- `allowedIncludes`
- `allowedEmbeds`
- `allowedExtends`
- `allowedFields` (per resource)
- `maxPerPage`
- Guardrails: max number of filters/sorts/includes; max include depth

Default behavior:

- Deny unknown filter keys / operators / sorts / includes / embeds / extends / fields.
- Respond with RFC 9457 Problem Details and structured `errors[]`.

## Security / Performance Guardrails

Recommended defaults (per resource, configurable):

- Max include depth: `2`
- Max includes: `5`
- Max sort fields: `3`
- Max filter conditions: `10`
- `maxPerPage`: `100`
- Deny unknown fields by default (do not “ignore silently”)

## Nested Filtering Examples

BelongsTo:

```text
GET /api/v1/vehicles?filter[customer.name][like]=neder
```

To-many (ANY semantics):

```text
GET /api/v1/customers?filter[vehicles.plate][like]=ABC
```

## Copy/Paste Endpoint Examples

### 1) GET /api/v1/customers

Request:

```text
GET /api/v1/customers?page=1&perPage=15&sort=fullName&filter[fullName][like]=ana
```

Response:

```json
{
  "data": [{ "id": 1, "fullName": "Ana Gomez" }],
  "meta": { "page": 1, "perPage": 15, "total": 1 },
  "links": { "first": "...", "prev": null, "next": null, "last": "..." }
}
```

### 2) GET /api/v1/customers/{id}

Request:

```text
GET /api/v1/customers/1
```

Response:

```json
{ "data": { "id": 1, "fullName": "Ana Gomez", "documentNumber": "123" } }
```

### 3) GET /api/v1/vehicles (nested filter + include + sort + pagination + sparse fields)

Request:

```text
GET /api/v1/vehicles?page=2&perPage=10&sort=-createdAt&include=customer&filter[customer.name][like]=neder&fields[vehicles]=id,plate,createdAt,customerId&fields[customers]=id,name
```

Response:

```json
{
  "data": [
    {
      "id": 10,
      "plate": "ABC-123",
      "createdAt": "2026-02-01T12:00:00Z",
      "customerId": 1,
      "customer": { "id": 1, "name": "Neder Perez" }
    }
  ],
  "meta": { "page": 2, "perPage": 10, "total": 34 },
  "links": { "first": "...", "prev": "...", "next": "...", "last": "..." }
}
```

## XLSX Export Endpoints (Architectural Rule)

For XLSX exports, only these endpoint patterns are allowed:

1. Representation-based export (synchronous):
   - `GET /api/v1/{resource}?format=xlsx`
   - or via `Accept` negotiation with an XLSX media type.
2. Resource-based export (asynchronous lifecycle):
   - `POST /api/v1/{resource}-exports`
   - `GET /api/v1/{resource}-exports/{id}`
   - `GET /api/v1/{resource}-exports/{id}?format=xlsx`
   - or `Accept` negotiation on `GET /api/v1/{resource}-exports/{id}`.

Do not use action-style endpoints such as `/{resource}/export` unless an ADR documents a hard constraint that prevents the two approved patterns.

## Error Examples (Copy/Paste)

### Invalid filter key

Request:

```text
GET /api/v1/vehicles?filter[customer.*.name][like]=x
```

Response:

```json
{
  "type": "https://example.test/problems/invalid-query",
  "title": "Invalid query parameters",
  "status": 400,
  "detail": "One or more query parameters are invalid.",
  "instance": "/api/v1/vehicles",
  "errors": [
    {
      "source": "filter[customer.*.name][like]",
      "code": "invalid_filter_path",
      "message": "Wildcard/index paths are not supported. Use dot paths only."
    }
  ]
}
```

### Unknown include

Request:

```text
GET /api/v1/vehicles?include=customer,secrets
```

Response:

```json
{
  "type": "https://example.test/problems/invalid-query",
  "title": "Invalid query parameters",
  "status": 400,
  "detail": "One or more query parameters are invalid.",
  "instance": "/api/v1/vehicles",
  "errors": [
    { "source": "include", "code": "unknown_include", "message": "Relationship include is not allowed: secrets." }
  ]
}
```

### Validation failure (422)

```json
{
  "type": "https://example.test/problems/validation-failed",
  "title": "Validation failed",
  "status": 422,
  "detail": "The given data was invalid.",
  "instance": "/api/v1/vehicles",
  "errors": [
    { "source": "data.plate", "code": "required", "message": "Plate is required." }
  ]
}
```

## Implementation Blueprint (No Full Code)

The spec and the implementation approach are intentionally documented separately.

See `REFERENCES.md` for the implementation blueprint and boundary mapping guidance.
