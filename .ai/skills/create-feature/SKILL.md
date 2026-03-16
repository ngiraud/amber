---
name: create-feature
description: Create a branch and plan for a new feature, saving it to .ai/plans/.
---

# Skill: create-feature

Create a branch and write a detailed plan for a new feature. Implementation happens separately with `/implement-feature`.

## Usage

```
/create-feature <feature-name>
```

## Steps

### 1. Setup

- Ensure you are on the Opus model for planning (`/model` to check/switch)
- Ask the user: **"Should I create a new feature branch, or work directly on `main`?"**
  - **New branch**: `git checkout -b feature/<feature-name>`
  - **Stay on `main`**: skip branch creation

> **PAUSE**: Ensure you are on the Opus model for planning (`/model` to check/switch). Wait for OK.

### 2. Planning

- Read `.ai/PLAN.md` to check if the feature is in the roadmap
- Enter **plan mode** to propose the approach
- Explore existing code to understand patterns in place
- Propose the list of files to create/modify, organized by phase (backend, frontend, tests)
- Save the plan to `.ai/plans/<feature-name>.md` (versioned in repo)

> **PAUSE**: "Plan saved to `.ai/plans/<feature-name>.md`. Review it, then switch to Sonnet (`/model`) and run `/implement-feature` to start implementation."
