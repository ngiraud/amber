---
name: complete-feature
description: Update plans and commit changes for a completed feature.
---

# Skill: complete-feature

Update plans and create a commit for the completed feature.

## Usage

```
/complete-feature
```

## Steps

### 1. Update plans

- If `.ai/PLAN.md` contains the feature, mark it as done
- Delete the temporary plan file `.ai/plans/<feature>.md` if it exists

### 2. Commit

1. `git status` to review all changes
2. `git diff` to review staged and unstaged changes
3. Stage relevant files (never `.env`, credentials, etc.)
4. Create commit with conventional message:
   - `feat: <description>` for a new feature
   - `fix: <description>` for a bugfix
   - `refactor: <description>` for refactoring
