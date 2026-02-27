---
name: ui-input-toggle-semantics
description: >-
  Enforces semantic usage of Switch vs Checkbox in forms and settings.
  Treat as a UI architecture constraint: Switch for immediate state mutation,
  Checkbox for grouped form selection and multi-select.
---

# UI Input Toggle Semantics

Source of truth: `AGENTS.md` section **UI Skill Rule: `ui-input-toggle-semantics`**.

## Purpose

Ensure `Switch` and `Checkbox` are used by semantic intent, not visual preference.

- `Switch` = immediate state mutation.
- `Checkbox` = form selection input (including boolean fields submitted with the form).

## Rule 1: Switch Usage

Use `Switch` when all of these are true:

1. The change applies immediately (no submit button required).
2. The control represents a real-time system state (`ON/OFF`).
3. The interaction is a binary setting (`enabled/disabled`).
4. The user expects instant feedback.

Examples:
- Enable notifications
- User active/inactive (if applied instantly)
- Dark mode
- Airplane mode
- Feature flags

Do not use `Checkbox` for immediate state toggling.

## Rule 2: Checkbox Usage

Use `Checkbox` when one or more of these apply:

1. The field belongs to a larger form submitted together.
2. The value does not apply until form submission.
3. Multiple selections are allowed.
4. It represents consent or agreement.

Examples:
- Accept terms and conditions
- Select permissions
- Subscribe to newsletter (inside a form)
- Select multiple items from a list

Do not use `Switch` as a replacement for multi-select or consent inputs.

## Canonical Checkbox Composition

Use this structure as the default implementation pattern:

1. Inline option:
   - `div.flex.items-center.gap-3`
   - `<Checkbox id=\"...\" />`
   - `<Label for=\"...\">...</Label>`
2. Option with description:
   - `div.flex.items-start.gap-3`
   - Checkbox + text block (`Label` + helper text paragraph).
3. Selectable card option:
   - Label wrapper pattern with `has-[[aria-checked=true]]` states is allowed for rich selectable cards.

### Consistency rule

- Do not create redundant labels for the same single checkbox.
- If a checkbox already has `Label for=\"id\"`, do not add another parent label for that exact control.
- Use a group label/legend only when the section contains multiple related options.

## Mandatory Decision Checklist

Before implementing any toggle-like control, explicitly evaluate:

1. Is this immediate?
2. Is this part of a grouped submission?
3. Is this multi-select?

If unclear:
- Default to `Checkbox` inside forms.
- Default to `Switch` for standalone settings.

## Review Heuristic

When reviewing form UI:

- Flag any `Switch` inside multi-select groups as incorrect.
- Flag any `Checkbox` used for immediate side effects as incorrect.
- Require semantic justification in PR comments for ambiguous cases.
