# Phase 1 — Foundation (Models, CRUD, Layout)

## Context

The CRA Tracker desktop app (NativePHP + Laravel 12 + Inertia/Vue 3) is a clean scaffold with no domain code. Phase 1 builds the data layer, client/project CRUD, app layout, and
basic settings — the foundation for all subsequent phases.

---

## Step 1: Clean existing migration + Create enums

**Modify** `database/migrations/0001_01_01_000000_create_users_table.php`

- Remove `password_reset_tokens` and `sessions` (HTTP) table blocks

**Create enums** in `app/Enums/` (5 files):

- `RoundingStrategy` — Quarter, HalfHour, Hour (Backed int)
- `SessionSource` — Manual, Auto, Reconstructed (Backed int)
- `ActivityEventType` — GitCommit, FileChange, ClaudeSessionStart, ClaudeSessionEnd, ClaudeFileTouch (Backed string)
- `TimeEntrySource` — Session, Manual, Reconstructed (Backed int)
- `CraStatus` — Draft, Finalized, Sent (Backed int)

All `enum Foo: string` with TitleCase keys, `declare(strict_types=1)`.

## Step 2: Domain migrations

Use `php artisan make:migration` for each (7 tables):

- `create_clients_table` — ulid PK, name, address?, contacts?, notes?, timestamps
- `create_projects_table` — ulid PK, client_id FK cascade, name, color(7), tjm?, hourly_rate?, daily_reference_hours(default 7), rounding(default 'quarter'), is_active(default
  true), timestamps, index(client_id, is_active)
- `create_project_repositories_table` — ulid PK, project_id FK cascade, local_path(unique), name, timestamps
- `create_sessions_table` — ulid PK, project_id FK cascade, started_at, ended_at?, duration_minutes?, source, notes?, is_validated(default false), timestamps, indexes
- `create_activity_events_table` — ulid PK, project_id? FK cascade, project_repository_id? FK, session_id? FK set null, source_type, type, occurred_at, metadata(json), timestamps,
  indexes
- `create_time_entries_table` — ulid PK, session_id? FK cascade, project_id FK cascade, date, started_at, ended_at, raw_minutes, rounded_minutes, source, description?,
  is_validated(default true), timestamps, index(project_id, date)
- `create_app_settings_table` — auto-increment id, key(unique), value(json), timestamps

Store enum values as strings (SQLite compatibility).

## Step 3: Models + Factories

**Fix** `app/Models/User.php` — remove `$fillable` property

**Create models** (use `php artisan make:model -f`):

| Model             | Traits               | Key Relations                                                                    | Casts                                                                    |
|-------------------|----------------------|----------------------------------------------------------------------------------|--------------------------------------------------------------------------|
| Client            | HasFactory, HasUlids | hasMany(Project)                                                                 | —                                                                        |
| Project           | HasFactory, HasUlids | belongsTo(Client), hasMany(ProjectRepository, Session, ActivityEvent, TimeEntry) | rounding→RoundingStrategy, is_active→boolean, decimals                   |
| ProjectRepository | HasFactory, HasUlids | belongsTo(Project), hasMany(ActivityEvent)                                       | —                                                                        |
| Session           | HasFactory, HasUlids | belongsTo(Project), hasMany(ActivityEvent, TimeEntry)                            | started_at/ended_at→datetime, source→SessionSource, is_validated→boolean |
| ActivityEvent     | HasFactory, HasUlids | belongsTo(Project, ProjectRepository, Session)                                   | type→ActivityEventType, occurred_at→datetime, metadata→array             |
| TimeEntry         | HasFactory, HasUlids | belongsTo(Session, Project)                                                      | date→date, datetimes, source→TimeEntrySource, is_validated→boolean       |
| AppSetting        | HasFactory (no ULID) | —                                                                                | value→json. Static `get(key, default)` / `set(key, value)` helpers       |

Project gets `#[Scope] protected function active(Builder $query)`.

**Factories** — use `fake()` (not `$this->faker`), matching existing UserFactory convention. Each factory has sensible defaults + useful states (e.g. Project::inactive(), Session::
completed(), Session::validated()).

## Step 4: DTOs + API Resources

**DTOs** in `app/Data/`:

- `ClientData` — readonly constructor properties, static `fromRequest()` method
- `ProjectData` — same pattern

**API Resources** in `app/Http/Resources/` (6 files):

- ClientResource, ProjectResource, ProjectRepositoryResource, SessionResource, ActivityEventResource, TimeEntryResource
- Use `whenLoaded()` for relations, `whenCounted()` for counts
- Always `Resource::make()` in controllers

## Step 5: Client CRUD (Actions + Controller + FormRequests)

**Actions** in `app/Actions/Clients/`:

- `CreateClient` — `handle(ClientData): Client`
- `UpdateClient` — `handle(Client, ClientData): Client`
- `DeleteClient` — `handle(Client): void`

**FormRequests** in `app/Http/Requests/`:

- `StoreClientRequest`, `UpdateClientRequest` — array syntax rules

**Controller** `app/Http/Controllers/ClientController.php`:

