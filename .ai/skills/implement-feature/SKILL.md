---
name: implement-feature
description: Implement a feature from an existing plan in .ai/plans/.
---

# Skill: implement-feature

Implement a feature from an existing plan created by `/create-feature`. Ensure you are on the **Sonnet** model before starting.

## Usage

```
/implement-feature
```

## Steps

### 1. Load the plan

- Run `git branch --show-current` to check the current branch
- **On a feature branch**: read the matching plan from `.ai/plans/<feature-name>.md`
- **On `main`**: if no plan file is referenced, proceed without one — implement based on the user's instructions directly
- If on a feature branch but no plan exists, stop and ask the user to run `/create-feature <name>` first

### 2. Backend (in this order)

1. **Migration**: `php artisan make:migration`
2. **Model**: `php artisan make:model` with factory
3. **Enum** (if needed): `php artisan make:enum`
4. **Action(s)**: Create in `app/Actions/{Domain}/`
5. **Form Request**: `php artisan make:request`
6. **Controller**: `php artisan make:controller`
7. **Policy** (if needed): `php artisan make:policy`
8. **Routes**: Add in `routes/web.php`

> **PAUSE**: Summarize what was created/modified. Wait for OK.

### 3. Frontend

1. **Vue pages** in `resources/js/Pages/`
2. **Reusable components** in `resources/js/Components/`
3. Regenerate Wayfinder: `php artisan wayfinder:generate`

> **PAUSE**: "Frontend ready. Run `php composer dev` to test manually, then tell me to continue."

### 4. Tests

1. **Unit tests** for Actions in `tests/Unit/Actions/`
2. **Feature tests** for Controller delegation in `tests/Feature/{Domain}/`
3. Run `php artisan test --compact --filter=FeatureName`

> **PAUSE**: "Tests written and passing. Run `/validate-feature` when ready."
