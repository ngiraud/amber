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

### 1. Check current branch

Run `git branch --show-current` to determine the workflow:

- **On a feature branch**: full flow — update plans, simplify, commit, then suggest running `/create-pr`
- **On `main`**: skip plan updates and PR suggestion — just simplify and commit directly

### 2. Update plans

- **Always**: if `.ai/PLAN.md` contains the feature, mark it as done
- **Always**: delete the temporary plan file `.ai/plans/<feature>.md` if it exists

### 3. Simplify

Review uncommitted files for code quality issues before committing: duplication, missed reuse of existing utilities, redundant logic, efficiency problems (N+1, O(n×m) loops, etc.).
Fix any real issues found.

### 4. Update README.md

Read `README.md` and update it if the feature adds user-visible functionality worth documenting (new capability, keyboard shortcut, integration, etc.). Skip for internal refactors, bug fixes, or developer-only changes.

If updating, add the feature under the most relevant existing section (e.g. **Key Features**, **Deep Linking**). Keep the same tone and style as the existing entries — concise, emoji-prefixed bullet points for features.

### 5. Update CHANGELOG.md

Read `CHANGELOG.md` and add an entry under `## [Unreleased]` for the completed feature.

- Determine the correct category: **Added** (new feature), **Changed** (behavior change or refactor), **Fixed** (bug fix), **Removed** (deletion of functionality)
- Write a concise, user-facing bullet point — same tone and style as existing entries
- Skip for internal-only changes (developer tooling, test updates, CI, code style) that have no user-visible impact
- Do **not** create a new version section — only append to `[Unreleased]`

### 6. Commit

1. `git status` to review all changes
2. `git diff` to review staged and unstaged changes
3. Stage only the files directly related to the feature — never `.env`, credentials, or unrelated changes. If unsure about a file, ask the user.
4. Create commit with conventional message:
    - `feat: <description>` for a new feature
    - `fix: <description>` for a bugfix
    - `refactor: <description>` for refactoring

### 7. Next step *(feature branch only)*

Remind the user to run `/create-pr` when ready to open a pull request.
