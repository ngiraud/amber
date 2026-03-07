# Feature: Refactor Session Sources

## Context

The app currently has 3 session sources: Manual, Auto, and Reconstructed. In practice, the "Auto" source (sessions started automatically from activity detection via `StartSession`) adds friction (forgotten starts/stops, phantom sessions) and is redundant with "Reconstructed" sessions which are built reliably from activity events via gap analysis.

This refactor simplifies the model:
- **Reconstructed becomes Auto** — passive, zero-friction sessions built from activity events are the primary mode
- **Manual keeps a timer option** — for work that doesn't generate ActivityEvents (meetings, reviews, calls). User can either create a session with fixed times (past) or start a live timer and stop it later
- **"Still alive?" notification** — periodic reminder for active manual timer sessions, configurable interval

---

## Step 1: Rename `Reconstructed` to `Auto`, remove old `Auto`

### 1a. Migration: rename enum values in `sessions.source`

```
php artisan make:migration rename_session_source_values --no-interaction
```

Update existing rows:
- `source = 20` (old Auto) → `source = 10` (Manual) — these were auto-started sessions triggered by activity detection, they behave like manual timer sessions
- `source = 30` (old Reconstructed) → `source = 20` (new Auto)

### 1b. Update `SessionSource` enum

File: `app/Enums/SessionSource.php`

```php
enum SessionSource: int
{
    case Manual = 10;
    case Auto = 20;
}
```

Remove `Reconstructed = 30`. Only two sources remain.

### 1c. Update `CreateSession`

File: `app/Actions/Session/CreateSession.php`

- Remove `reconstructed()` method
- Remove `auto()` method
- Default source becomes `Manual`
- Add `auto()` method that sets source to `Auto` (used by `ReconstructDailySessions`)
- The `manual()` method stays as-is

### 1d. Update `ReconstructDailySessions`

File: `app/Actions/Session/ReconstructDailySessions.php`

- Change `$this->createSession->reconstructed()` to `$this->createSession->auto()`

### 1e. Update `SessionFactory`

File: `database/factories/SessionFactory.php`

- Add `auto()` state that sets `source => SessionSource::Auto`
- Default stays `Manual`

### 1f. Update TypeScript types

File: `resources/js/types/resources.ts`

- No structural change needed (SessionSource is `{value, label}`)

### 1g. Update frontend labels

The `SessionSource` enum `label()` method already returns `$this->name`, so "Auto" will display correctly.

---

## Step 2: Simplify session start/stop — timer only for Manual

### 2a. Rework `StartSession`

File: `app/Actions/Session/StartSession.php`

This action now exclusively starts a **manual timer session**. It:
- Creates a `Manual` session with `started_at = now()`, no `ended_at`
- Keeps the `SessionAlreadyActiveException` check (only one active timer at a time)
- Dispatches `SessionStarted`
- Uses `$this->createSession->manual()` (explicit)

### 2b. `StopSession` — unchanged

File: `app/Actions/Session/StopSession.php`

Stays as-is. Only manual timer sessions can be "active" (no `ended_at`), so this only applies to them.

### 2c. `SwitchSessionProject` — unchanged

File: `app/Actions/Session/SwitchSessionProject.php`

Stays as-is. Stops current manual timer, starts new one on different project.

### 2d. Remove auto-start from activity detection

File: `app/Listeners/HandleStartSessionFromNotification.php`

Currently starts a session when user clicks the "untracked activity" notification. **Keep this** — it's user-initiated (clicking a notification = manual intent to start a timer). This is the "Start tracking" action from the notification.

File: `app/Listeners/HandleStartSessionFromMenu.php`

**Keep this** — user explicitly starts a timer from the menu bar.

### 2e. `CheckIdleStatus` — scope to manual sessions only

File: `app/Actions/Activity/CheckIdleStatus.php`

Add a check: only auto-stop sessions with `source = Manual`. Auto sessions are always complete (have `ended_at`), so `findActive()` will naturally only find manual timer sessions. **No change needed** — `findActive()` already filters on `whereNull('ended_at')`, and Auto sessions always have `ended_at` set.

