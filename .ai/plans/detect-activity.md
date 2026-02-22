# Phase 3 — Détection d'activité

## Context

Phase 2 (menu bar & sessions) is complete. The app can start/stop work sessions manually from the menu bar or UI. Phase 3 adds **automatic activity detection** from three sources (Git commits, Claude Code sessions, filesystem changes), enabling the app to know *when* the user is working and *on which project* — without manual input.

All database tables (`activity_events`, `project_repositories`), models (`ActivityEvent`, `ProjectRepository`), enums (`ActivityEventType`), and factories already exist from Phase 1.

---

## Step 1: Interface + DTO + Config

**Create** `app/Contracts/ActivitySource.php`

```php
interface ActivitySource
{
    public function identifier(): string;
    public function scan(CarbonImmutable $since): Collection; // returns Collection<ActivityEventData>
    public function isAvailable(): bool;
}
```

**Create** `app/Data/ActivityEventData.php`

- Readonly constructor: `type` (ActivityEventType), `sourceType` (string), `occurredAt` (CarbonImmutable), `metadata` (array), `?projectId`, `?projectRepositoryId`, `?filePath`
- `filePath` is used by `DetectActiveProject` to resolve project from path

**Create** `config/activity.php`

- `idle_timeout_minutes` (default 15)
- `scan_interval_minutes` (default 5)
- `git.author_email` — filter commits by author
- `fswatch.enabled`, `fswatch.debounce_seconds` (default 3), `fswatch.excluded_patterns` (`.git/`, `node_modules/`, `vendor/`, etc.)
- `claude.projects_path` (default `~/.claude/projects`)

---

## Step 2: Core Actions

**Create** `app/Actions/Activity/DetectActiveProject.php`

```php
handle(?string $filePath): array // returns ['project_id' => ?string, 'project_repository_id' => ?string]
```

- Loads all `ProjectRepository` records
- Matches `$filePath` against each `local_path` (starts-with check)
- Picks the most specific match (longest path)

**Create** `app/Actions/Activity/RecordActivityEvent.php`

```php
__construct(DetectActiveProject $detectActiveProject)
handle(ActivityEventData $data): ActivityEvent
```

- If `$data->projectId` is null and `$data->filePath` is set → calls `DetectActiveProject`
- Links to active session if `project_id` matches
- Creates `ActivityEvent` in DB
- Dispatches `ActivityDetected` event

**Create** `app/Actions/Activity/ScanAllSources.php`

```php
__construct(RecordActivityEvent $recordEvent, array $sources = [])
handle(CarbonImmutable $since): Collection
```

- Filters `$sources` by `isAvailable()`
- FlatMaps `scan($since)` across all sources
- Deduplicates by `[type, occurred_at, sourceType]`
- Records each via `RecordActivityEvent`
- `$sources` injected via `AppServiceProvider` contextual binding

**Create** `app/Actions/Activity/CheckIdleStatus.php`

```php
handle(): void
```

- Returns early if no active session
- Gets latest `ActivityEvent` for session's project (or falls back to `session->started_at`)
- If idle > `config('activity.idle_timeout_minutes')` → dispatches `IdleTimeoutReached`

---

## Step 3: Events + Listeners

**Create** events:
- `app/Events/ActivityDetected.php` — carries `ActivityEvent $activityEvent`
- `app/Events/IdleTimeoutReached.php` — carries `Session $session`, `CarbonImmutable $lastActivityAt`

**Create** listeners:
- `app/Listeners/HandleActivityDetected.php` — if no active session + resolved project → sends NativePHP notification ("Activity detected on {project} — Start a session?"). Refreshes menu bar.
- `app/Listeners/HandleIdleTimeout.php` — auto-stops session via `StopSession`, sends notification ("Session stopped after {minutes}min idle on {project}")
- `app/Listeners/HandleFileWatcherMessage.php` — listens to `Native\Desktop\Events\ChildProcess\MessageReceived`, filters by alias `file-watcher`, debounces, calls `RecordActivityEvent`

**Modify** `app/Providers/AppServiceProvider.php` — wire in `configureEvents()`:
```php
Event::listen(ActivityDetected::class, HandleActivityDetected::class);
Event::listen(IdleTimeoutReached::class, HandleIdleTimeout::class);
Event::listen(MessageReceived::class, HandleFileWatcherMessage::class);
```

---

## Step 4: Activity Sources

**Create** `app/Services/ActivitySources/GitActivitySource.php` — implements `ActivitySource`

- `identifier()` → `'git'`
- `isAvailable()` → checks `git --version` via `Symfony\Component\Process\Process`
- `scan($since)` → for each `ProjectRepository` with `.git` dir:
  - Runs `git -C {path} log --format='%H|%ae|%aI|%s' --after='{since}'`
  - Filters by `config('activity.git.author_email')` if set
  - Maps to `ActivityEventData(type: GitCommit, sourceType: 'git', projectId: $repo->project_id, ...)`

**Create** `app/Services/ActivitySources/ClaudeCodeActivitySource.php` — implements `ActivitySource`

- `identifier()` → `'claude-code'`
- `isAvailable()` → checks `~/.claude/projects/` directory exists
- `scan($since)` → reads Claude project dirs (encoded path → match to repos), parses JSONL session files, maps to `ActivityEventData` with types `ClaudeSessionStart`/`ClaudeSessionEnd`/`ClaudeFileTouch`

