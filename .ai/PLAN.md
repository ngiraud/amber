# CRA Tracker — Plan d'architecture et roadmap

## Contexte

App desktop macOS (NativePHP + Electron) qui tracke automatiquement l'activité de développement et génère des CRA mensuels. Le scaffold existe (Laravel 12, NativePHP, Inertia/Vue
3, Tailwind v4, Actions pattern). Tout le domaine métier est à construire.

**Décisions** : multi-projet dès le départ, 3 sources d'activité (Git + fswatch + Claude Code), menu bar dès le début, CRA/PDF en dernier. Table `sessions` Laravel HTTP supprimée —
le terme "session" désigne nos sessions de travail.

---

## Schema base de données

### Tables

**clients** : id (ulid), name, address?, contact_name?, contact_email?, contact_phone?, notes?, timestamps

**projects** : id (ulid), client_id (FK cascade), name, color (hex), tjm? (decimal 10,2), hourly_rate? (decimal 10,2), daily_reference_hours (decimal 4,2 default 7), rounding (enum
default quarter), is_active (bool default true), timestamps — INDEX(client_id, is_active)

**project_repositories** : id (ulid), project_id (FK cascade), local_path (unique), name, timestamps

**sessions** : id (ulid), project_id (FK cascade), started_at (datetime), ended_at? (datetime), duration_minutes? (int), source (enum: manual/auto/reconstructed), notes?,
is_validated (bool default false), timestamps — INDEX(project_id, started_at), INDEX(started_at, ended_at)
> Note : la migration Laravel `sessions` (HTTP) est supprimée. Cette table est 100% domaine métier.

**activity_events** : id (ulid), project_id? (FK cascade), project_repository_id? (FK), session_id? (FK set null), source_type (string — identifiant de la source : git,
claude_code, filesystem), type (enum: git_commit/file_change/claude_session_start/claude_session_end/claude_file_touch), occurred_at (datetime), metadata (json), timestamps —
INDEX(project_id, occurred_at), INDEX(source_type, occurred_at)

**time_entries** : id (ulid), session_id? (FK cascade), project_id (FK cascade), date, started_at, ended_at, raw_minutes (int), rounded_minutes (int), source (enum:
session/manual/reconstructed), description?, is_validated (bool default true), timestamps — INDEX(project_id, date)

**app_settings** : id, key (unique), value (json), timestamps

**cra_reports** : id (ulid), client_id (FK cascade), month (int), year (int), status (enum: draft/finalized/sent), total_minutes, total_days (decimal 5,2), total_amount_ht? (
decimal 10,2), generated_at?, pdf_path?, notes?, timestamps — UNIQUE(client_id, month, year)

**cra_report_lines** : id (ulid), cra_report_id (FK cascade), project_id (FK), date, minutes (int), days (decimal 4,2), description?, timestamps

### Enums (`app/Enums/`)

- `RoundingStrategy` : quarter, half_hour, hour
- `SessionSource` : manual, auto, reconstructed
- `ActivityEventType` : git_commit, file_change, claude_session_start, claude_session_end, claude_file_touch
- `TimeEntrySource` : session, manual, reconstructed
- `CraStatus` : draft, finalized, sent

---

## Architecture générique des sources d'activité

### Interface `ActivitySource`

```php
// app/Contracts/ActivitySource.php
interface ActivitySource
{
    /** Identifiant unique de la source (ex: 'git', 'claude_code', 'filesystem') */
    public function identifier(): string;

    /** Scanne l'activité depuis $since et retourne des ActivityEvent non persistés */
    public function scan(CarbonImmutable $since): Collection;

    /** Indique si cette source est disponible/configurée */
    public function isAvailable(): bool;
}
```

### Implémentations

- `GitActivitySource` (`app/Services/ActivitySources/GitActivitySource.php`) — parse `git log` des repos configurés, filtre par email auteur
- `ClaudeCodeActivitySource` (`app/Services/ActivitySources/ClaudeCodeActivitySource.php`) — parse JSON dans `~/.claude/projects/`, match au projet via le chemin
- `FilesystemActivitySource` (`app/Services/ActivitySources/FilesystemActivitySource.php`) — reçoit les events fswatch en temps réel, les transforme en `ActivityEvent`

### Orchestration

```php
// app/Actions/Activity/ScanAllSources.php
class ScanAllSources extends Action
{
    public function handle(CarbonImmutable $since): Collection
    {
        return collect($this->sources)
            ->filter(fn (ActivitySource $source) => $source->isAvailable())
            ->flatMap(fn (ActivitySource $source) => $source->scan($since))
            ->each(fn (ActivityEvent $event) => $this->recordEvent->handle($event));
    }
}
```

Les sources sont enregistrées via un ServiceProvider. Ajouter une nouvelle source = implémenter l'interface + l'enregistrer. Aucune duplication de la logique de
`RecordActivityEvent`.

---

## Actions par domaine

### Clients (`app/Actions/Clients/`)

- `CreateClient`, `UpdateClient`, `DeleteClient`

### Projects (`app/Actions/Projects/`)

- `CreateProject`, `UpdateProject`, `DeleteProject`
- `AttachRepository`, `DetachRepository`, `ValidateRepositoryPath`

### Sessions (`app/Actions/Sessions/`)

- `StartSession`, `StopSession`, `SwitchSessionProject`
- `UpdateSession`, `DeleteSession`

### Activity (`app/Actions/Activity/`)

