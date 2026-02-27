---
name: ui-action-governance
description: Standardize UI actions, icons, and naming in the Vue 3 app. Use when creating or refactoring action buttons/menus, mapping icons/variants for standard actions, deciding App* vs domain component names, or choosing composables vs data-provider patterns.
---

# UI Action Governance

## Overview
Keep action UX consistent and discoverable by centralizing action contracts (icons/variants), using App* wrappers, enforcing naming rules, and choosing the right data‑sharing pattern.

## Action Governance (Mandatory)
1. **Never hardcode Lucide icons for standard actions in templates.**
2. Always import the action map:
   - `import { Actions } from "@/types/actions/action-map";`
3. Use wrappers instead of raw `Button` for standard actions:
   - `AppActionButton` for icon+label
   - `AppActionIconButton` for icon-only + tooltip
   - `AppActionMenuItem` for dropdown actions
4. Labels are **module‑scoped i18n keys** (no global labels).

## Standard Action Map (Source of Truth)
Action IDs and icons/variants live in `resources/js/types/actions/action-map.ts`.
Never duplicate this mapping elsewhere.

## Naming Rules (ADR‑Aligned)
1. **App‑level base components**: prefix with `App*` (e.g., `AppActionButton`).
2. **Domain reusable components**: domain noun first (e.g., `VehicleCombobox`, `CustomerOptionRow`).
3. **Tightly‑coupled children**: prefix with parent (e.g., `VehiclePickerOptionRow`).
4. **Order of words**: noun first, qualifiers after.

## Data Provider vs Composable (Decision Rule)
Use **composables by default**.

Choose **Data Provider** only when:
- The **same data + loading/error state** must feed **multiple UIs** via slots, or
- You need renderless composition without prop‑drilling.

Otherwise:
- Use a composable with an injectable `fetcher` and keep UI presentational.

### Non-View Components (Mandatory)
- Components that are **not pages/views** must **avoid direct data fetching**.
- Keep them presentational and accept data via props.
- Data origin (API vs mock vs alternate query) must live in the page or composable.

### Decision Checklist (Fast)
1. **Single UI consumer?** → Composable.
2. **Multiple UIs need the exact same state?** → Data Provider.
3. **State must stay in sync across distant branches?** → Data Provider (renderless).
4. **Only data fetching differs (A/B/mock)?** → Composable with injectable `fetcher`.

## Examples (Template‑Only)
Toolbar:
```vue
<AppPageActions>
  <AppActionButton :action="Actions.create" :label-key="'customers.index.actions.create'" />
  <AppActionButton :action="Actions.export" :label-key="'customers.index.actions.export'" />
</AppPageActions>
```

Row actions:
```vue
<AppRowActions>
  <AppActionIconButton :action="Actions.edit" :label-key="'customers.index.actions.edit'" />
  <AppActionIconButton :action="Actions.delete" :label-key="'customers.index.actions.delete'" />
</AppRowActions>
```
