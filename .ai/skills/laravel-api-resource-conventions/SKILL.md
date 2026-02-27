---
name: laravel-api-resource-conventions
description: >-
  Conventions for Laravel JsonResource classes for readability and consistency: explicit key ordering, conditional
  fields/relationships via whenLoaded/whenPivotLoaded/whenCounted, and repository-specific non-negotiable
  patterns for private helper methods.
---

# Laravel API Resource Conventions (Repo Rules)

Use this skill when refactoring files under `app/Http/Resources/**` (Laravel `JsonResource`).

## Non-Negotiables (Repo)

1. **Key ordering is explicit**
   - `toArray()` must return a single `return [` with keys in the desired order.
   - Do not use array unpacking (`...$array`) to compose the response.

2. **Private helper methods do not accept model parameters**
   - If a private method needs the model, it must read it from `$this->resource`.
   - Do not re-annotate `$this->resource` inside private methods when the Resource already has a class-level PHPDoc like:
     - `/** @property-read Quote $resource */`
   - Prefer direct access:
     - `return $this->resource->updated_at?->toIso8601String();`
   - Do not pass `$quote`, `$bundle`, etc. as parameters to private methods.

3. **Prefer Laravel Resource helpers over manual conditionals**
   - Relationships:
     - `whenLoaded('rel', fn () => Resource::make($this->resource->rel))`
   - Pivot/intermediate table fields:
     - `whenPivotLoaded('table', fn () => $this->pivot->field)`
   - Counts:
     - `whenCounted('items')`

4. **Nested relationship access in Resources**
   - If a key exists only when a relationship is loaded, prefer the simple pattern:
     - `return $this->whenLoaded('vehicle', fn () => CustomerResource::make($this->resource->vehicle->customer));`
   - This intentionally keeps the Resource simple and “Laravel-y”.
   - If nested relations must not lazy-load, enforce eager-loading at the caller (controller/query) instead of adding complex guards in the Resource.

5. **Scope discipline**
   - Only edit controllers/queries to change eager-loading if explicitly requested.
   - Resource refactors should be output-compatible unless the request explicitly allows payload changes.

6. **Models**
   - Model rules live in the `laravel-eloquent-model-conventions` skill.

7. **Resource key semantics (strict)**
   - Resource keys must represent real attributes or top-level DTO fields of the current resource.
   - Do **not** fake nested properties using dot semantics inside keys (e.g. `items.count`).
   - Aggregates belong to the owning resource, not to child collections:
     - Correct: `itemsCount`
     - Incorrect: `items.count`
   - Reasoning:
     - `count` is not an attribute of each `item`.
     - The aggregate is metadata of the parent resource payload.
     - Dot-like keying blurs boundaries and causes contract ambiguity for clients.

8. **When NOT to create a Resource**
   - Do not create `JsonResource` for technical/internal objects that are not API domain representations.
   - Value objects, service DTOs, infra integration payloads, and transient process data should stay as plain arrays/DTOs unless they are part of a stable public API response contract.
   - Use Resources only when shaping externally consumed API representations with clear ownership and semantics.

## Pattern Template

```php
public function toArray(Request $request): array
{
    /** @var Quote $quote */
    $quote = $this->resource;

    return [
        'id' => $quote->id,
        'vehicleId' => $quote->vehicle_id,
        'vehicle' => $this->vehicle(),
        'customer' => $this->customer(),
        'itemsCount' => $this->whenCounted('items'),
        'items' => QuoteItemResource::collection($this->whenLoaded('items')),
        'createdAt' => $quote->created_at?->toIso8601String(),
    ];
}

private function customer(): mixed
{
    return $this->whenLoaded(
        'vehicle',
        fn () => CustomerResource::make($this->resource->vehicle->customer),
    );
}
```

## Anti-Pattern to Avoid

```php
return [
    'items' => QuoteItemResource::collection($this->whenLoaded('items')),
    'items.count' => $this->whenCounted('items'), // ❌ never do this
];
```