### 2f. `CheckUntrackedActivity` — unchanged

File: `app/Actions/Activity/CheckUntrackedActivity.php`

Stays as-is. It checks for activity events without a session — still relevant since Auto sessions are created periodically (not in real-time). The notification prompts the user to start a manual timer if they want real-time tracking.

### 2g. Menu bar — unchanged behavior

File: `app/Services/MenuBarService.php`

The menu bar shows Start/Stop/Switch for manual timer sessions. `findActive()` only returns manual timer sessions (Auto sessions are always complete). No changes needed.

### 2h. `HandleInertiaRequests` — unchanged

File: `app/Http/Middleware/HandleInertiaRequests.php`

`activeSession` shared prop still works — only manual timer sessions are "active".

---

## Step 3: "Still alive?" notification for manual timer sessions

### 3a. Add setting `manual_session_reminder_minutes`

File: `app/Settings/ActivitySettings.php`

Add property:
```php
public int $manual_session_reminder_minutes;
```

Default value: `60` (remind every hour).

### 3b. Settings migration

```
php artisan make:migration add_manual_session_reminder_minutes_setting --no-interaction
```

Add `manual_session_reminder_minutes => 60` to the activity settings group.

### 3c. Create `CheckManualSessionReminder` action

File: `app/Actions/Session/CheckManualSessionReminder.php`

```php
class CheckManualSessionReminder extends Action
{
    public function __construct(private readonly ActivitySettings $settings) {}

    public function handle(): void
    {
        $session = Session::findActive();

        if ($session === null || $session->source !== SessionSource::Manual) {
            return;
        }

        $reminderMinutes = $this->settings->manual_session_reminder_minutes;

        if ($reminderMinutes <= 0) {
            return; // disabled
        }

        $elapsedMinutes = (int) $session->started_at->diffInMinutes(CarbonImmutable::now());

        // Only remind at intervals (e.g. at 60min, 120min, 180min...)
        // Use cache to avoid duplicate notifications within the same interval
        $currentInterval = intdiv($elapsedMinutes, $reminderMinutes);

        if ($currentInterval < 1) {
            return;
        }

        $cacheKey = "session_reminder:{$session->id}:{$currentInterval}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes($reminderMinutes));

        ManualSessionReminderReached::dispatch($session);
    }
}
```

### 3d. Create `ManualSessionReminderReached` event

File: `app/Events/ManualSessionReminderReached.php`

```php
class ManualSessionReminderReached
{
    use Dispatchable;

    public function __construct(public readonly Session $session) {}
}
```

### 3e. Create `SendManualSessionReminder` listener

File: `app/Listeners/SendManualSessionReminder.php`

Sends a NativePHP notification:
- Title: "Session still running"
- Message: "You've been tracking {project_name} for {HH:MM}. Still working?"
- Action button: "Stop Session" (reference = session ID, handled by a new listener or existing stop mechanism)

### 3f. Register listener

File: `app/Providers/EventServiceProvider.php` (or wherever events are registered)

```php
ManualSessionReminderReached::class => [SendManualSessionReminder::class],
```

### 3g. Schedule the check

File: `routes/console.php`

Add:
```php
Schedule::call(fn () => app(CheckManualSessionReminder::class)->handle())->everyMinute();
```

### 3h. Update frontend settings form

File: `resources/js/pages/settings/Edit.vue` (or equivalent)

Add field for `manual_session_reminder_minutes` in the Activity Settings section:
- Label: "Manual session reminder (minutes)"
- Hint: "Get a notification reminder when a manual timer session is running. Set to 0 to disable."
- Type: number, min: 0

### 3i. Update TypeScript `ActivitySettings` type

File: `resources/js/types/resources.ts`

Add `manual_session_reminder_minutes: number` to the `ActivitySettings` type.

---

## Step 4: Update `SessionController.store` — manual session with optional timer

