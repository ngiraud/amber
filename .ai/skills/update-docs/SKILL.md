---
name: update-docs
description: Analyze README.md and CHANGELOG.md and update them to reflect the current state of the project.
---

# Skill: update-docs

Analyze `README.md` and `CHANGELOG.md` and update them if needed to accurately reflect the current state of the project.

## Usage

```
/update-docs
```

## Steps

### 1. Understand the current state

- Run `git log --oneline -20` to review recent commits
- Read `README.md` and `CHANGELOG.md` in full
- Read `.ai/plans/` if any active plans exist to understand in-progress or recently completed features

### 2. Analyze what needs updating

Compare the recent commits and codebase state against the existing documentation to identify:

- New features, capabilities, or integrations not yet documented in `README.md`
- Bug fixes, changes, or removals not yet reflected in `CHANGELOG.md`
- Outdated information (removed features, changed shortcuts, renamed concepts, etc.)

### 3. Update README.md

Update `README.md` if any user-visible functionality is missing or outdated:

- Add new features under the most relevant section (e.g. **Key Features**, **Deep Linking**)
- Update or remove entries that are no longer accurate
- Keep the same tone and style — concise, emoji-prefixed bullet points for features
- Do **not** restructure the document — only add, update, or remove specific entries

### 4. Update CHANGELOG.md

The CHANGELOG targets **all audiences** — end users AND developers/contributors. Document anything meaningful: new features, behavior changes, bug fixes, developer tooling, CI improvements, test infrastructure, etc. Only skip truly trivial commits (lint fixes, typos, dependency bumps with no behavior change).

Update `CHANGELOG.md` to reflect any undocumented changes:

- Add missing entries under `## [Unreleased]`, creating the section if absent
- Use the correct category:
  - **Added** — new features or tooling
  - **Changed** — behavior changes or refactors
  - **Fixed** — bug fixes
  - **Removed** — deleted functionality
- Write concise bullet points — user-facing changes in plain language, developer changes in technical terms
- Do **not** create a new version section — only append to `[Unreleased]`

### 5. Report changes

Summarize what was updated (or explain why no changes were needed).