- `RecordActivityEvent` — point unique d'enregistrement d'un event
- `ScanAllSources` — orchestre le scan de toutes les sources via l'interface
- `DetectActiveProject` — match un event à un projet via les chemins de repos
- `CheckIdleStatus` — calcule la durée d'inactivité depuis le dernier event

### Monitoring (`app/Actions/Monitoring/`)

- `StartActivityMonitor`, `StopActivityMonitor` — gère le process fswatch
- `ProcessFileChangeEvent` — reçoit un event fswatch, crée un ActivityEvent
- `HandleActivityDetected` — notifie si pas de session active
- `HandleIdleTimeout` — notification + auto-stop

### TimeEntries (`app/Actions/TimeEntries/`)

- `CreateTimeEntry`, `UpdateTimeEntry`, `DeleteTimeEntry`
- `GenerateTimeEntriesFromSession`, `ReconstructDayEntries`, `RoundMinutes`

### CRA (`app/Actions/Cra/`) — Phase 5

- `GenerateCraReport`, `RegenerateCraReport`, `FinalizeCraReport`
- `GenerateCraPdf`, `ExportCraExcel`

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
Resource cra-reports                → CraReportController (Phase 5)
GET  settings                       → SettingsController@edit
PUT  settings                       → SettingsController@update

API (menu bar) :
GET api/active-session, GET api/today-summary, GET api/projects/active
```

---

## NativePHP Integration

- **Menu bar** : `MenuBar::create()` avec icône idle/active, context menu dynamique via `MenuBarService`
- **Shortcuts** : `Cmd+Shift+T` toggle session, `Cmd+Shift+S` switch projet
- **fswatch** : `ChildProcess::start(['fswatch', '-r', ...paths])` avec listener `MessageReceived`
- **Notifications** : activité détectée, idle warning, auto-stop, rappel CRA
- **Scheduler** : scan sources (5min), idle check (1min), menu bar update (1min)

---

## Pages Vue (`resources/js/pages/`)

```
Dashboard.vue
Clients/    Index, Create, Show, Edit
Projects/   Show, Create, Edit
Sessions/   Index, Show
Timeline/   Index, DayDetail
TimeEntries/ Index, Create
CraReports/ Index, Show, Create (Phase 5)
Settings/   Edit
```

---

## Roadmap

### Phase 1 — Foundation (modèles, CRUD, layout)

1. Supprimer table `sessions` et `password_reset_tokens` de la migration Laravel
2. Toutes les migrations domaine (sauf cra_reports/cra_report_lines → Phase 5)
3. Modèles avec relations, factories, seeders
4. Enums
5. DTOs dans `app/Data/`
6. API Resources
7. Actions + Controllers + FormRequests pour Clients et Projects (avec repos)
8. `AppLayout.vue` avec sidebar navigation
9. Pages Clients (Index, Create, Show, Edit)
10. Pages Projects (Show, Create, Edit) + gestion repos
11. Page Settings basique
12. Seeder avec données exemples
13. Tests feature pour tout le CRUD

### Phase 2 — Menu bar & sessions

1. `NativeAppServiceProvider` configuré (menu bar, icônes, shortcuts)
2. `MenuBarService` pour menu contextuel dynamique
3. Actions Session : `StartSession`, `StopSession`, `SwitchSessionProject`
4. Controllers et routes sessions
5. `ActiveSessionBanner.vue`, `SessionTimer.vue`, `StartSessionDialog.vue`
6. Pages Sessions (Index, Show)
7. États menu bar : idle vs active avec timer
8. Events Laravel : `SessionStarted`, `SessionStopped`
9. Tests sessions

### Phase 3 — Détection d'activité (architecture générique)

1. Interface `ActivitySource` dans `app/Contracts/`
2. Implémentations : `GitActivitySource`, `ClaudeCodeActivitySource`, `FilesystemActivitySource`
3. Enregistrement des sources dans un ServiceProvider
4. `ScanAllSources` — orchestre le scan via l'interface
5. `RecordActivityEvent` — point unique de persistence
6. `DetectActiveProject` — match event → projet
7. `CheckIdleStatus`, `HandleActivityDetected`, `HandleIdleTimeout`
8. fswatch via `ChildProcess` + `ProcessFileChangeEvent`
9. Jobs schedulés : `ScanActivitySourcesJob`, `CheckIdleStatusJob`
10. Notifications NativePHP
11. Tests pour chaque source + orchestration

### Phase 4 — Timeline, time entries & reconstruction

1. `GenerateTimeEntriesFromSession`, `RoundMinutes`
2. `ReconstructDayEntries` — algorithme (gap grouping, padding, arrondi)
3. CRUD TimeEntry
4. `MonthCalendar.vue`, `DayDetail.vue`
5. Time entries éditables inline
6. Entrées manuelles (réunions, appels)
7. `Dashboard.vue` avec résumé du jour
8. `EndOfDayReconstructionJob`
9. Tests rounding, reconstruction

### Phase 5 — Génération CRA & PDF

1. Migrations cra_reports, cra_report_lines
2. Actions CRA
3. Package PDF
4. Template Blade PDF
5. Pages CRA
6. Export Excel/CSV
7. Tests génération

### Phase 6 — Polish

1. Onboarding (premier lancement)
2. Backup/restore SQLite
3. Deferred props + skeletons
4. Gestion erreurs (repo manquant, fswatch absent)
5. Auto-updater, icône app

---

## Vérification

Chaque phase : `composer test:all` (Pint, Rector, PHPStan, Pest) + test manuel via `php artisan native:serve` + `list-routes` + `database-schema`.