### 4a. Update `StoreSessionRequest`

File: `app/Http/Requests/Session/StoreSessionRequest.php`

The existing rules already support both modes:
- With `started_at` + `ended_at`: creates a completed manual session (past event)
- With only `project_id` (no times): the `SessionTimerController.start` handles the timer

No changes needed — the two controllers (`SessionController.store` for completed sessions, `SessionTimerController.start` for timer) already handle both cases.

### 4b. Update `SessionController.store` — ensure Manual source

File: `app/Http/Controllers/SessionController.php`

The `store` method uses `CreateSession` which defaults to `Manual`. Ensure `manual()` is called explicitly:

```php
$action->manual()->handle($project, SessionData::fromArray($request->validated()));
```

---

## Step 5: Tests

### 5a. Update existing tests

- `tests/Feature/Session/StartSessionTest.php` — verify sessions are `Manual` source
- `tests/Feature/Session/StopSessionTest.php` — unchanged
- `tests/Feature/Session/ReconstructDaySessionsTest.php` — verify sessions are `Auto` source (was `Reconstructed`)
- `tests/Feature/Session/ManageSessionTest.php` — update any `SessionSource::Reconstructed` references

### 5b. New tests

- `tests/Feature/Session/CheckManualSessionReminderTest.php`
  - Dispatches event after reminder interval elapsed
  - Does not dispatch before interval
  - Does not dispatch for Auto sessions
  - Does not dispatch when no active session
  - Does not dispatch duplicate within same interval
  - Does not dispatch when setting is 0 (disabled)

### 5c. Update factory states if used in tests

Ensure tests using `SessionSource::Reconstructed` are updated to `SessionSource::Auto`.

---

## Files Summary

### Modified
- `app/Enums/SessionSource.php` — remove Reconstructed, keep Manual + Auto
- `app/Actions/Session/CreateSession.php` — remove `reconstructed()`, update `auto()`
- `app/Actions/Session/ReconstructDailySessions.php` — `reconstructed()` → `auto()`
- `app/Actions/Session/StartSession.php` — use `manual()` explicitly
- `app/Http/Controllers/SessionController.php` — use `manual()` explicitly
- `app/Settings/ActivitySettings.php` — add `manual_session_reminder_minutes`
- `database/factories/SessionFactory.php` — add `auto()` state
- `resources/js/types/resources.ts` — add `manual_session_reminder_minutes`
- `routes/console.php` — schedule `CheckManualSessionReminder`
- Settings frontend (edit page) — add reminder field
- Existing tests — update `Reconstructed` → `Auto` references

### Created
- Migration: rename session source values
- Migration: add `manual_session_reminder_minutes` setting
- `app/Actions/Session/CheckManualSessionReminder.php`
- `app/Events/ManualSessionReminderReached.php`
- `app/Listeners/SendManualSessionReminder.php`
- `tests/Feature/Session/CheckManualSessionReminderTest.php`

### Unchanged (verified no changes needed)
- `app/Actions/Session/StopSession.php`
- `app/Actions/Session/SwitchSessionProject.php`
- `app/Actions/Activity/CheckIdleStatus.php`
- `app/Actions/Activity/CheckUntrackedActivity.php`
- `app/Services/MenuBarService.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `app/Listeners/HandleStartSessionFromMenu.php`
- `app/Listeners/HandleStopSessionFromMenu.php`
- `app/Listeners/HandleStartSessionFromNotification.php`

---

## Verification

1. `php artisan migrate` — migration runs cleanly
2. `php artisan test --compact --filter=Session` — all session tests pass
3. `php artisan test --compact --filter=CheckManualSessionReminder` — reminder tests pass
4. `composer test:all` — Pint, Rector, PHPStan, Pest all green
5. Manual test: create a manual session with timer, verify "Still alive?" notification after configured interval
6. Manual test: verify reconstructed sessions appear as "Auto" source
7. `database-schema` MCP tool to verify migration applied correctly
