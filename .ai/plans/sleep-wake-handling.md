# Sleep/Wake Handling

## Overview

When the user's screen locks (before sleep or manual lock) and later unlocks (after wake or unlock), Amber should automatically stop any active session at the lock time and offer a notification to resume tracking for the same project. This prevents recording idle/sleep time as billable work.

## Current State

- `NativeAppServiceProvider` registers NativePHP listeners manually via `Event::listen()`
- `StopSession::handle(Session $session)` always uses `CarbonImmutable::now()` as `endedAt`
- `HandleStopSessionFromNotification` uses `$event->index === 0` and `$event->reference` (session ULID) to route `NotificationActionClicked` events
- `ActivitySettings` has `idle_timeout_minutes`, `block_end_padding_minutes`, `manual_session_reminder_minutes` — no sleep/wake threshold yet
- `FileWatcherService::restart()` restarts the fswatch child process

## NativePHP Events Available

No direct `Suspend`/`Resume` events in the NativePHP PHP layer. The proxies are:

- `Native\Desktop\Events\PowerMonitor\ScreenLocked` — fires before sleep or on manual lock
- `Native\Desktop\Events\PowerMonitor\ScreenUnlocked` — fires on wake or manual unlock

## Strategy

1. Add `screen_lock_threshold_minutes` (default `5`) to `ActivitySettings`
2. Modify `StopSession::handle()` to accept an optional `?CarbonImmutable $at = null` for retroactive stopping
3. On `ScreenLocked`: if an active session exists, cache `{session_id, project_id, project_name, locked_at}`
4. On `ScreenUnlocked`:
   - Always restart fswatch
   - If no cached state: return
   - If away ≤ threshold: clear cache, do nothing (brief lock)
   - If away > threshold: stop session retroactively at `locked_at`, send "Resume" notification
5. On `NotificationActionClicked` with reference prefix `resume-session:`: start new session for that project

## Implementation Steps

### Step 1 — Settings migration

Create: `database/settings/2026_03_19_000000_add_screen_lock_threshold_to_activity_settings.php`

```php
$this->migrator->add('activity.screen_lock_threshold_minutes', 5);
```

### Step 2 — Update `ActivitySettings`

Add to `app/Settings/ActivitySettings.php`:

```php
public int $screen_lock_threshold_minutes;
```

### Step 3 — Modify `StopSession` action

Update `app/Actions/Session/StopSession.php` — add optional `$at` parameter:

```php
public function handle(Session $session, ?CarbonImmutable $at = null): Session
{
    $stopped = $this->updateSession->handle($session, new SessionData(
        endedAt: $at ?? CarbonImmutable::now(),
    ));

    SessionStopped::dispatch($stopped);

    return $stopped;
}
```

All existing callers pass no second argument — fully backward-compatible.

### Step 4 — `HandleScreenLocked` listener

Create `app/Listeners/HandleScreenLocked.php`.

Listens to: `Native\Desktop\Events\PowerMonitor\ScreenLocked`

Responsibilities:
- Load the active session with project eager-loaded; return early if none
- Cache under key `screen-lock-state` with TTL of 24 hours:
  - `session_id`, `project_id`, `project_name`, `locked_at` (ISO 8601 string)

### Step 5 — `HandleScreenUnlocked` listener

Create `app/Listeners/HandleScreenUnlocked.php`.

Listens to: `Native\Desktop\Events\PowerMonitor\ScreenUnlocked`

Injects: `StopSession`, `ActivitySettings`, `FileWatcherService`

Responsibilities:
1. Always restart fswatch: `$this->fileWatcherService->restart()`
2. Retrieve `Cache::get('screen-lock-state')`; return if null
3. Parse `locked_at` → `CarbonImmutable`
4. Calculate `$awayMinutes = $lockedAt->diffInMinutes(CarbonImmutable::now())`
5. Always clear cache: `Cache::forget('screen-lock-state')`
6. If `$awayMinutes <= $this->settings->screen_lock_threshold_minutes`: return
7. Find session by `$state['session_id']`; return if not found or already ended
8. Stop retroactively: `$this->stopSession->handle($session, $lockedAt)`
9. Send notification:

```php
Notification::title('Session paused')
    ->message("Away for {$awayMinutes} min. Resume {$state['project_name']}?")
    ->reference('resume-session:' . $state['project_id'])
    ->addAction('Resume')
    ->show();
```

### Step 6 — `HandleResumeSessionFromNotification` listener

Create `app/Listeners/HandleResumeSessionFromNotification.php`.

Listens to: `Native\Desktop\Events\Notifications\NotificationActionClicked`

Injects: `StartSession`

Responsibilities:
- Guard: `$event->index !== 0` → return
- Guard: reference does not start with `'resume-session:'` → return
- Extract project ID: `Str::after($event->reference, 'resume-session:')`
- Find project; return if not found
- Call `$this->startSession->handle($project)`

