# Feature: Self-Describing Activity Sources

## Context

Adding a new activity source currently requires touching ~12 files with lots of copy-paste wiring. The scan/service layer is clean, but the settings/config/UI layer is hard-coded per source. This refactoring makes each source self-contained and adds an artisan scaffolding command.

**After refactoring, adding a new source = run `php artisan make:activity-source {Name}` + fill in the scan logic and field definitions.**

---

## Phase 1: Make SourceConfig Self-Describing

### 1.1 Create `FieldDefinition` value object

**New file:** `app/Data/ActivitySourceConfigs/FieldDefinition.php`

Readonly class describing a form field for the frontend:
- `name`, `type` (text|number|textarea|email-list|string-list), `label`, `hint`
- Optional: `placeholder`, `min`, `max`, `rows`, `separator` (`,` or `\n`)
- `toArray(): array` for serialization

### 1.2 Extend `SourceConfig` interface

**Modify:** `app/Data/ActivitySourceConfigs/Contracts/SourceConfig.php`

Add 3 static methods:
- `validationRules(): array` — Laravel rules without source prefix (e.g. `['author_emails' => ['array']]`)
- `defaultData(): array` — defaults for the settings migration
- `fieldDefinitions(): array` — returns `FieldDefinition[]` for the frontend form

### 1.3 Implement on each existing SourceConfig

**Modify:**
- `app/Data/ActivitySourceConfigs/GitSourceConfig.php`
- `app/Data/ActivitySourceConfigs/GitHubSourceConfig.php`
- `app/Data/ActivitySourceConfigs/ClaudeCodeSourceConfig.php`
- `app/Data/ActivitySourceConfigs/FswatchSourceConfig.php`

Move validation rules from `UpdateActivitySourceSettingsRequest` into each config.
Move defaults from the settings migration into `defaultData()`.
Add `fieldDefinitions()` with the label/hint/placeholder currently hard-coded in `Sources.vue`.

---

## Phase 2: Add Metadata to the Enum

**Modify:** `app/Enums/ActivityEventSourceType.php`

### 2.1 Add `configClass(): string` method
Convention-based: `App\Data\ActivitySourceConfigs\{Name}SourceConfig`. Same pattern as `guessActivitySource()`.

### 2.2 Add `description(): string` method
Match statement returning the description currently hard-coded in `Sources.vue` template (e.g. "Detect commits and branch activity from local repositories").

### 2.3 Update `toArray()`
Include `key` (settingsKey), `description`, and `fields` (from `configClass()::fieldDefinitions()`).

---

## Phase 3: Make Backend Wiring Dynamic

### 3.1 `ActivitySourceSettings` — dynamic casts and configFor

**Modify:** `app/Settings/ActivitySourceSettings.php`

- `casts()`: iterate `ActivityEventSourceType::cases()` instead of hard-coded array
- `configFor()`: use `$this->{$type->settingsKey()}` instead of match statement

> Note: The typed properties (`public GitSourceConfig $git`, etc.) MUST remain — Spatie Settings requires them. The artisan command will inject these automatically.

### 3.2 `UpdateActivitySourceSettingsRequest` — dynamic rules

**Modify:** `app/Http/Requests/Settings/UpdateActivitySourceSettingsRequest.php`

Build rules by iterating enum cases and calling `$type->configClass()::validationRules()`, prefixing each rule key with `{settingsKey}.`.

### 3.3 `UpdateActivitySourceSettings` — generic update loop

**Modify:** `app/Actions/Settings/UpdateActivitySourceSettings.php`

Replace the 4 `if (isset($data[...]))` blocks with a single loop over enum cases. Keep `handleFswatchLifecycle()` as-is (source-specific, not worth abstracting for one case).

### 3.4 `ActivitySourceSettingsController::edit()` — dynamic props

**Modify:** `app/Http/Controllers/Settings/ActivitySourceSettingsController.php`

Replace `activitySourceSettings` + `sourceInfo` props with a single `sources` array built by iterating enum cases, merging `$type->toArray()` with `config => $settings->configFor($type)->toArray()`.

> **Important:** This step must happen atomically with Phase 4 (frontend) since the Inertia prop shape changes.

---

## Phase 4: Frontend Refactoring

### 4.1 Update TypeScript types

**Modify:** `resources/js/types/resources.ts`

Remove: `GitSourceConfig`, `GitHubSourceConfig`, `ClaudeCodeSourceConfig`, `FswatchSourceConfig`, `ActivitySourceSettings`

Add:
```typescript
type SourceFieldDefinition = {
    name: string;
    type: 'text' | 'number' | 'textarea' | 'email-list' | 'string-list';
    label: string;
    hint: string;
    placeholder?: string;
    min?: number;
    max?: number;
    rows?: number;
    separator?: string;
};

type SourceDefinition = {
    value: string;
    key: string;
    label: string;
    color: string;
    description: string;
    requirements: string;
    fields: SourceFieldDefinition[];
    config: Record<string, unknown>;
};
```

