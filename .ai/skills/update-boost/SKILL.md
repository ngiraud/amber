---
name: update-boost
description: Update Laravel Boost configuration after modifying .ai/ files.
---

# Skill: update-boost

Update Laravel Boost configuration after modifying `.ai/` files.

## Usage

```
/update-boost
```

## When to use

After modifying or adding files in:

- `.ai/guidelines/` (conventions)
- `.ai/skills/` (skills)
- `.ai/PLAN.md`, `.ai/WORKFLOW.md`, etc.

## Action

Run the command:

```bash
php artisan boost:update
```

## What it does

- Syncs conventions from `.ai/guidelines/` to `CLAUDE.md`
- Registers new skills from `.ai/skills/`
- Updates MCP configuration if needed

## Reminder

Always run `/update-boost` after modifying project configuration so changes are picked up by all tools using Laravel Boost.
