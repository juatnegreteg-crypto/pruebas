---
name: vue-component-naming-standard
description: >-
  Standardize Vue 3 component naming for readability and discoverability, using
  the repository ADR that adopts selected Vue 2 naming conventions. Use when
  creating, renaming, or reviewing Vue SFC components; especially for:
  (1) single-instance component names, (2) tightly-coupled child component
  names, and (3) consistent noun-first ordering in component names.
---

# Vue Component Naming Standard (Vue 3)

Source of truth: `AGENTS.md` ADR "Vue 3 Style Guide (Adopting Vue 2 Naming Conventions)".

## Compatibility Rule

- Vue 3 best practices override any Vue 2 guideline.
- Adopt Vue 2 naming/readability rules only when they improve readability and do
  not conflict with Vue 3 patterns used here (Composition API, `<script setup>`,
  Inertia).

Reject guidance if it:

- Assumes Options API structure as a requirement (for example, rules that only
  work with `export default { ... }`).
- Pushes runtime-only naming conventions over consistent file/import naming.
- Encourages generic names that reduce discoverability in a larger codebase.

## Rule 1: Single-Instance Component Names

**Definition:** A single-instance component is unique in meaning and typically
rendered once per page or once per app shell. Its name must be explicit and
unique to its context.

**How to apply (decision procedure):**

1. Ask: would this name still be unambiguous if you saw it in a stack trace, an
   import list, or a file search?
2. If the answer is no: treat it as single-instance and name it explicitly for
   the context (app area, page area, or purpose).
3. If the answer is yes (it is broadly reusable): do not force single-instance
   naming; keep it reusable and rely on Rule 3 ordering.

**Examples (good vs bad):**

- Good: `AppSidebar`, `SettingsSidebar`, `TheHeading`, `UserMenuContent`
- Bad: `Sidebar`, `Heading`, `Content`, `Index`

**File naming guidance:** Use `PascalCase` filenames that exactly match the
component name: `AppSidebar.vue`, `SettingsSidebar.vue`.

**Vue 3 note:** These rules apply regardless of authoring style (Composition API
and `<script setup>` included). Do not require Options API-only patterns.

## Rule 2: Tightly-Coupled Component Names (Parent Prefix)

**Definition:** A tightly-coupled child component is only meaningful within a
single parent component. Prefix the child name with the parent name.

**How to apply (decision procedure):**

1. Ask: would this component be reasonable to reuse outside the parent without
   becoming confusing or over-specific?
2. If no: it is tightly-coupled. Prefix with the parent component name.
3. If yes: it is reusable. Do not prefix; use a reusable noun-first name (Rule 3).

**Examples (good vs bad):**

- Good: `AppSidebarHeader`, `AppSidebarNavItem`, `TodoListItem`, `TodoListItemButton`
- Bad: `SidebarHeader`, `NavItem`, `ListItem`, `ItemButton`

**File naming guidance:** Keep the prefix in the filename too:
`AppSidebarHeader.vue`. When possible, keep tightly-coupled children near the
parent in the filesystem for discoverability.

**Vue 3 note:** Coupling is about meaning and usage, not the Options API. A
tightly-coupled child is still a normal SFC (often `<script setup>`).

## Rule 3: Order Of Words In Component Names (Noun First)

**Definition:** Start with the primary object (noun), then add qualifiers in a
consistent order (noun first, then qualifier).

**How to apply (decision procedure):**

1. Identify the primary object: `Sidebar`, `NavItem`, `Menu`, `Button`, `Form`.
2. Add qualifiers after the noun, ordered from most structural to most specific.
3. If tightly-coupled, apply Rule 2 first (parent prefix), then keep noun-first
   ordering within the remainder of the name.
4. Prefer full words over abbreviations to keep names searchable.

**Examples (good vs bad):**

- Good: `SearchButtonClear`, `SettingsNavItem`, `UserMenuContent`
- Bad: `ClearSearchButton`, `NavItemSettings`, `ContentUserMenu`

**File naming guidance:** Use `PascalCase` filenames matching the component:
`SearchButtonClear.vue`.

**Vue 3 note:** This is a readability rule for code review and file navigation;
it does not depend on component internals.

## Quick Checklist (Naming Review)

- Did you classify the component as single-instance vs reusable vs tightly-coupled?
- If single-instance: is the name explicit and unique (not generic)?
- If tightly-coupled: does the name start with the parent component name?
- Is the name noun-first with consistent qualifier ordering?
- Does it use full words (avoid abbreviations) to stay searchable?
- Is the filename `PascalCase` and exactly matches the component name?
- Did you `rg` the codebase to reuse an existing pattern before adding a new name?