### 4.2 Rewrite `Sources.vue` — dynamic rendering

**Modify:** `resources/js/pages/settings/Sources.vue`

- Props: `sources: SourceDefinition[]`
- Create all forms in `<script setup>` via `Object.fromEntries(props.sources.map(...))`
- Helper `initFormData(source)`: joins arrays to strings for list-type fields
- Helper `transformFormData(source, data)`: splits strings back to arrays
- Generic `save(source, visitOptions)` function
- Template: `v-for="source in sources"` rendering `SourceCard` with dynamic fields inside
- Dynamic field rendering: `v-if` on `field.type` for Input/Textarea/number

### 4.3 Update `SourceCard.vue` if needed

Verify it doesn't need changes — it already accepts `title`, `description`, `requirements`, `source-value` as props. Should work as-is.

---

## Phase 5: Artisan Scaffolding Command

### 5.1 Create stubs

**New files:**
- `stubs/activity-source-config.stub` — SourceConfig with `validationRules()`, `defaultData()`, `fieldDefinitions()`, `fromArray()`, `toArray()`
- `stubs/activity-source.stub` — ActivitySource with `identifier()`, `scan()`, `isAvailable()`
- `stubs/activity-source-settings-migration.stub` — Spatie Settings migration adding the new property

### 5.2 Create command

**New file:** `app/Console/Commands/MakeActivitySourceCommand.php`

`php artisan make:activity-source {Name}` does:

1. Generate `app/Data/ActivitySourceConfigs/{Name}SourceConfig.php` from stub
2. Generate `app/Services/ActivitySources/{Name}ActivitySource.php` from stub
3. Generate `database/settings/{timestamp}_add_{snake}_source_setting.php` from stub
4. **Inject enum case** into `ActivityEventSourceType.php` — regex to find last case and insert after it, plus add match arms for `color()`, `requirements()`, `description()`
5. **Inject property + import** into `ActivitySourceSettings.php` — regex to add `public {Name}SourceConfig ${snake};` and the `use` import
6. Print summary of generated/modified files

File manipulation strategy: read file content, use regex patterns anchored to known structures (last enum `case`, last `public` property in Settings, last match arm in each method). Fail gracefully with a clear message if the pattern doesn't match.

---

## Phase 6: Tests

### 6.1 Update existing settings tests
- Update assertions that reference the old prop shape (`activitySourceSettings` -> `sources`)
- All existing behavior must continue passing

### 6.2 Add new tests
- Test `SourceConfig::validationRules()` produces valid rules for each config
- Test `SourceConfig::defaultData()` round-trips through `fromArray()`
- Test the dynamic FormRequest builds expected rules
- Test the artisan command generates files and modifies enum/settings correctly
- Cleanup generated files in test teardown

---

## Execution Order

1. Phase 1 (SourceConfig interface + FieldDefinition) — additive, no breaking changes
2. Phase 2 (enum metadata) — additive
3. Phase 3.1-3.3 (backend dynamic wiring except controller) — behavior-preserving refactor
4. Phase 3.4 + Phase 4 (controller + frontend) — **atomic**, prop shape changes
5. Phase 5 (artisan command + stubs) — independent
6. Phase 6 (tests) — incremental alongside each phase

---

## Files Summary

| Action | File |
|--------|------|
| Create | `app/Data/ActivitySourceConfigs/FieldDefinition.php` |
| Modify | `app/Data/ActivitySourceConfigs/Contracts/SourceConfig.php` |
| Modify | `app/Data/ActivitySourceConfigs/GitSourceConfig.php` |
| Modify | `app/Data/ActivitySourceConfigs/GitHubSourceConfig.php` |
| Modify | `app/Data/ActivitySourceConfigs/ClaudeCodeSourceConfig.php` |
| Modify | `app/Data/ActivitySourceConfigs/FswatchSourceConfig.php` |
| Modify | `app/Enums/ActivityEventSourceType.php` |
| Modify | `app/Settings/ActivitySourceSettings.php` |
| Modify | `app/Http/Requests/Settings/UpdateActivitySourceSettingsRequest.php` |
| Modify | `app/Actions/Settings/UpdateActivitySourceSettings.php` |
| Modify | `app/Http/Controllers/Settings/ActivitySourceSettingsController.php` |
| Modify | `resources/js/pages/settings/Sources.vue` |
| Modify | `resources/js/types/resources.ts` |
| Create | `app/Console/Commands/MakeActivitySourceCommand.php` |
| Create | `stubs/activity-source-config.stub` |
| Create | `stubs/activity-source.stub` |
| Create | `stubs/activity-source-settings-migration.stub` |

---

## Verification

1. `php artisan test --compact` — all existing tests pass after each phase
2. Manual browser test — Sources settings page renders all 4 cards, toggle/save/verify work
3. `php artisan make:activity-source TestDummy` — verify files generated + enum/settings modified, then revert
4. `composer test:all` (Pint + Rector + PHPStan + Pest) — green
