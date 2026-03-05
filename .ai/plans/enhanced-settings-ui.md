# Feature: Enhanced Settings UI

## Context

Activity configuration (source toggles, timeouts, fswatch patterns) lives only in `config/activity.php` via env vars — not editable from the UI. The `github_username` setting exists in DB but is missing from the frontend. This feature makes all activity config values editable from a well-organized tabbed Settings page, and adds user preferences (timezone, locale).

## Architecture: Boot-time Config Override

`AppServiceProvider::configureActivitySettings()` reads activity-prefixed `AppSetting` rows and calls `config()->set()` to override values. All existing consumers (`ActivityEventSourceType::isEnabled()`, `FileWatcherService`, `ScanAllSources`, etc.) continue reading from `config()` unchanged.

Key mapping: AppSetting keys use dots (e.g. `activity.sources.git.enabled`) and map directly to config paths. Exception: `activity.sources.claude_code.enabled` → `activity.sources.claude-code.enabled`.

## Steps

### 1. Install shadcn-vue components
```bash
npx shadcn-vue@latest add tabs switch
```

### 2. New migration: seed activity settings defaults
- File: `database/migrations/xxxx_add_activity_settings_defaults.php`
- Insert default rows for all new keys so they appear in `AppSetting::pluck('value', 'key')`
- Keys to insert:
  - `activity.idle_timeout_minutes` → 30
  - `activity.untracked_threshold_minutes` → 15
  - `activity.scan_interval_minutes` → 2
  - `activity.block_end_padding_minutes` → 15
  - `activity.sources.git.enabled` → true
  - `activity.sources.github.enabled` → true
  - `activity.sources.claude_code.enabled` → true
  - `activity.sources.fswatch.enabled` → true
  - `activity.sources.fswatch.debounce_seconds` → 3
  - `activity.sources.fswatch.excluded_patterns` → (from config default array)
  - `activity.sources.fswatch.allowed_extensions` → (from config default array)
  - `timezone` → null
  - `locale` → null

### 3. AppServiceProvider: config overlay
- File: `app/Providers/AppServiceProvider.php`
- Add `configureActivitySettings()` called from `boot()` (first call)
- Loads all `activity.*` AppSettings, maps keys, calls `config()->set()`
- `activity.sources.claude_code.enabled` → maps to `activity.sources.claude-code.enabled`
- Also handles `timezone` → `config('app.timezone')` + `date_default_timezone_set()`
- Also handles `locale` → `config('app.locale')` + `app()->setLocale()`
- Extract the overlay logic into a static helper callable from both boot() and UpdateSettings action

### 4. UpdateSettingsRequest: new validation rules
- File: `app/Http/Requests/Settings/UpdateSettingsRequest.php`
- Add:
  - `github_username`: nullable, string, max:255
  - `timezone`: nullable, string, Rule::in(timezone_identifiers_list())
  - `locale`: nullable, string, Rule::in(['en', 'fr'])
  - `activity.sources.git.enabled`: nullable, boolean
  - `activity.sources.github.enabled`: nullable, boolean
  - `activity.sources.claude_code.enabled`: nullable, boolean
  - `activity.sources.fswatch.enabled`: nullable, boolean
  - `activity.idle_timeout_minutes`: nullable, integer, min:1, max:120
  - `activity.untracked_threshold_minutes`: nullable, integer, min:1, max:120
  - `activity.scan_interval_minutes`: nullable, integer, min:1, max:30
  - `activity.block_end_padding_minutes`: nullable, integer, min:0, max:60
  - `activity.sources.fswatch.debounce_seconds`: nullable, integer, min:1, max:30
  - `activity.sources.fswatch.excluded_patterns`: nullable, array
  - `activity.sources.fswatch.excluded_patterns.*`: string
  - `activity.sources.fswatch.allowed_extensions`: nullable, array
  - `activity.sources.fswatch.allowed_extensions.*`: string

### 5. UpdateSettings action: fswatch lifecycle
- File: `app/Actions/Settings/UpdateSettings.php`
- Capture `previousFswatchEnabled` from `config()` before saving
- After upsert transaction: re-apply config overlay (call configureActivitySettings)
- Then handle fswatch:
  - enabled → disabled: `FileWatcherService::make()->stop()`
  - disabled → enabled: `FileWatcherService::make()->start()`
  - stayed enabled + (debounce or patterns changed): `FileWatcherService::make()->restart()`

