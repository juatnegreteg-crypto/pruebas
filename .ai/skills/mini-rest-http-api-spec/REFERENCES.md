# Implementation Blueprint (No Full Code)

Keep the spec contract separate from the implementation details. This file is
the implementation companion to `SKILL.md`.

## 1) Transport Parsers -> Neutral Query DTO (camelCase)

Goal: parse HTTP query params into a neutral `QueryDto` (camelCase) that the
service layer can consume without knowing persistence names.

HTTP:

- Use a `FormRequest` or dedicated `QueryParser`.
- Validate reserved controls vs criteria:
  - Controls: `page`, `perPage`, `sort`, `include`, `fields`, `embed`, `extend`.
  - Criteria: `filter[...]` only.
- Reject unknown controls and unknown filter keys by default (allow-list).
- Produce `QueryDto` (camelCase):
  - `page`, `perPage`
  - `filters[]`: `{ fieldPath, operator, value }`
  - `sort[]`: `{ field, direction }`
  - `include[]`, `embed[]`, `extend[]`
  - `fieldsByResource`: `{ [resourceName]: string[] }`

CLI:

- Parse CLI args into the same `QueryDto` shape.

Note on aliases:

- If the system must accept legacy snake_case query keys (for example `per_page`),
  normalize them to camelCase **at the boundary only** and treat them as deprecated.

## 2) Service Layer Consumes Query DTO

- Services accept `QueryDto` only (camelCase).
- Services may enforce business defaults (default sort, default include policy).
- Services must not apply persistence mapping or reference DB column names.

## 3) Repository Adapters Translate Query DTO -> Eloquent (snake_case)

Each resource gets an adapter responsible for translating:

- API field paths (camelCase, dot paths) -> Eloquent columns/relations (snake_case).
- Filter operators -> query builder clauses.

Per-resource explicit maps:

```text
apiFieldMap:
  id -> vehicles.id
  createdAt -> vehicles.created_at
  customerId -> vehicles.customer_id

  // belongsTo relation dot paths:
  customer.name -> customer.full_name
  customer.documentNumber -> customer.document_number
```

Implementation notes:

- Apply allow-lists before building queries:
  - `allowedFilters`: fieldPath + allowed operators
  - `allowedSorts`
  - `allowedIncludes`
  - `allowedFields` (sparse fields per resource)
  - `allowedEmbeds`, `allowedExtends`
  - `maxPerPage`, max counts, max include depth
- Dot-path filters:
  - `customer.name` -> `whereHas('customer', ...)`
  - To-many defaults to EXISTS/ANY semantics:
    - `vehicles.plate` -> `whereHas('vehicles', ...)`
- `like` is case-insensitive by contract:
  - Implement via collation, `LOWER(...)` normalization, or driver-specific ops.
  - Do not leak DB-specific operators into the API contract.
- Always eager-load allowed includes to avoid N+1.

## 4) Output Mapping (Resources/DTO)

Boundary mapping for responses happens in Resources/DTO mappers:

- Translate persistence names (snake_case) -> API names (camelCase).
- Do not introduce ad-hoc alias fields per consumer; prefer:
  - `include` for relationships
  - `fields[...]` for sparse payloads
  - `embed`/`extend` for computed or expensive blocks

