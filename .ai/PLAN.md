# Activity Report Tracker — Architecture Plan & Roadmap

## Context

macOS desktop app (NativePHP + Electron) that automatically tracks development activity and generates monthly activity reports (CRA). Built on Laravel 12, NativePHP, Inertia/Vue 3, Tailwind v4, Actions pattern.

**Key decisions**: multi-project from day one, multiple activity sources (Git + GitHub + fswatch + Claude Code), menu bar from the start, activity reports/PDF last. Laravel HTTP `sessions` table removed — the term "session" refers to work sessions. Optional LLM summarization via Laravel AI SDK (multi-provider: Mistral, OpenAI, Anthropic, Ollama).

---

## Database Schema

### Tables

**clients**: id (ulid), name, address?, contact_name?, contact_email?, contact_phone?, notes?, timestamps

**projects**: id (ulid), client_id (FK cascade), name, color (hex), tjm? (decimal 10,2), hourly_rate? (decimal 10,2), daily_reference_hours (decimal 4,2 default 7), rounding (enum default quarter), is_active (bool default true), timestamps — INDEX(client_id, is_active)

**project_repositories**: id (ulid), project_id (FK cascade), local_path (unique), name, timestamps

**sessions**: id (ulid), project_id (FK cascade), started_at (datetime), ended_at? (datetime), duration_minutes? (int), source (enum: manual/auto/reconstructed), notes?, is_validated (bool default false), timestamps — INDEX(project_id, started_at), INDEX(started_at, ended_at)
> Note: the Laravel HTTP `sessions` migration is deleted. This table is 100% domain-specific.

**activity_events**: id (ulid), project_id? (FK cascade), project_repository_id? (FK), session_id? (FK set null), source_type (string — source identifier: git, claude_code, filesystem), type (enum: git_commit/file_change/claude_session_start/claude_session_end/claude_file_touch), occurred_at (datetime), metadata (json), timestamps — INDEX(project_id, occurred_at), INDEX(source_type, occurred_at)

**time_entries**: id (ulid), session_id? (FK cascade), project_id (FK cascade), date, started_at, ended_at, raw_minutes (int), rounded_minutes (int), source (enum: session/manual/reconstructed), description?, is_validated (bool default true), timestamps — INDEX(project_id, date)

**app_settings**: id, key (unique), value (json), timestamps

**activity_reports**: id (ulid), client_id (FK cascade), month (int), year (int), status (enum: draft/finalized/sent), total_minutes, total_days (decimal 5,2), total_amount_ht? (decimal 10,2), generated_at?, pdf_path?, notes?, timestamps — UNIQUE(client_id, month, year)

**activity_report_lines**: id (ulid), activity_report_id (FK cascade), project_id (FK), date, minutes (int), days (decimal 4,2), description?, timestamps

### Enums (`app/Enums/`)

- `RoundingStrategy`: quarter, half_hour, hour
- `SessionSource`: manual, auto, reconstructed
- `ActivityEventType`: git_commit, git_branch_switch, git_pr_opened, git_pr_merged, file_change, claude_session_start, claude_session_end, claude_file_touch
- `TimeEntrySource`: session, manual, reconstructed
- `ActivityReportStatus`: draft, finalized, sent

---

## Activity Sources Architecture

### `ActivitySource` Interface

```php
// app/Contracts/ActivitySource.php
interface ActivitySource
{
    /** Unique source identifier (e.g. 'git', 'claude_code', 'filesystem') */
    public function identifier(): string;

    /** Scan activity since $since and return unpersisted ActivityEvent instances */
    public function scan(CarbonImmutable $since): Collection;

    /** Whether this source is available/configured */
    public function isAvailable(): bool;
}
```

### Implementations