**Create** `app/Services/FileWatcherService.php` — **NOT** an ActivitySource (push-based, not pull)

```php
public static function make(): self { return app(self::class); }
public function start(): void    // ChildProcess::start('fswatch -r {paths} --exclude ...', alias: 'file-watcher')
public function stop(): void     // ChildProcess::stop('file-watcher')
public function restart(): void  // stop + start
```

- Watches all `ProjectRepository` paths from active projects
- Single fswatch process watching all directories
- Output handled by `HandleFileWatcherMessage` listener

---

## Step 5: Service Provider Registration

**Modify** `app/Providers/AppServiceProvider.php` — in `register()`:

```php
$this->app->singleton(FileWatcherService::class);
$this->app->when(ScanAllSources::class)->needs('$sources')->give(fn () => [
    app(GitActivitySource::class),
    app(ClaudeCodeActivitySource::class),
]);
```

**Modify** `app/Providers/NativeAppServiceProvider.php` — in `boot()`, after menu bar init:

```php
if (config('activity.fswatch.enabled')) {
    FileWatcherService::make()->start();
}
```

---

## Step 6: Scheduled Commands

**Create** `app/Console/Commands/ScanActivitySourcesCommand.php`

- Signature: `activity:scan`
- Calls `ScanAllSources::handle(now()->subMinutes(config('activity.scan_interval_minutes') + 1))`

**Create** `app/Console/Commands/CheckIdleStatusCommand.php`

- Signature: `activity:check-idle`
- Calls `CheckIdleStatus::handle()`

**Modify** `routes/console.php`:

```php
Schedule::command('activity:scan')->everyFiveMinutes();
Schedule::command('activity:check-idle')->everyMinute();
```

---

## Step 7: Tests

| Test file | Group | Tests |
|-----------|-------|-------|
| `tests/Feature/Activity/DetectActiveProjectTest.php` | actions, activity | resolves project from path, picks longest match, returns nulls |
| `tests/Feature/Activity/RecordActivityEventTest.php` | actions, activity | creates event, calls DetectActiveProject, links to session, dispatches event |
| `tests/Feature/Activity/ScanAllSourcesTest.php` | actions, activity | scans available sources, skips unavailable, deduplicates |
| `tests/Feature/Activity/CheckIdleStatusTest.php` | actions, activity | no-op without session, no-op if recent activity, dispatches IdleTimeoutReached |
| `tests/Unit/ActivitySources/GitActivitySourceTest.php` | activity, sources | parses git log, filters by email, handles missing repos |
| `tests/Unit/ActivitySources/ClaudeCodeActivitySourceTest.php` | activity, sources | parses session files, filters by date |
| `tests/Feature/Activity/HandleFileWatcherMessageTest.php` | listeners, activity | ignores other aliases, records event, debounces |
| `tests/Feature/Activity/HandleActivityDetectedTest.php` | listeners, activity | notifies when no session, no-op when session active |
| `tests/Feature/Activity/HandleIdleTimeoutTest.php` | listeners, activity | stops session, sends notification |

---

## Implementation Order

1. Config + DTO + Interface (Step 1)
2. `DetectActiveProject` (Step 2)
3. Events: `ActivityDetected`, `IdleTimeoutReached` (Step 3)
4. `RecordActivityEvent` (Step 2)
5. `ScanAllSources` (Step 2)
6. `CheckIdleStatus` (Step 2)
7. `GitActivitySource` (Step 4)
8. `ClaudeCodeActivitySource` (Step 4)
9. `FileWatcherService` + `HandleFileWatcherMessage` (Step 4)
10. `HandleActivityDetected` + `HandleIdleTimeout` (Step 3)
11. Service provider wiring (Step 5)
12. Commands + scheduler (Step 6)
13. Tests (Step 7)
14. `vendor/bin/pint --dirty` + `composer test:all`

---

## Key Files to Reference

| Pattern | File |
|---------|------|
| Action base class | `app/Actions/Action.php` |
| Composite action (constructor DI) | `app/Actions/Session/SwitchSessionProject.php` |
| DTO pattern | `app/Data/SessionData.php` |
| Service (singleton, static make) | `app/Services/MenuBarService.php` |
| Event pattern | `app/Events/SessionStarted.php` |
| Listener with DI + notification | `app/Listeners/SendSessionNotification.php` |
| Listener for native event | `app/Listeners/HandleStartSessionFromMenu.php` |
| Scheduled command | `app/Console/Commands/UpdateMenuBarTimerCommand.php` |
| Event wiring | `app/Providers/AppServiceProvider.php` → `configureEvents()` |
| NativePHP boot | `app/Providers/NativeAppServiceProvider.php` |
| ActivityEvent model | `app/Models/ActivityEvent.php` |
| ProjectRepository model | `app/Models/ProjectRepository.php` |

## Verification

1. `vendor/bin/pint --dirty --format agent`
2. `composer test:all`
3. `php artisan activity:scan` — should scan git + claude sources
4. `php artisan activity:check-idle` — should no-op or warn
5. `composer run native:dev` — verify fswatch starts, file changes create events, idle timeout triggers notification