### 6. SettingsController: pass extra props
- File: `app/Http/Controllers/SettingsController.php`
- `edit()` now passes:
  - `timezones`: `timezone_identifiers_list()`
  - `locales`: `['en' => 'English', 'fr' => 'Français']`
- Settings already come from `AppSetting::pluck('value', 'key')` which includes new keys after migration

### 7. TypeScript types
- File: `resources/js/types/resources.ts`
- Expand `AppSettings` type:
  ```ts
  github_username?: string | null;
  timezone?: string | null;
  locale?: string | null;
  'activity.sources.git.enabled'?: boolean;
  'activity.sources.github.enabled'?: boolean;
  'activity.sources.claude_code.enabled'?: boolean;
  'activity.sources.fswatch.enabled'?: boolean;
  'activity.idle_timeout_minutes'?: number;
  'activity.untracked_threshold_minutes'?: number;
  'activity.scan_interval_minutes'?: number;
  'activity.block_end_padding_minutes'?: number;
  'activity.sources.fswatch.debounce_seconds'?: number;
  'activity.sources.fswatch.excluded_patterns'?: string[];
  'activity.sources.fswatch.allowed_extensions'?: string[];
  ```

### 8. Settings page: tabbed UI
- File: `resources/js/pages/settings/Edit.vue`
- Use `useForm()` from `@inertiajs/vue3` (instead of native `<Form>`) for Switch component compatibility
- Wrap everything in a single form, one Save button at the bottom
- Import: Tabs, TabsList, TabsTrigger, TabsContent, Switch from installed shadcn components

**Tab: General**
- Git author emails (existing, comma-separated input)
- GitHub username (new, text input)
- Timezone (new, searchable select from `timezones` prop)
- Locale (new, select: English / Français)
- Separator
- Company name, Company address (existing)
- Separator
- Billing defaults: hourly rate, daily rate, daily reference hours, rounding (existing)

**Tab: Activity**
- Section "Sources" — 4 rows, each with Switch + label + description:
  - Git (detect git commits)
  - GitHub (detect PRs and reviews)
  - Claude Code (detect Claude Code sessions)
  - Fswatch (detect file changes in real-time) — note: restart effect
- Section "Timing" — 4 number inputs with descriptions:
  - Idle timeout (minutes before session considered idle)
  - Untracked threshold (minimum minutes to prompt tracking)
  - Scan interval (how often sources are scanned, minutes)
  - Block end padding (minutes added after last event)

**Tab: Advanced**
- Fswatch section:
  - Debounce (seconds, number input)
  - Excluded patterns (textarea, one pattern per line → transforms to/from array)
  - Allowed extensions (textarea, comma-separated → transforms to/from array)

**Transform function** (from useForm data → submitted payload):
- `git_author_emails`: string → array (split by comma)
- `activity.sources.fswatch.excluded_patterns`: textarea string → array (split by newline)
- `activity.sources.fswatch.allowed_extensions`: textarea string → array (split by comma)

### 9. Tests
- File: `tests/Feature/Settings/UpdateSettingsTest.php` (extend existing)
  - Validate timezone must be in timezone_identifiers_list()
  - Validate locale must be in allowed list
  - Validate source toggles are boolean
  - Validate timing bounds (e.g. idle_timeout_minutes min:1)
  - Action: persists activity settings in DB
  - Action: calls FileWatcherService::stop() when fswatch disabled
  - Action: calls FileWatcherService::start() when fswatch enabled
  - Action: calls FileWatcherService::restart() when debounce changed

## Files Modified

| File | Type |
|------|------|
| `app/Providers/AppServiceProvider.php` | Modified |
| `app/Http/Requests/Settings/UpdateSettingsRequest.php` | Modified |
| `app/Actions/Settings/UpdateSettings.php` | Modified |
| `app/Http/Controllers/SettingsController.php` | Modified |
| `resources/js/pages/settings/Edit.vue` | Modified |
| `resources/js/types/resources.ts` | Modified |
| `database/migrations/xxxx_add_activity_settings_defaults.php` | New |
| `tests/Feature/Settings/UpdateSettingsTest.php` | Modified |

## Verification

1. `php artisan migrate` — new settings rows inserted
2. Open Settings → 3 tabs visible, fields populated with defaults
3. Toggle fswatch off → Save → watcher stops
4. Toggle fswatch on → Save → watcher restarts
5. Change idle timeout → Save → `config('activity.idle_timeout_minutes')` returns new value
6. Set timezone → Save → app uses it for date display
7. `php artisan test --compact --filter=Settings` — all tests pass
8. `composer test:all` — full suite passes