- index (list with project count), create, store, show (eager load projects.repositories), edit, update, destroy
- Inject actions via method DI, use ClientData::fromRequest()
- Use `Inertia::flash()` for notifications

## Step 6: Project + Repository CRUD

**Actions** in `app/Actions/Projects/`:

- `CreateProject` — `handle(Client, ProjectData): Project`
- `UpdateProject` — `handle(Project, ProjectData): Project`
- `DeleteProject` — `handle(Project): void`
- `AttachRepository` — `handle(Project, string localPath, string name): ProjectRepository`
- `DetachRepository` — `handle(ProjectRepository): void`
- `ValidateRepositoryPath` — `handle(string path): bool` (checks .git exists)

**FormRequests**: `StoreProjectRequest`, `UpdateProjectRequest`, `StoreProjectRepositoryRequest`

**Controllers**:

- `ProjectController` — store (nested under client), show, edit, update, destroy
- `ProjectRepositoryController` — store (nested under project), destroy

## Step 7: Settings

**Actions**: `GetSettings`, `UpdateSettings` in `app/Actions/Settings/`
**FormRequest**: `UpdateSettingsRequest`
**Controller**: `SettingsController` — edit, update

Phase 1 settings: git_author_email, company_name, company_address, default_tjm, default_daily_reference_hours, default_rounding_strategy

## Step 8: Routes + Dashboard

**`routes/web.php`** — replace current content:

```
GET  /                        → DashboardController (invokable, redirects to clients.index)
Resource clients              → ClientController
Resource projects             → ProjectController
POST projects/{project}/repositories → ProjectRepositoryController@store
DELETE repositories/{repository}     → ProjectRepositoryController@destroy
GET  settings                 → SettingsController@edit
PUT  settings                 → SettingsController@update
```

Create `DashboardController` as invokable — redirects to `clients.index` for now.

## Step 9: HandleInertiaRequests update

**Modify** `app/Http/Middleware/HandleInertiaRequests.php`:

- Remove `auth.user` sharing (desktop app, no auth)
- Add flash message sharing: `session('success')`, `session('error')`

## Step 10: Vue Layout + Components + Pages

**Layout** `resources/js/layouts/AppLayout.vue`:

- Sidebar (~250px) with app name, nav links (Dashboard, Clients, Settings), Inertia `<Link>`
- Main content area with slot
- `defineProps<{ title?: string }>()`

**Shared components** in `resources/js/components/`:

- `AppSidebar.vue` — extracted navigation
- `FormSection.vue` — form section wrapper
- `InputField.vue` — label + input + error
- `ColorPicker.vue` — hex color input with preview
- `Badge.vue` — status badge
- `ConfirmDialog.vue` — confirmation modal
- `FlashMessage.vue` — toast for flash messages

**TypeScript types** — update `resources/js/types/` with domain interfaces (Client, Project, ProjectRepository, etc.)

**Pages** (all using AppLayout):

- `Clients/Index.vue` — table/cards with project count, "New Client" button
- `Clients/Create.vue` — form with `useForm`, submit via Wayfinder action
- `Clients/Show.vue` — client details + project list as cards + "Add Project" button
- `Clients/Edit.vue` — same form as Create, pre-filled, delete button
- `Projects/Create.vue` — form: name, color picker, tjm, hourly_rate, daily_reference_hours, rounding select, is_active toggle
- `Projects/Show.vue` — project details + repository list with add/remove
- `Projects/Edit.vue` — same form as Create, pre-filled, delete button
- `Settings/Edit.vue` — settings form

## Step 11: Seeder

**`database/seeders/DatabaseSeeder.php`**:

- 2 Clients (e.g., "Acme Corp", "Startup Inc")
- 3 Projects across them with different colors/rates
- 2 repositories per project
- A few sessions (some completed)
- Activity events and time entries
- Default app_settings

## Step 12: Tests

**Feature tests** (by domain folder, named by action):

- `tests/Feature/Client/` — CreateClientTest, UpdateClientTest, DeleteClientTest
- `tests/Feature/Project/` — CreateProjectTest, UpdateProjectTest, DeleteProjectTest, ManageRepositoriesTest
- `tests/Feature/Settings/` — UpdateSettingsTest

Each: `pest()->group('controllers', '<domain>')`, happy path + validation + action delegation via `Action::fake()`

**Unit tests** (action logic):

- `tests/Unit/Actions/Clients/` — CreateClientTest, UpdateClientTest, DeleteClientTest
- `tests/Unit/Actions/Projects/` — CreateProjectTest, UpdateProjectTest, DeleteProjectTest, AttachRepositoryTest, DetachRepositoryTest
- `tests/Unit/Models/` — ClientTest, ProjectTest (relationships, scopes, casts)

## Step 13: Verification

1. `vendor/bin/pint --dirty --format agent`
2. `composer test:all` (lint + phpstan + pest)
3. `php artisan migrate:fresh --seed`
4. Verify routes with `list-routes` tool

---

## Implementation Order

Steps 1–4 (data layer) → Steps 5–8 (backend CRUD + routes) → Step 9 (middleware) → Step 10 (frontend) → Step 11 (seeder) → Step 12 (tests) → Step 13 (verify)
