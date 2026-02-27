---
name: laravel-eloquent-model-conventions
description: >-
  Repository conventions for Laravel Eloquent models: avoid manual @property PHPDoc, prefer casts and
  accessors (Attribute) for computed fields, use ObservedBy for model-side effects, and keep query
  filtering/sorting logic in local scopes.
---

# Laravel Eloquent Model Conventions (Repo Rules)

Use this skill when editing files under `app/Models/**`.

## Non-Negotiables (Repo)

1. **No manual `@property` / `@property-read` blocks**
   - Do not add PHPDoc property lists to Eloquent models to “teach” IDEs about columns/relations.
   - Rely on tooling that reads the real database schema and the model definition.
   - Keep models free of manual property docs; use real schema, casts, accessors, relations, and observers.
   - Exception: allow a minimal `@property-read` entry for polymorphic relations when the relation method returns `MorphTo` and IDE/static analysis cannot infer union targets (for example `Product|Service|Bundle`). Keep it limited to that relation only.

2. **Computed fields must be accessors/casts (not PHPDoc)**
   - If a field is not a database column, define it as an accessor using `Attribute`.
   - If it represents a casted value, use `$casts` (current project preference).
   - Use decimal scales explicitly (`decimal:2`, `decimal:4`) based on domain precision.

3. **Use `$appends` only when it must be serialized**
   - If a computed field must appear in arrays/JSON, add it to `$appends`.
   - Otherwise, leave it as an accessor-only field.

4. **Observers are first-class for write-side calculations**
   - Prefer model observers for derived-field sync and side effects (e.g., quote item totals / quote aggregate totals).
   - Register observer via `#[ObservedBy([...])]` in the model when the model owns the behavior.
   - Avoid duplicating the same calculation in controllers/services when observer already handles it.

5. **Scopes own query concerns**
   - For text search, keep DB-driver-aware operator selection (`ilike` for pgsql, `like` for others).
   - Sort/filter allow-lists and normalization belong to Form Requests; scopes should consume already validated input.
   - When creating new domain-specific scopes, follow the naming convention below.

6. **Relationship signatures stay explicit**
   - Always use explicit relation return types (`BelongsTo`, `HasMany`, `MorphToMany`, etc.).
   - For polymorphic/complex relations, short PHPDoc on the relation method is acceptable when it adds type clarity.

7. **Catalog detail models follow shared structure**
   - For models backed by `*_details` tables, keep:
     - explicit `$table`
     - custom `$primaryKey` (`catalog_item_id`)
     - `public $incrementing = false`
     - `$keyType = 'int'`
   - Reuse `CatalogItemAttributes` trait for shared cross-catalog attributes.

## Scope Naming Convention (Required for New Scopes)

When a user asks for a new Eloquent scope, generate names with this semantic convention:

1. **Exact lookup:** `by<Noun>(value)`
   - Example: `byId(123)`, `byEmail($email)`

2. **State / boolean condition:** `thatAre<Noun|Adjective>()`
   - Example: `thatAreActive()`, `thatAreArchived()`

3. **Relationship existence:** `thatHave<Noun>()` / `thatDontHave<Noun>()`
   - Example: `thatHaveDocuments()`, `thatDontHaveInvoices()`

4. **In-list filter:** `thatAreIn<Noun>(array $values)`
   - Example: `thatAreInStatuses(['pending', 'approved'])`

5. **Minimum/maximum thresholds:** `atLeastThis<Noun>(value)` / `atMostThis<Noun>(value)`
   - Example: `atLeastThisAge(18)`, `atMostThisPrice(1000)`

6. **Direct comparisons:** `thatAreGreaterThan<Noun>(value)` / `thatAreLessThan<Noun>(value)`
   - Example: `thatAreGreaterThanPrice(100)`, `thatAreLessThanStock(5)`

7. **Range filter:** `thatAreBetween<Noun>(min, max)`
   - Example: `thatAreBetweenAges(18, 30)`

8. **Ordering:** `orderedBy<Noun>(string $direction = 'asc')`
   - Example: `orderedByName()`, `orderedByCreatedAt('desc')`
   - `orderedBy(...)` without suffix is acceptable when the field and direction are validated/normalized before reaching the scope.

### Additional Rules for Scopes

- Keep a clear distinction between `by<Noun>()` (exact lookup) and broader filtering scopes.
- Handle `null` explicitly:
  - If the value is `null`, either skip the filter intentionally (`->when(...)`) or use `whereNull(...)` / `whereNotNull(...)` depending on intent.
- If user intent is ambiguous (exact match vs partial search, include null vs ignore null), ask before implementing.
- Do not create scope names outside this convention unless the codebase already has a fixed public contract that must be preserved.

### Enforcement Note

- Do not introduce or preserve legacy generic names such as `scopeSearch` / `scopeSort`.
- When touching an existing model that still has legacy scope names, refactor them to this naming convention and update all call sites in the same change.

### Legacy Migration Checklist

- Rename legacy scopes to convention-compliant names.
- Update all call sites in controllers/services in the same PR.
- Move sorting/filtering allow-lists to the corresponding `FormRequest` if they still live in scopes.
- Add or update tests to enforce the naming convention and request validation behavior.

## Example: Accessor (Computed Field)

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function statusLabel(): Attribute
{
    return Attribute::get(fn () => $this->status?->label());
}
```

If it must be included in arrays/JSON:
- Add `protected $appends = ['status_label'];` (or follow existing project convention).
