---
name: github-workflow-naming-standard
description: Defines a strict and self-explanatory naming convention for GitHub Actions workflows.
version: 1.0.0
---

# GitHub Workflow Naming Standard

## Purpose

Define a strict and self-explanatory naming convention for all GitHub Actions workflows.

The goal is:

- Ensure clarity about **who is affected**
- Make explicit **what is being modified**
- Clarify **what action is performed**
- Make names splittable and semantically understandable
- Avoid ambiguity between PR domain logic and Project projections

---

## Core Principle

Workflows must reflect architecture boundaries:

- PR is the source of truth for code lifecycle.
- Project fields (Status / Integration Status) are projections.
- Guards validate projections.
- Sync workflows derive state.
- Mutations must be explicit.

---

## Workflow Naming Format

Use the following structure:

```

<scope>.<target>.<actor>.<action>[.<qualifier>].yml

```

### Definitions

- **scope** → what entity is modified
    - `project`
    - `pr`
    - `issue`
    - `repo`
    - `ci`

- **target** → specific domain area affected
    - `status`
    - `integration-status`
    - `merge`
    - `labels`
    - `checks`
    - etc.

- **actor** → source of truth or trigger origin
    - `pr`
    - `issue`
    - `project`
    - `ci`
    - `manual`
    - `from-pr`
    - `from-project`

- **action** → what the workflow does
    - `sync`
    - `guard`
    - `merge`
    - `notify`
    - `enforce`

- **qualifier** (optional) → state or condition
    - `done`
    - `in-review`
    - `on-label`
    - `on-merge`
    - etc.

---

## Examples (Correct Usage)

### Project Status Guards

```

project.status.issue.guard.done.yml
project.status.issue.guard.in-review.yml

```

Meaning:

- Affects Project
- Target: Status field
- Applies to Issue items
- Action: Guard (validate & revert if invalid)
- Qualifier: Done / In review

---

### Integration Status Sync (Derived from PR)

```

project.integration-status.pr.sync.yml

```

or explicitly:

```

project.integration-status.sync.from-pr.yml

```

Meaning:

- Affects Project
- Target: Integration Status
- Source of truth: PR
- Action: Sync (derive state from PR lifecycle)

---

### PR Automerge

```

pr.merge.automerge.on-label.yml

```

Meaning:

- Affects PR
- Target: Merge operation
- Trigger: Label
- Action: Merge

---

## Architectural Constraints

1. Project workflows must NOT mutate PR state.
2. PR workflows may mutate PR state explicitly (e.g., merge).
3. Guards must only validate and revert projections.
4. Sync workflows must be deterministic and idempotent.
5. Avoid bidirectional state coupling.

---

## Required Metadata

Each workflow must include a clear `name:` field:

Example:

```

name: Project Status Guard (Issue Done)

```

The `name:` should describe intent, not just technical action.

---

## Enforcement Rule

When creating or modifying a workflow:

- Follow the naming format strictly.
- Do not use ambiguous names like:
    - `sync.yml`
    - `automation.yml`
    - `update-status.yml`
- Names must be semantically parseable.

---

## Philosophy

The board is a projection.
The PR is the domain.

Workflows must reflect this separation.
