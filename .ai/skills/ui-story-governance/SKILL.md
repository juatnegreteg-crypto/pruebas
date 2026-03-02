---
name: ui-story-governance
description: Govern Histoire/Storybook stories with mandatory taxonomy, eligibility rules, and scenario standards. Use when creating, reviewing, or refactoring UI/composable stories, deciding whether a component deserves a story, or organizing story titles/fixtures.
---

# UI Story Governance

## Overview
Apply strict, low-noise rules for when stories exist, how they are titled, and what they must demonstrate. Optimize for clarity and reusability, not completeness.

## Eligibility Rules (Mandatory)
Create stories only for components that expose a public contract:
- Have meaningful props or slots.
- Have state variants (loading, empty, selected, disabled, etc.).
- Contain behavioral logic.
- Can be reused independently outside their parent context.

Do not create standalone stories for:
- Structural wrappers (`*Row`, `*Cell`, `*Item`) with no independent behavior.
- Internal-only components not intended as public API.

If a component is internal to a higher-level component, document it through the parent component story.

## Scope (Mandatory)
- Allowed: `resources/js/components/**`, `resources/js/composables/**`, `resources/js/layouts/**`.
- Disallowed: `resources/js/pages/**`.
- File convention: `.story.vue`.
- Do not add MD docs unless explicitly requested.

## Title Taxonomy (Mandatory)
Use `meta.title` with one of the following:
- `UI/<Category>/<Component>`
- `App/<Domain>/<Component>`
- `Patterns/<PatternName>`

Examples:
- `UI/Forms/Input`
- `App/Customers/CustomerStatusBadge`
- `Patterns/BulkActionsToolbar`

## Scenario Standard (Mandatory)
Each story must include exactly three variants unless the component does not support one of them:
1. Minimal: essential API only.
2. Realistic: typical usage with real-ish content.
3. Edge: boundary/rare case (long text, empty, loading, error, etc.).

## Fixtures (Mandatory)
- Centralize fixtures in `resources/js/stories/fixtures/`.
- Keep fixtures minimal and readable.
- Stories must import fixtures rather than inline large datasets.

## Pattern Stories
When you need to document an interaction pattern (e.g., table + filters + bulk actions), create a pattern story under `Patterns/...` without tying it to a page route.

## Decision Examples
- `AppTable`: YES (public API + states + behavior).
- `AppTableRow`: NO (structural child, document in `AppTable`).
- `AppTableRowCell`: NO (structural child, document in `AppTable`).