- `GitActivitySource` — parses `git log` for configured repos, filters by author email. Also captures current branch name, diff stats (lines added/removed), and branch switches via `git reflog`
- `GitHubActivitySource` — uses `gh` CLI to fetch PRs (opened, merged, title, description) and reviews for project repos. Requires `gh` installed and authenticated
- `ClaudeCodeActivitySource` — parses JSONL files in `~/.claude/projects/`, matches to project via path
- `FilesystemActivitySource` — receives real-time fswatch events, converts them to `ActivityEvent`
- `GeminiActivitySource` — Gemini AI session logs
- `MistralVibeActivitySource` — Mistral Vibe session logs
- `OpencodeActivitySource` — Opencode AI session logs (uses `part` table)

### Orchestration

```php
// app/Actions/Activity/ScanActivitySources.php
class ScanActivitySources extends Action
{
    public function handle(ActivitySource $source, CarbonImmutable $since): Collection
    {
        return $source->scan($since)
            ->each(fn (ActivityEvent $event) => $this->recordEvent->handle($event));
    }
}
```

Sources are registered via a ServiceProvider. Adding a new source = implement the interface + register it. No duplication of `RecordActivityEvent` logic.

---

## Actions by Domain

### Clients (`app/Actions/Clients/`)

- `CreateClient`, `UpdateClient`, `DeleteClient`

### Projects (`app/Actions/Projects/`)

- `CreateProject`, `UpdateProject`, `DeleteProject`
- `AttachRepository`, `DetachRepository`, `ValidateRepositoryPath`

### Sessions (`app/Actions/Sessions/`)

- `StartSession`, `StopSession`, `SwitchSessionProject`
- `UpdateSession`, `DeleteSession`

### Activity (`app/Actions/Activity/`)

- `RecordActivityEvent` — single entry point for persisting an event
- `ScanActivitySources` — runs a scan for a given source
- `DetectActiveProject` — matches an event to a project via repo paths
- `CheckIdleStatus` — calculates idle duration since last event

### Monitoring (`app/Actions/Monitoring/`)

- `StartActivityMonitor`, `StopActivityMonitor` — manages the fswatch process
- `ProcessFileChangeEvent` — receives an fswatch event, creates an `ActivityEvent`
- `HandleActivityDetected` — notifies if no active session
- `HandleIdleTimeout` — notification + auto-stop

### TimeEntries (`app/Actions/TimeEntries/`)

- `CreateTimeEntry`, `UpdateTimeEntry`, `DeleteTimeEntry`
- `GenerateTimeEntriesFromSession`, `ReconstructDayEntries`, `RoundMinutes`

### ActivityReport (`app/Actions/ActivityReport/`)

- `CollectDayContext` — aggregates commits, PRs, branches, Claude prompts for a given day/project
- `GenerateActivityReport`, `RegenerateActivityReport`, `FinalizeActivityReport`
- `SummarizeDayActivity` — sends aggregated context to LLM (optional) and returns a summary
- `GenerateActivityReportPdf`, `ExportActivityReportExcel`

### Settings (`app/Actions/Settings/`)

- `UpdateSettings`, `GetSettings`

---

## Routes (`routes/web.php`)

```
GET  /                              → DashboardController (invokable)
Resource clients                    → ClientController
POST clients/{client}/projects      → ProjectController@store
Resource projects (except create/store/index)
POST projects/{project}/repositories → ProjectRepositoryController@store
DELETE repositories/{repo}          → ProjectRepositoryController@destroy
POST sessions/start                 → StartSessionController
POST sessions/{session}/stop        → StopSessionController
POST sessions/{session}/switch      → SwitchSessionController
Resource sessions (index/show/update/destroy)
Resource time-entries               → TimeEntryController
POST time-entries/reconstruct       → ReconstructTimeEntriesController
GET  timeline                       → TimelineController@index
GET  timeline/{date}                → TimelineController@show
Resource activity-reports           → ActivityReportController
GET  settings                       → SettingsController@edit
PUT  settings                       → SettingsController@update

API (menu bar):
GET api/active-session, GET api/today-summary, GET api/projects/active
```

---

## NativePHP Integration

