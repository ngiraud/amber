---
name: create-pr
description: Push branch and create a pull request on GitHub.
---

# Skill: create-pr

Push the current branch and create a pull request on GitHub.

## Usage

```
/create-pr [--skip-ci]
```

## Steps

### 1. Verify branch and working tree

- Ensure we are on a feature branch (not `main`)
- Run `git status` — if there are uncommitted changes:
  - If CI will run (no `--skip-ci` and changes touch application code): stop and ask the user to run `/complete-feature` first
  - If CI will be skipped: stage and commit the remaining changes directly

### 2. Review all changes

- `git log main..HEAD --oneline` to see every commit on this branch
- `git diff main...HEAD` to see the full diff against main

### 3. Skip CI

Amend the last commit to add `[skip ci]` to the commit message if:
- The `--skip-ci` flag was passed, OR
- The changes do not touch application code (e.g., only `.ai/`, docs, config, CI files)

### 4. Push and create PR

- Push with `-u` if the branch is not yet on the remote
- Use `gh pr create` with:
  - Short title (< 70 characters)
  - Description covering **all commits** on the branch, not just the latest:
    - Summary (what was done)
    - Test plan (how to verify)