Both `HandleStopSessionFromNotification` and this listener handle `NotificationActionClicked` independently — each guards on its own reference convention.

### Step 7 — Register listeners in `NativeAppServiceProvider`

Add to `boot()`:

```php
Event::listen(ScreenLocked::class, HandleScreenLocked::class);
Event::listen(ScreenUnlocked::class, HandleScreenUnlocked::class);
```

`HandleResumeSessionFromNotification` is auto-discovered like all other listeners.

### Step 8 — Settings form and types

**`UpdateActivitySettingsRequest`** — add rule:
```php
'screen_lock_threshold_minutes' => ['required', 'integer', 'min:0', 'max:60'],
```

**`UpdateActivitySettings` action** — add branch:
```php
if (isset($data['screen_lock_threshold_minutes'])) {
    $this->settings->screen_lock_threshold_minutes = $data['screen_lock_threshold_minutes'];
}
```

**TypeScript types** — add `screen_lock_threshold_minutes: number` to `ActivitySettings`

**`Activity.vue`** — add to form initializer and grid:
```html
<InputField
    label="Screen lock threshold (min)"
    :error="form.errors.screen_lock_threshold_minutes"
    hint="If away longer than this after a screen lock, the active session is paused automatically. Set to 0 to disable."
>
    <Input v-model.number="form.screen_lock_threshold_minutes" type="number" min="0" max="60" />
</InputField>
```

### Step 9 — Tests

**`tests/Feature/Session/HandleScreenLockedTest.php`**
- caches lock state when an active session exists
- does nothing when no active session exists

**`tests/Feature/Session/HandleScreenUnlockedTest.php`**
- always restarts fswatch
- does nothing when no cached lock state
- does nothing when away duration is within threshold
- stops session retroactively at `locked_at` when away exceeds threshold
- sends resume notification after stopping
- clears cache after processing
- does nothing when cached session is already ended

**`tests/Feature/Session/HandleResumeSessionFromNotificationTest.php`**
- starts a new session when index is 0 and reference has the prefix
- ignores events with index != 0
- ignores events without the `resume-session:` prefix
- does nothing when project is not found

**`tests/Feature/Session/StopSessionTest.php`** (extend existing)
- stops session at a custom `$at` time (retroactive stop)

## Files to Create

| File | Purpose |
|------|---------|
| `database/settings/2026_03_19_000000_add_screen_lock_threshold_to_activity_settings.php` | Settings migration |
| `app/Listeners/HandleScreenLocked.php` | Cache lock state |
| `app/Listeners/HandleScreenUnlocked.php` | Stop session + restart fswatch + send notification |
| `app/Listeners/HandleResumeSessionFromNotification.php` | Start new session from notification |
| `tests/Feature/Session/HandleScreenLockedTest.php` | Tests |
| `tests/Feature/Session/HandleScreenUnlockedTest.php` | Tests |
| `tests/Feature/Session/HandleResumeSessionFromNotificationTest.php` | Tests |

## Files to Modify

| File | Change |
|------|--------|
| `app/Settings/ActivitySettings.php` | Add `$screen_lock_threshold_minutes` |
| `app/Actions/Session/StopSession.php` | Add optional `?CarbonImmutable $at` parameter |
| `app/Providers/NativeAppServiceProvider.php` | Register `ScreenLocked` + `ScreenUnlocked` listeners |
| `app/Http/Requests/Settings/UpdateActivitySettingsRequest.php` | Add validation rule |
| `app/Actions/Settings/UpdateActivitySettings.php` | Handle new setting |
| `resources/js/types/index.d.ts` (or `settings.ts`) | Extend `ActivitySettings` type |
| `resources/js/pages/settings/Activity.vue` | Add form field |
| `tests/Feature/Session/StopSessionTest.php` | Add retroactive stop test case |

## Design Decisions

**Why `Cache` and not the database for lock state?**
The lock state is transient — it only needs to survive from lock to next unlock. A cache entry with a 24-hour TTL is lightweight and avoids a migration for a throw-away row. If the app restarts between lock and unlock, the cache is lost — acceptable since the standard idle reconstruction will eventually handle the leaked session.

**Why `screen_lock_threshold_minutes = 0` disables the feature?**
Consistent with `manual_session_reminder_minutes = 0` disabling reminders. Users wanting to pause on every lock should set it to `1`.

**Why a prefixed reference (`resume-session:{projectId}`) instead of session ID?**
When the user unlocks, the old session is already stopped. A new one must be created. Storing the project ID (not the stopped session ULID) is the correct payload. The prefix disambiguates from the existing stop-session notification.

**Why register `ScreenLocked`/`ScreenUnlocked` manually in `NativeAppServiceProvider`?**
Following the existing pattern: `OpenedFromURL` is already registered manually in `NativeAppServiceProvider`. NativePHP broadcast events may not resolve correctly through auto-discovery in the desktop process.
