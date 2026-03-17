# Plan: Active Session UX Improvements

## Context

Amber auto-tracks activity events and reconstructs sessions every 5 minutes in Replace mode. However, the UX has two friction points:

1. **No "live" feedback without a manual session** — between reconstruction runs, the dashboard shows nothing happening even if events are being captured. The only "live" state is a manual session (open `ended_at`), which the user has to explicitly start.
2. **"Start Session" is misleading** — it's the most prominent action in the TitleBar, implying the user *must* do something to track their work. This contradicts the app's core promise of automatic tracking. Its real use case is narrow: log untracked time (meetings, reviews, etc.).

**Already done:** Schedule runs every 5 minutes, `ReconstructSessionsCommand` uses `Replace` mode by default.

---

## Phase 1 — Inferred "currently active" state

### Goal
When there are recent activity events but no open manual session, show `● Active · Xh Ym` in the TitleBar — derived from events, no new stored session model needed.

### Backend

**New: `app/ViewModels/InferredActivityViewModel.php`**
- Query `ActivityEvent` where `occurred_at >= now()->subMinutes($settings->idle_timeout_minutes)` and `project_id IS NOT NULL`
- Group by `project_id`, for each compute `since = min(occurred_at)` in the window
- Eager-load `project.client`
- Return `null` if no events, otherwise array of `{ project: ProjectResource, since: ISO string }`
- Implement as an Inertia `Invokable` ViewModel (same pattern as `app/ViewModels/ActiveProjectsViewModel.php`)

**`app/Http/Middleware/HandleInertiaRequests.php`**
- Add `new InferredActivityViewModel` to the shared props array (lazy, same as `ActiveProjectsViewModel`)
- Exposes `inferredActivity: InferredProject[] | null` to all pages

### Frontend

**`resources/js/components/TitleBar.vue`**
- Add `inferredActivity` from `page.props`
- Extend native event listeners to also reload `inferredActivity`:
  ```ts
  useNativeEvent('App\\Events\\SessionStarted', () => router.reload({ only: ['activeSession', 'inferredActivity'] }));
  useNativeEvent('App\\Events\\SessionStopped', () => router.reload({ only: ['activeSession', 'inferredActivity'] }));
  ```
- Add polling via `setInterval` every 2 minutes (when no `activeSession`) to reload `inferredActivity`
- Right-side priority: `activeSession` (manual) › `inferredActivity` › nothing
- When `inferredActivity` and no `activeSession`:
  - Single project: `● Client — Project · Xh Ym` (inline, no interaction)
  - Multiple projects: `● Active · Xh Ym` pill wrapped in `Popover`, listing each project + `since` time
  - **No Stop, no Notes** — inferred activity is display-only

**TypeScript type** (wherever global types live):
```ts
type InferredProject = { project: Project; since: string }
```

---

## Phase 2 — Rename & improve "Start Session" sheet

### Goal
Make "Start Session" clearly secondary — for edge cases (meetings, reviews, non-tracked work).

**`resources/js/components/StartTimerSheet.vue`**
- Title: `"Manual Session"`
- Add `SheetDescription`: `"Track time for work that isn't automatically captured — meetings, code reviews, pair programming, training."`
- Notes placeholder: `"Meeting agenda, decisions, context…"`
- Submit button: `"Start manual session"`

**`resources/js/components/TitleBar.vue`**
- Button text: `"Manual session"` (was `"Start session"`)

---

## Phase 3 — Tests

**`tests/Unit/ViewModels/InferredActivityViewModelTest.php`**
- Returns `null` when no recent events
- Returns projects with correct `since` when events exist within idle window
- Excludes events older than `idle_timeout_minutes`
- Handles multiple projects correctly

---

## Files

| File | Action |
|---|---|
| `app/ViewModels/InferredActivityViewModel.php` | Create |
| `app/Http/Middleware/HandleInertiaRequests.php` | Modify — add shared prop |
| `resources/js/components/TitleBar.vue` | Modify — inferred state + rename button |
| `resources/js/components/StartTimerSheet.vue` | Modify — rename + description |
| `tests/Unit/ViewModels/InferredActivityViewModelTest.php` | Create |

---

## Verification

1. Recent events in DB + no manual session → TitleBar shows `● Active · Xm`
2. After `idle_timeout_minutes` of no new events → pill disappears on next poll
3. Start manual session → switches to active session UI (timer, Notes, Stop)
4. Stop manual session → returns to inferred pill if events still recent
5. Multi-project → clicking pill opens popover with project list
6. `StartTimerSheet` shows new title and description
7. Tests: `php artisan test --compact --filter=InferredActivityViewModel`
