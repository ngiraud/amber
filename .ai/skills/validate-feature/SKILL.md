---
name: validate-feature
description: Run full validation suite (tests, Rector, Pint, PHPStan) and fix errors.
---

# Skill: validate-feature

Run the full validation suite and fix any issues until everything passes.

## Usage

```
/validate-feature
```

## Steps

### 1. Run validation

```bash
composer test:all
```

### 2. Fix errors (if any)

- **Failed tests**: Fix tests or code
- **Rector**: Apply suggested refactors
- **Pint**: Already auto-fixed, re-run if needed
- **PHPStan**: Fix type errors

### 3. Re-run until clean

Re-run `composer test:all` until everything passes with zero errors.
