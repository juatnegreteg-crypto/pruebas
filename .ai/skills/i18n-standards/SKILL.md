---
name: i18n-standards
description: >-
  Enforces UI i18n usage, enum translation boundaries, export header localization,
  and the translation bundle API contract.
---

# I18n Standards (Repo Rule)

Use this skill when touching UI text, enums, exports, or translation bundles.

## Non-Negotiables

1. **UI strings are never hardcoded**
   - Vue/Blade/React must render text via i18n keys (`t()` / `__()`), including
     labels, placeholders, helper text, and status chips.
   - If a legacy exception is unavoidable, use `// i18n-exempt-next-line`.
   - Exemptions are forbidden in strict/prod linting.

2. **APIs never translate enum labels**
   - API payloads return enum `value` only.
   - Frontend maps to translations via i18n keys.

3. **Enum key structure is nested**
   - Each enum scope uses:
     - `label`
     - `values.<value>`
   - Example: `catalog.unit.label`, `catalog.unit.values.gram`.

4. **Current enum scopes**
   - `catalog.unit`
   - `customers.documentType`
   - `customers.addressType`
   - `dayOfWeek`
   - `quotes.status`
   - `technicians.appointment.status`

5. **Exports are localized server-side**
   - XLSX headings and status labels use `lang/{locale}/exports.php`.
   - API export endpoints accept `locale` and use it for translation.

6. **Translation bundle API**
   - `GET /api/v1/i18n` returns available locales.
   - `GET /api/v1/i18n/{locale}` returns `{ locale, version, messages }`.
   - Uses `ETag` and `Last-Modified` for caching.
   - Access via `Authorization: Bearer <api-client-key>`.

## Implementation Checklist

- Add new strings under `lang/{locale}/messages.php` (UI) or
  `lang/{locale}/exports.php` (exports).
- Re-run `php artisan app:export-i18n` to regenerate `resources/js/i18n/messages/*.ts`.
- Never add translated labels to API resources; pass raw enum values only.
- When adding enums, create i18n keys in both `en` and `es`.

## Linting

- ESLint rule `local/i18n-no-hardcoded` is the enforcement mechanism.
- Use `I18N_STRICT=true` in CI to disallow exemptions.
