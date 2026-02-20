# Phase 2 — Menu Bar & Sessions

## Context

Phase 1 (foundation) is complete: models, CRUD for clients/projects/settings, layout, tests. Phase 2 adds the core tracking feature: work sessions managed from a macOS menu bar tray icon, with global keyboard shortcuts, notifications, and session pages in the main app.

The menu bar is context-menu only (no webview panel). It shows an idle/active icon and an elapsed-time label. Context menu items dispatch NativePHP events that trigger Laravel actions.

---

## Step 1: Session model scope

**Modify** `app/Models/Session.php`

- Add `#[Scope] protected function active(Builder $query)` → `whereNull('ended_at')`
- Allows `Session::active()->first()` to find the running session

## Step 2: SessionData DTO

**Create** `app/Data/SessionData.php`

- `project_id` (string, required), `notes` (?string)
- `fromArray()`, `toArray()` — same pattern as `ClientData`

## Step 3: Custom exception

**Create** `app/Exceptions/SessionAlreadyActiveException.php`

- Extends `RuntimeException`, accepts the active `Session` in constructor
- Thrown by `StartSession` when a session is already running

## Step 4: Laravel events

**Create** `app/Events/SessionStarted.php` and `app/Events/SessionStopped.php`

- Each has `public Session $session` constructor property
- Uses `Dispatchable`, `SerializesModels` traits

## Step 5: Actions

**Create** `app/Actions/Session/StartSession.php`

```
handle(Project $project, ?string $notes = null): Session
```
- Check no active session exists (throw `SessionAlreadyActiveException` if one does)
- `Session::create(...)` with `started_at => now()`, `source => SessionSource::Manual`
- Dispatch `SessionStarted` event
- Wrap in `DB::transaction()`

**Create** `app/Actions/Session/StopSession.php`

```
handle(Session $session): Session
```
- Set `ended_at => now()`, compute `duration_minutes`
- Dispatch `SessionStopped` event

**Create** `app/Actions/Session/SwitchSessionProject.php`

```
handle(Session $currentSession, Project $newProject): Session
```
- Inject `StopSession` and `StartSession` via method params
- Stop current, start new — all in one transaction

## Step 6: MenuBarService

**Create** `app/Services/MenuBarService.php`

Not an Action — a service that wraps NativePHP `MenuBar` and `Menu` facades.

- `initialize()` — called from `NativeAppServiceProvider::boot()`, creates the menu bar with `MenuBar::create()->icon()->label()->tooltip()->onlyShowContextMenu()->withContextMenu()`
- `refresh()` — queries active session, updates icon + label + context menu via `MenuBar::icon()`, `MenuBar::label()`, `MenuBar::contextMenu()`
- `updateLabel()` — only updates the elapsed-time label (called every minute by scheduler)
- `buildIdleMenu()` — "Start Session" with submenu of active projects (each item: `->id("start-session:{$project->id}")->event(StartSessionFromMenu::class)`), "Open App" link, Quit
- `buildActiveMenu(Session)` — current project label (disabled), elapsed time (disabled), "Stop Session" (event), "Switch Project" submenu (each: `->id("switch-project:{$project->id}")->event(SwitchProjectFromMenu::class)`), "Open App", Quit
- `resolveIcon(?Session)` — returns path to `public/icons/menubar-idle.png` or `menubar-active.png`
- `formatElapsed(Session)` — `sprintf('%02d:%02d', hours, minutes)`

Register as singleton in `AppServiceProvider`.

## Step 7: NativePHP event classes

**Create** in `app/Events/Native/`:

- `StartSessionFromMenu` — fired when "Start Session > Project X" clicked
- `StopSessionFromMenu` — fired when "Stop Session" clicked
- `SwitchProjectFromMenu` — fired when "Switch Project > Project Y" clicked
- `ToggleSessionShortcut` — fired by `Cmd+Shift+T`

Each receives `array $item = [], array $combo = []` in constructor (NativePHP passes menu item data).

## Step 8: Event listeners

**Create** in `app/Listeners/`:

- `HandleStartSessionFromMenu` — parses project ID from `$event->item['id']` (`Str::after('start-session:')`), calls `StartSession`
- `HandleStopSessionFromMenu` — finds active session, calls `StopSession`
- `HandleSwitchProjectFromMenu` — parses project ID, finds active session, calls `SwitchSessionProject`
- `HandleToggleSessionShortcut` — if active session → stop; else start on last-used project (or send notification if no project found)
- `RefreshMenuBarOnSessionChange` — listens to `SessionStarted` + `SessionStopped`, calls `MenuBarService::refresh()`
- `SendSessionNotification` — listens to `SessionStarted` + `SessionStopped`, sends NativePHP `Notification`

Register listeners in `AppServiceProvider::boot()` via `Event::listen()`.

## Step 9: NativeAppServiceProvider

**Modify** `app/Providers/NativeAppServiceProvider.php`

```php
public function boot(): void
{
    Window::open();

    app(MenuBarService::class)->initialize();

    GlobalShortcut::key('CmdOrCtrl+Shift+T')
        ->event(ToggleSessionShortcut::class)
        ->register();
}
```

## Step 10: Scheduled command for timer

**Create** `app/Console/Commands/UpdateMenuBarTimerCommand.php`

- Signature: `menubar:update-timer`
- Calls `MenuBarService::updateLabel()`

**Modify** `routes/console.php` — add `Schedule::command('menubar:update-timer')->everyMinute()`

