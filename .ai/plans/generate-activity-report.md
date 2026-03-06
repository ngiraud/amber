# Feature: Generate Activity Report

## Context

The app tracks developer activity (sessions, git commits, PRs, Claude Code prompts, file changes) across projects and clients. The goal is generating CRAs (Compte Rendu d'Activite) -- monthly reports per client showing what was done, how long, and the total amount. Reports are generated from Sessions, with structured descriptions built from ActivityEvent metadata. The generation runs as a background job with real-time progress via NativePHP broadcasting.

## Decisions

- **Data source**: Sessions grouped by date + project (no TimeEntries)
- **Formats**: PDF (via `spatie/laravel-pdf` with DOMPDF driver) + CSV
- **Descriptions**: Source-agnostic structured text (labels + details). Not editable individually -- regenerate to update. LLM enhancement planned later. Manual editing planned later.
- **Scope**: One report = one client + one month
- **Generation**: Background job with NativePHP broadcasting for progress (`ShouldBroadcastNow` on `Channel('nativephp')`)
- **Lines**: Generated, not individually editable for now

---

## Step 1: Database Layer

### 1a. Migration: `create_activity_reports_table`

```
php artisan make:migration create_activity_reports_table --no-interaction
```

Schema:
- `ulid('id')->primary()`
- `ulid('client_id')` + foreign key cascade
- `unsignedTinyInteger('month')` (1-12)
- `unsignedSmallInteger('year')`
- `unsignedTinyInteger('status')->default(ActivityReportStatus::Draft->value)`
- `unsignedInteger('total_minutes')->default(0)`
- `decimal('total_days', 6, 2)->default(0)`
- `unsignedBigInteger('total_amount_ht')->nullable()` (cents)
- `datetime('generated_at')->nullable()`
- `string('pdf_path')->nullable()`
- `string('csv_path')->nullable()`
- `text('notes')->nullable()`
- `timestamps()`
- `unique(['client_id', 'month', 'year'])`

### 1b. Migration: `create_activity_report_lines_table`

Schema:
- `ulid('id')->primary()`
- `ulid('activity_report_id')` + foreign key cascade
- `ulid('project_id')` + foreign key cascade
- `date('date')`
- `unsignedInteger('minutes')->default(0)`
- `decimal('days', 5, 2)->default(0)`
- `text('description')->nullable()`
- `timestamps()`
- `unique(['activity_report_id', 'project_id', 'date'])`

### 1c. Model: `ActivityReport`

File: `app/Models/ActivityReport.php`
- HasUlids, HasFactory
- Relations: `belongsTo(Client)`, `hasMany(ActivityReportLine)`
- Casts: `status => ActivityReportStatus`, `generated_at => datetime`
- Accessor for `totalAmountHt` (cents to euros, same pattern as Project)
- Scope: `#[Scope] forPeriod(Builder $query, int $month, int $year)`

### 1d. Model: `ActivityReportLine`

File: `app/Models/ActivityReportLine.php`
- HasUlids, HasFactory
- Relations: `belongsTo(ActivityReport)`, `belongsTo(Project)`
- Casts: `date => date`

### 1e. Add `activityReports(): HasMany` to `Client` model

### 1f. Factories

- `ActivityReportFactory` with states: `draft()`, `generating()`, `finalized()`, `sent()`
- `ActivityReportLineFactory`

### 1g. DTO: `ActivityReportData`

File: `app/Data/ActivityReportData.php`
- Properties: `clientId`, `month`, `year`, `notes`
- Static `fromArray()` method (follow existing DTO pattern in `app/Data/`)

### 1h. Enums

**Add `Generating = 5` to `ActivityReportStatus`**

Order: Generating(5) -> Draft(10) -> Finalized(20) -> Sent(30).

**New enum: `ActivityReportStep`**

File: `app/Enums/ActivityReportStep.php`

```php
enum ActivityReportStep: string
{
    case CollectingContext = 'collecting_context';
    case BuildingLines = 'building_lines';
    case GeneratingFiles = 'generating_files';
    case Completed = 'completed';
    case Failed = 'failed';
}
```

**New enum: `ActivityReportExportFormat`**

File: `app/Enums/ActivityReportExportFormat.php`

```php
enum ActivityReportExportFormat: string
{
    case Pdf = 'pdf';
    case Csv = 'csv';
}
```

---

## Step 2: Core Actions & Job

### 2a. DTO: `DayContext`

File: `app/Data/DayContext.php`

Source-agnostic structure. Each ActivitySource contributes labels and details without DayContext knowing about specific sources.

```php
class DayContext
{
    public function __construct(
        /** @var list<string> Short identifiers: branch names, PR titles, etc. */
        public readonly array $labels,
        /** @var list<string> Detailed items: commit messages, Claude prompts, etc. */
        public readonly array $details,
        public readonly int $filesChanged,
    ) {}
}
```

### 2b. `CollectDayContext`

File: `app/Actions/ActivityReport/CollectDayContext.php`

`handle(Project $project, CarbonImmutable $date): DayContext`

Queries ActivityEvents **linked to sessions** (`whereNotNull('session_id')`) for that project+date. Iterates over events, extracts labels and details from metadata generically (uses `ActivityEventType` to determine what goes where). Returns a `DayContext` DTO.

The mapping logic (event type -> label vs detail) lives in `ActivityEventType` itself via a method like `toContextParts(array $metadata): array{label: ?string, detail: ?string}`, keeping `CollectDayContext` source-agnostic.

### 2c. `BuildLineDescription`

File: `app/Actions/ActivityReport/BuildLineDescription.php`

`handle(DayContext $context): string`

Converts DayContext into formatted text. Deduplicates labels, joins details.
```
feat: add login page, fix: token refresh | feature/auth | PR #42: Authentication flow
```

### 2d. `GenerateActivityReport` (orchestrator action)

File: `app/Actions/ActivityReport/GenerateActivityReport.php`

`handle(ActivityReportData $data): ActivityReport`

1. Check existing: if Generating/Draft, delete + regenerate. If Finalized/Sent, throw `ActivityReportAlreadyFinalizedException`
2. Create ActivityReport with status `Generating`
3. Dispatch `GenerateActivityReportJob` with the report
4. Return the report (frontend will receive progress via NativePHP events)

### 2e. `GenerateActivityReportJob`

File: `app/Jobs/GenerateActivityReportJob.php`

Implements `ShouldQueue`. This is the main worker:

1. **Broadcast** `ActivityReportProgress` (step: `CollectingContext`)
2. Query Sessions for client's projects in month/year (`whereNotNull('ended_at')`)
3. Group by `[project_id, date]` (derive date from `started_at` if `date` is null)
4. **Broadcast** `ActivityReportProgress` (step: `BuildingLines`)
5. Per group: sum `rounded_minutes` (fallback `duration_minutes`), calc days via `project.daily_reference_hours`, call `CollectDayContext` + `BuildLineDescription`, create `ActivityReportLine`
6. Calc totals: `total_minutes`, `total_days`, `total_amount_ht` (per project: days * daily_rate in cents)
7. **Broadcast** `ActivityReportProgress` (step: `GeneratingFiles`)
8. Generate PDF via `ExportActivityReportPdf`
9. Generate CSV via `ExportActivityReportCsv`
10. Update report: status -> `Draft`, set `generated_at`
11. **Broadcast** `ActivityReportProgress` (step: `Completed`)

On failure: update status to Draft with empty lines, broadcast `Failed` step.

### 2f. `ActivityReportProgress` event

File: `app/Events/ActivityReportProgress.php`

Implements `ShouldBroadcastNow`, broadcasts on `new Channel('nativephp')`.

```php
class ActivityReportProgress implements ShouldBroadcastNow
{
    public function __construct(
        public readonly string $reportId,
        public readonly ActivityReportStep $step,
        public readonly ?string $message = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('nativephp')];
    }
}
```

### 2g. `RegenerateActivityReport`

File: `app/Actions/ActivityReport/RegenerateActivityReport.php`

Deletes lines + files of a Draft report, sets status to Generating, re-dispatches the job.

### 2h. `DeleteActivityReport`

File: `app/Actions/ActivityReport/DeleteActivityReport.php`

Deletes a Draft report + associated files. Throws if Finalized/Sent.

### 2i. Exception

File: `app/Exceptions/ActivityReportAlreadyFinalizedException.php`

---

## Step 3: Exports (PDF & CSV)

### 3a. Install dependencies

```bash
composer require spatie/laravel-pdf dompdf/dompdf
```

### 3b. Blade PDF template

File: `resources/views/pdf/activity-report.blade.php`

CRA layout with inline CSS (DOMPDF compatible):
- Header: consultant info (from settings), client name + address, period
- Table: Date | Project | Hours | Days | Description
- Footer: totals (hours, days, amount HT if daily_rate exists), signature line

### 3c. `ExportActivityReportPdf`

File: `app/Actions/ActivityReport/Exports/ExportActivityReportPdf.php`

```php
Pdf::view('pdf.activity-report', compact('report', 'client', 'lines'))
    ->format('a4')
    ->save($path);
```

Stores in `storage/app/private/reports/cra-{client_slug}-{year}-{month}.pdf`, updates `pdf_path`.

### 3d. `ExportActivityReportCsv`

File: `app/Actions/ActivityReport/Exports/ExportActivityReportCsv.php`

CSV columns: `Date,Project,Hours,Days,Description` + summary row. Stores similarly, updates `csv_path`.

---

## Step 4: Controllers, Routes, Resources, Form Requests

### 4a. API Resources

- `ActivityReportResource` -- client (whenLoaded), lines count, formatted amount, status, pdf_path, csv_path
- `ActivityReportLineResource` -- project (whenLoaded), formatted duration

### 4b. Form Requests

- `StoreActivityReportRequest` -- client_id (required, exists), month (required, 1-12), year (required, min:2020), notes (nullable)

### 4c. Controllers

- `ActivityReportController` -- index, create, show, store, destroy
- `RegenerateActivityReportController` (invokable) -- re-dispatches generation job
- `ActivityReportExportController` (invokable) -- resolves format from route param via `ActivityReportExportFormat` enum, delegates to the right export action, returns download response

### 4d. Routes (`routes/web.php`)

```php
Route::resource('reports', ActivityReportController::class)->except(['edit', 'update']);
Route::post('reports/{report}/regenerate', RegenerateActivityReportController::class)->name('reports.regenerate');
Route::get('reports/{report}/{format}', ActivityReportExportController::class)->name('reports.export');
```

The `{format}` param is validated against `ActivityReportExportFormat` enum values (pdf, csv).

---

## Step 5: Frontend (Vue Pages)

### 5a. TypeScript types

Add `ActivityReport`, `ActivityReportLine`, `ActivityReportStatus`, `ActivityReportStep` to `resources/js/types/`

### 5b. NativePHP event listener composable

File: `resources/js/composables/useNativeEvent.ts`

```typescript
export function useNativeEvent<T>(eventClass: string, callback: (payload: T) => void) {
    onMounted(() => {
        window.addEventListener('native:init', () => {
            window.Native.on(eventClass, callback)
        })
    })
}
```

### 5c. `report/Index.vue`

- List reports as cards/rows: client, period, status badge (Generating=pulse animation, Draft=yellow, Finalized=blue, Sent=green), total days, amount
- Create button, empty state
- Listen to `ActivityReportProgress` events to update status in real-time

### 5d. `report/Create.vue`

Form: client select, month select (1-12), year input (default current), notes textarea. Uses `useForm`. On submit, redirects to Show page.

### 5e. `report/Show.vue`

- Header: client, period, status badge, totals card
- **Generating state**: progress indicator with current step (collecting context -> building lines -> generating files -> done)
- **Draft state**: table of lines sorted by date (Date | Project with color dot | Hours | Days | Description), download PDF/CSV buttons, regenerate button
- Notes section
- Listen to `ActivityReportProgress` via `useNativeEvent` for real-time updates

### 5f. Sidebar navigation

Add "Reports" link in sidebar nav.

---

## Future (not implemented now)

- **LLM Summarization**: `SummarizeDayActivity` action, AI settings, "Summarize with AI" button
- **Manual line editing**: Allow editing individual line descriptions/durations on the Show page

---

## Key Files to Reuse/Reference

- `app/Models/Session.php` -- data source, relationships, scopes
- `app/Models/Client.php` -- add HasMany relationship
- `app/Models/Project.php` -- daily_reference_hours, dailyRate accessor pattern (cents)
- `app/Enums/ActivityReportStatus.php` -- already exists (Draft=10, Finalized=20, Sent=30), add Generating=5
- `app/Enums/ActivityEventType.php` -- add `toContextParts()` method for source-agnostic context extraction
- `app/Data/SessionData.php` -- DTO pattern reference (fromArray)
- `app/Actions/Session/CreateSession.php` -- Action pattern reference
- `app/Http/Controllers/ClientController.php` -- Controller pattern reference
- `app/Http/Resources/SessionResource.php` -- Resource pattern reference
- `resources/js/pages/client/Index.vue` -- Vue page pattern reference
- `resources/js/pages/session/Show.vue` -- Detail page pattern reference

## Verification

1. `php artisan test --compact --filter=ActivityReport` -- all tests pass
2. `composer test:all` -- Pint, Rector, PHPStan, Pest all green
3. Manual test: create a report for a client with sessions, verify lines, download PDF/CSV
4. Verify NativePHP broadcasting works: progress events appear on Show page during generation
5. `database-schema` MCP tool to verify migrations
6. `list-routes` MCP tool to verify routes registered correctly