- **Menu bar**: `MenuBar::create()` with idle/active icon, dynamic context menu via `MenuBarService`
- **Shortcuts**: `Cmd+Shift+T` toggle session, `Cmd+Shift+S` switch project
- **fswatch**: `ChildProcess::start(['fswatch', '-r', ...paths])` with `MessageReceived` listener
- **Notifications**: activity detected, idle warning, auto-stop, activity report reminder
- **Scheduler**: source scan (5min), idle check (1min), menu bar update (1min)

---

## Vue Pages (`resources/js/pages/`)

```
Dashboard.vue
client/     Index, Show
project/    Index, Show
session/    Index, Show
timeline/   Index, Show
activity/   Index
report/     Index, Show
settings/   General, Sources, Activity, Ai
```

---

## Roadmap

### ✅ Phase 1 — Foundation (models, CRUD, layout)

1. Remove Laravel HTTP `sessions` and `password_reset_tokens` migrations
2. All domain migrations (except activity_reports/activity_report_lines → Phase 5)
3. Models with relationships, factories, seeders
4. Enums
5. DTOs in `app/Data/`
6. API Resources
7. Actions + Controllers + FormRequests for Clients and Projects (with repos)
8. `AppLayout.vue` with sidebar navigation
9. Client pages (Index, Create, Show, Edit)
10. Project pages (Show, Create, Edit) + repository management
11. Basic Settings page
12. Seeder with example data
13. Feature tests for all CRUD

### ✅ Phase 2 — Menu bar & sessions

1. `NativeAppServiceProvider` configured (menu bar, icons, shortcuts)
2. `MenuBarService` for dynamic context menu
3. Session actions: `StartSession`, `StopSession`, `SwitchSessionProject`
4. Session controllers and routes
5. `ActiveSessionBanner.vue`, `SessionTimer.vue`, `StartSessionDialog.vue`
6. Session pages (Index, Show)
7. Menu bar states: idle vs active with timer
8. Laravel events: `SessionStarted`, `SessionStopped`
9. Session tests

### ✅ Phase 3 — Activity detection (generic architecture)

1. `ActivitySource` interface in `app/Contracts/`
2. Implementations: `GitActivitySource`, `ClaudeCodeActivitySource`, `FilesystemActivitySource`
3. Source registration via ServiceProvider
4. `ScanActivitySources` — runs a scan for a given source
5. `RecordActivityEvent` — single persistence entry point
6. `DetectActiveProject` — event → project matching
7. `CheckIdleStatus`, `HandleActivityDetected`, `HandleIdleTimeout`
8. fswatch via `ChildProcess` + `ProcessFileChangeEvent`
9. Scheduled jobs: `ScanActivitySourcesJob`, `CheckIdleStatusJob`
10. NativePHP notifications
11. Tests for each source + orchestration

### ✅ Phase 4 — Timeline, time entries & reconstruction

1. `GenerateTimeEntriesFromSession`, `RoundMinutes`
2. `ReconstructDayEntries` — algorithm (gap grouping, padding, rounding)
3. TimeEntry CRUD
4. Timeline pages (Index, Show with `DaySummaryCard`)
5. Inline-editable time entries
6. Manual entries (meetings, calls)
7. `Dashboard.vue` with daily summary
8. `EndOfDayReconstructionJob`
9. Rounding and reconstruction tests

### ✅ Phase 4.5 — Activity source enrichment

Goal: capture enough semantic context to describe **what was done** (not just when). This data feeds activity report generation (Phase 5).

#### Git enrichment

1. Add **branch name** to `GitCommit` event metadata (`git log --format=%D`)
2. Add **diff stats** per commit to metadata: lines added/removed, files changed (`git log --numstat`)
3. New `GitBranchSwitch` type — detect branch changes via `git reflog` to contextualize sessions

#### GitHub source (PRs & reviews)