## Step 11: FormRequests + Controller + Routes

**Create** `app/Http/Requests/Session/StoreSessionRequest.php`

- `project_id` → `['required', 'string', 'exists:projects,id']`
- `notes` → `['nullable', 'string']`

**Create** `app/Http/Requests/Session/StopSessionRequest.php` — minimal (notes update only)

**Create** `app/Http/Controllers/SessionController.php`

- `index()` → `Inertia::render('session/Index')` with paginated sessions + active projects
- `show(Session)` → `Inertia::render('session/Show')` with session + relations
- `store(StoreSessionRequest, StartSession)` → start session, redirect to sessions.index
- `stop(Session, StopSession)` → stop session, redirect to sessions.index

**Modify** `routes/web.php` — add:

```
GET  /sessions              → SessionController@index    (sessions.index)
POST /sessions              → SessionController@store    (sessions.store)
GET  /sessions/{session}    → SessionController@show     (sessions.show)
PATCH /sessions/{session}/stop → SessionController@stop  (sessions.stop)
```

## Step 12: Update SessionResource + shared Inertia props

**Modify** `app/Http/Resources/SessionResource.php` — add `'project' => ProjectResource::make($this->whenLoaded('project'))`

**Modify** `app/Http/Middleware/HandleInertiaRequests.php` — share `activeSession`:

```php
'activeSession' => fn () => ($s = Session::active()->with('project.client')->first())
    ? SessionResource::make($s) : null,
```

## Step 13: TypeScript types

**Modify** `resources/js/types/resources.ts` — add `Session` type

**Modify** `resources/js/types/global.d.ts` — add `activeSession: Session | null` to shared page props

## Step 14: Vue components

**Create** `resources/js/components/SessionTimer.vue`

- Props: `startedAt: string`
- Uses `setInterval(1000)` to update HH:MM:SS display

**Create** `resources/js/components/ActiveSessionBanner.vue`

- Reads `usePage().props.activeSession`
- Shows project name + `<SessionTimer>` + "Stop" button (PATCH to `sessions.stop`)
- Placed at top of main content area in AppLayout

**Create** `resources/js/components/StartSessionDialog.vue`

- Select active project + optional notes field
- Submit POSTs to `sessions.store`

## Step 15: Vue pages

**Create** `resources/js/pages/session/Index.vue`

- Paginated list of sessions with project name, start time, duration/"Active" badge, source
- "Start Session" button opens `StartSessionDialog`
- Uses Inertia `<Link>` to session show page

**Create** `resources/js/pages/session/Show.vue`

- Session details: project, start/end, duration, notes, validation status
- If active: shows `<SessionTimer>` + "Stop" button

## Step 16: Layout updates

**Modify** `resources/js/layouts/AppLayout.vue` — add `<ActiveSessionBanner />` above `<main>`

**Modify** `resources/js/components/AppSidebar.vue` — add "Sessions" link between Clients and Settings

## Step 17: Menu bar icons

**Create** `public/icons/menubar-idle.png` and `public/icons/menubar-active.png`

- 22x22px PNG template images for macOS menu bar
- Idle: outline/grayscale timer icon
- Active: filled/colored timer icon

## Step 18: Tests

**Create** `tests/Feature/Session/StartSessionTest.php`

- Controller delegation with `StartSession::fake()`, validation tests
- Action unit test: creates session, fires event, throws on duplicate

**Create** `tests/Feature/Session/StopSessionTest.php`

- Controller delegation, action sets `ended_at` + `duration_minutes`, fires event

**Create** `tests/Feature/Session/SwitchSessionProjectTest.php`

- Stops old session, starts new one

**Create** `tests/Feature/Session/ListSessionsTest.php`

- Index renders with sessions, show renders with session data

## Step 19: Verification

1. `vendor/bin/pint --dirty --format agent`
2. `composer test:all` (Pint + Rector + PHPStan + Pest)
3. `php artisan migrate:fresh --seed`
4. `composer run native:dev` — test menu bar idle/active states, shortcuts, notifications

---

## Implementation order

Steps 1–5 (model + DTO + exception + events + actions) → Step 6 (MenuBarService) → Steps 7–8 (NativePHP events + listeners) → Step 9 (NativeAppServiceProvider) → Step 10 (scheduler) → Step 11 (controller + routes) → Step 12 (resource + shared props) → Steps 13–16 (frontend) → Step 17 (icons) → Step 18 (tests) → Step 19 (verify)

---

## Key files to reference

| Pattern | Reference file |
|---------|---------------|
| Action base class | `app/Actions/Action.php` |
| Action example | `app/Actions/Client/CreateClient.php` |
| DTO example | `app/Data/ClientData.php` |
| Controller pattern | `app/Http/Controllers/ClientController.php` |
| FormRequest pattern | `app/Http/Requests/Client/StoreClientRequest.php` |
| Test pattern | `tests/Feature/Client/CreateClientTest.php` |
| SessionResource | `app/Http/Resources/SessionResource.php` |
| Session model | `app/Models/Session.php` |
| NativeAppServiceProvider | `app/Providers/NativeAppServiceProvider.php` |
| HandleInertiaRequests | `app/Http/Middleware/HandleInertiaRequests.php` |
| AppLayout | `resources/js/layouts/AppLayout.vue` |
| TypeScript types | `resources/js/types/resources.ts` |
| Shared props types | `resources/js/types/global.d.ts` |
