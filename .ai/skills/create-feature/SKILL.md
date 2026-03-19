---
name: create-feature
description: Create a branch and plan for a new feature, saving it to .ai/plans/.
---

# Skill: create-feature

Create a branch and write a detailed plan for a new feature. Implementation happens separately with `/implement-feature`.

## Usage

```
/create-feature <feature-name> [main] [ClaudeModel]
```

### Parameters

- `<feature-name>` — (**required**) name of the feature, used for the branch and plan file name
- `main` — (**optional**) literal keyword; if present, stay on `main` instead of creating a feature branch
- `ClaudeModel` — (**optional**) model to use for planning, e.g. `opus` or `sonnet`; if present, skip asking about the model

If a parameter is missing, ask about it before proceeding.

## Steps

### 1. Setup

**Branch**: If the `main` keyword was NOT passed, ask the user: **"Should I create a new feature branch, or work directly on `main`?"**
  - **New branch**: `git checkout -b feature/<feature-name>`
  - **Stay on `main`**: skip branch creation

**Model**: If `ClaudeModel` was NOT passed, ask the user: **"Which model should I use for planning? (`opus` recommended, or `sonnet`)"**
  - Switch to the specified model via `/model`

> **PAUSE**: Wait for all missing parameter answers before continuing.

### 2. Confirmation

Before starting to plan, present a summary of what will be done:

- Feature name: `<feature-name>`
- Branch: `feature/<feature-name>` or `main`
- Model: `<ClaudeModel>`

Ask: **"Ready to start planning? (yes/no)"**

> **PAUSE**: Wait for confirmation before proceeding.

### 3. Planning

- Read `.ai/PLAN.md` to check if the feature is in the roadmap
- Enter **plan mode** to propose the approach
- Explore existing code to understand patterns in place
- Propose the list of files to create/modify, organized by phase (backend, frontend, tests)
- Save the plan to `.ai/plans/<feature-name>.md` (versioned in repo)

> **PAUSE**: "Plan saved to `.ai/plans/<feature-name>.md`. Review it, then switch to Sonnet (`/model`) and run `/implement-feature` to start implementation."
