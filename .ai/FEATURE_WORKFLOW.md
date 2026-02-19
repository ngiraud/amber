# Feature Workflow

## Overview

Features are built in 5 phases, each with its own skill:

| Phase        | Skill                    | What it does                                    |
|--------------|--------------------------|-------------------------------------------------|
| 1. Plan      | `/create-feature <name>` | Branch + plan + save to `.ai/plans/`            |
| 2. Implement | `/implement-feature`     | Backend, frontend, tests from plan              |
| 3. Validate  | `/validate-feature`      | Run tests + Rector + Pint + PHPStan, fix errors |
| 4. Commit    | `/complete-feature`      | Update plans, stage files, commit               |
| 5. PR        | `/create-pr`             | Push branch, create pull request                |

## Model switching

- **Opus** for planning (`/create-feature`)
- **Sonnet** for everything else (`/implement-feature` and subsequent skills)

The `/create-feature` skill reminds you when to switch.

## Typical session

```
You: /create-feature billing-portal
Claude: [creates branch, enters plan mode, proposes approach]
Claude: "Plan saved to .ai/plans/billing-portal.md. Switch to Sonnet and run /implement-feature."

You: /implement-feature
Claude: [backend implementation]
Claude: "Backend done. Here's what was created. OK to continue?"

You: ok
Claude: [frontend implementation]
Claude: "Frontend ready. Run composer dev to test."

You: looks good, continue
Claude: [writes tests, runs them]
Claude: "Tests passing. Run /validate-feature when ready."

You: /validate-feature
Claude: [runs full suite, fixes issues]

You: /complete-feature
Claude: [updates plan, commits]

You: /create-pr
Claude: [pushes, creates PR, returns URL]
```

## Tips

- Use `/clear` between sessions to keep context clean — the plan file preserves the context
- Plans in `.ai/plans/` can be created in one session and implemented in another
- Be specific in requests ("add validation for email field") over vague ("make it better")
- Fix issues early rather than waiting until the end