1. New `GitHubActivitySource` — requires `gh` CLI installed and authenticated
2. Detect GitHub repos among `ProjectRepository` (via `git remote -v`)
3. Fetch user **PRs**: title, description, state (opened/merged/closed), dates (`gh pr list --author @me --json`)
4. New `GitPrOpened`, `GitPrMerged` types — events with title and description in metadata
5. Optional: fetch reviews received/given to enrich context
6. `github_username` setting in `app_settings` to filter by author
7. Tests for each new source and event type

#### Claude Code enrichment

1. Extract **user messages** (prompts) from Claude Code JSONL files to capture work intent in metadata

### ✅ Phase 5 — Activity Reports & PDF generation

1. `activity_reports`, `activity_report_lines` migrations
2. Actions: `GenerateActivityReport`, `RegenerateActivityReport`, `FinalizeActivityReport`
3. `CollectDayContext` — aggregates for a given day/project: commits (messages + branch), PRs (titles), Claude prompts, diff stats
4. Auto-descriptions for `activity_report_lines` based on aggregated context (without LLM: structured concatenation of commit messages, branch names, PR titles)
5. PDF package + Blade PDF template
6. ActivityReport pages (Index, Show)
7. Excel/CSV export
8. Generation tests

#### LLM option (intelligent summarization via Laravel AI SDK)

1. `laravel/ai` — multi-provider abstraction (Mistral, OpenAI, Anthropic, Ollama)
2. `ai_provider` + `ai_api_key` + `ai_model` settings in `app_settings` (disabled by default)
3. `SummarizeDayActivity` action — sends aggregated daily context to LLM, returns a concise summary (1-2 sentences per project)
4. Configurable prompt template in `config/activity-report.php` with variables: `{commits}`, `{branches}`, `{prs}`, `{claude_prompts}`, `{files_changed}`
5. Integration in `GenerateActivityReport`: if LLM enabled, replace raw descriptions with LLM summaries in `activity_report_lines.description`
6. "Summarize with AI" button on ActivityReport Show page to regenerate descriptions on demand
7. User can manually edit descriptions after generation (LLM or not)
8. Tests with fake AI provider

### Phase 6 — Polish

#### ✅ Already done

- ✅ In-context onboarding (Dashboard checklist + spotlight, `GetOnboardingState`, `DismissOnboarding`)
- ✅ `amber://` deep links (start/stop session from Raycast, Alfred, scripts)
- ✅ Settings > Reset Database (fresh migration with confirmation dialog)
- ✅ Settings > Sources (SyncActivityDialog, per-source progress, parallel syncs)
- ✅ Settings > General (theme preview without save, timezone)
- ✅ App icon

#### To do — UX & quality of life

1. ✅ **Native folder picker** for `local_path` when creating/editing a `ProjectRepository` — auto-fill `name` from the selected folder's basename. Also available in `ProjectSheet` (inline repos) and `SourceConfigurationSheet` (via new `folder-path` field type). Reusable `FolderPathInput.vue` component.
2. **Quick note during active session** — note field accessible from the session banner or sidebar, saved to `Session.notes`
3. **Recent projects** in `StartSessionDialog` — show last-used projects first
4. **Skeletons / deferred props** — loading states for heavy lists (activity events, timeline)

#### To do — Native macOS

1. **Global shortcuts** — `Cmd+Shift+T` toggle session, `Cmd+Shift+S` switch project (NativePHP `GlobalShortcut`)
2. **Dock badge** with active session timer (updated every minute)
3. **System notification for long sessions** — alert after N hours without a break (configurable in Settings)
4. **System notification when a background sync completes**

#### To do — Robustness

1. **Auto-sync on startup** (optional toggle in Settings > Sources) — triggers a source scan when the app launches
2. **Error handling** — missing repo, fswatch not found: clear UI error messages instead of silent failures
3. **SQLite backup/restore** — manual export/import of the database
4. **Auto-updater**

---

## Verification

Each phase: `composer test:all` (Pint, Rector, PHPStan, Pest) + manual testing via `php artisan native:serve` + `route:list` + `database-schema`.
