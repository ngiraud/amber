# Feature: Timeline — Phase 4

## Context

Phases 1–3 (foundation, sessions, activity detection) are complete. The app tracks sessions and activity events but has no way to view or manage **time entries** — the billable units of work. Phase 4 introduces the timeline UI (month calendar + day detail), time entry CRUD, automatic generation from sessions, rounding algorithms, day reconstruction, and a today-focused dashboard.

---

## Phased Implementation

### Phase 4A — Backend: Actions, CRUD, Rounding

**Files to create:**

| File | Purpose |
|------|---------|
| `app/Actions/TimeEntry/CreateTimeEntry.php` | Create a time entry (manual or from session) |
| `app/Actions/TimeEntry/UpdateTimeEntry.php` | Update an existing time entry |
| `app/Actions/TimeEntry/DeleteTimeEntry.php` | Delete a time entry |
| `app/Actions/TimeEntry/GenerateTimeEntryFromSession.php` | Convert a stopped session into a time entry |
| `app/Actions/TimeEntry/ReconstructDayEntries.php` | Analyse a day's sessions + activity, fill gaps, generate entries |
| `app/Actions/TimeEntry/RoundMinutes.php` | Apply rounding strategy (quarter/half/hour) to raw minutes |
| `app/Data/TimeEntryData.php` | DTO for time entry creation/update |
| `app/Http/Controllers/TimeEntryController.php` | CRUD controller (store, update, destroy) |
| `app/Http/Controllers/TimelineController.php` | Timeline index (month) + show (day detail) |
| `app/Http/Controllers/ReconstructTimeEntriesController.php` | Invokable — manual reconstruct trigger |
| `app/Http/Requests/TimeEntry/StoreTimeEntryRequest.php` | Validation for creating a time entry |
| `app/Http/Requests/TimeEntry/UpdateTimeEntryRequest.php` | Validation for updating a time entry |
| `tests/Feature/TimeEntry/CreateTimeEntryTest.php` | Tests for manual time entry creation |
| `tests/Feature/TimeEntry/UpdateTimeEntryTest.php` | Tests for updating time entries |
| `tests/Feature/TimeEntry/DeleteTimeEntryTest.php` | Tests for deleting time entries |
| `tests/Unit/TimeEntry/RoundMinutesTest.php` | Unit tests for all rounding strategies |
| `tests/Feature/TimeEntry/GenerateTimeEntryFromSessionTest.php` | Tests for session→time entry generation |
| `tests/Feature/TimeEntry/ReconstructDayEntriesTest.php` | Tests for day reconstruction algorithm |
| `tests/Feature/Timeline/ViewTimelineTest.php` | Tests for timeline pages |

**Files to modify:**

| File | Change |
|------|--------|
| `app/Actions/Session/StopSession.php` | After stopping, call `GenerateTimeEntryFromSession` |
| `app/Http/Resources/TimeEntryResource.php` | Add `project` and `session` relationships, formatted fields |
| `app/Models/TimeEntry.php` | Add `forDate` and `forProject` scopes if needed |
| `routes/web.php` | Add timeline + time-entry routes |
| `resources/js/types/resources.ts` | Add `TimeEntry` TypeScript type |

**RoundMinutes algorithm:**
- Input: raw minutes (int), RoundingStrategy enum
- Quarter (15): ceil to nearest 15 (e.g., 23 → 30, 15 → 15, 1 → 15)
- HalfHour (30): ceil to nearest 30 (e.g., 23 → 30, 31 → 60)
- Hour (60): ceil to nearest 60 (e.g., 23 → 60, 61 → 120)
- Formula: `(int) ceil($raw / $strategy->value) * $strategy->value`

**GenerateTimeEntryFromSession:**
- Takes a stopped Session
- Calculates raw_minutes from started_at → ended_at
- Applies project's rounding strategy via RoundMinutes
- Creates TimeEntry with source=Session, linked to session + project

**ReconstructDayEntries:**
- Takes a date + project (or all projects)
- Gathers all sessions for that day
- For each session without a time entry: generate one
- Optional: detect gaps between sessions using activity_events, create additional entries
- Round all entries using project rounding strategy

**Routes (per PLAN.md):**
```
GET    /timeline              → TimelineController@index   (month calendar)
GET    /timeline/{date}       → TimelineController@show    (day detail)
POST   /time-entries          → TimeEntryController@store  (create manual entry)
PATCH  /time-entries/{entry}  → TimeEntryController@update
DELETE /time-entries/{entry}  → TimeEntryController@destroy
POST   /time-entries/reconstruct → ReconstructTimeEntriesController (invokable)
```

### Phase 4B — Frontend: Timeline UI

**Files to create:**

| File | Purpose |
|------|---------|
| `resources/js/pages/timeline/Index.vue` | Month calendar view |
| `resources/js/pages/timeline/Show.vue` | Day detail with time entries |
| `resources/js/components/MonthCalendar.vue` | Calendar grid component |
| `resources/js/components/TimeEntryRow.vue` | Single time entry display (inline editable) |
| `resources/js/components/TimeEntrySheet.vue` | Sheet for creating/editing a manual time entry |

**Files to modify:**

| File | Change |
|------|--------|
| `resources/js/components/AppSidebar.vue` | Add "Timeline" nav item (CalendarDaysIcon) |
| `resources/js/types/resources.ts` | Add TimeEntry type |

**MonthCalendar.vue:**
- Grid of days for the selected month
- Each day cell shows: total rounded hours, color dots per project
- Click a day → navigate to `/timeline/{date}`
- Month/year navigation (prev/next arrows)
- Props: `year`, `month`, `days` (array of `{ date, entries, totalMinutes }`)
- Highlight today, dim future days, mark weekends

**timeline/Show.vue (Day Detail):**
- Shows all time entries for the selected date
- Grouped by project (with project color indicator)
- Each entry: time range, raw/rounded duration, source badge, description
- Inline edit (click to modify description, times)
- "Add manual entry" button → TimeEntrySheet
- "Reconstruct day" button → POST /time-entries/reconstruct
- Day total at the bottom

### Phase 4C — Dashboard

**Files to create:**

| File | Purpose |
|------|---------|
| `resources/js/pages/Dashboard.vue` | Today-focused dashboard |

**Files to modify:**

| File | Change |
|------|--------|
| `app/Http/Controllers/DashboardController.php` | Render Dashboard.vue with today's data instead of redirect |
| `resources/js/components/AppSidebar.vue` | Add "Dashboard" nav item (LayoutDashboardIcon), update home link |

**Dashboard.vue content:**
- Active session banner (already exists globally)
- Today's time entries list (reuse TimeEntryRow)
- Daily total: rounded minutes → hours/days
- Quick "Start session" button (reuse StartSessionDialog)
- Quick "Add manual entry" button
- Mini summary: this week's total, this month's total

### Phase 4D — EndOfDayReconstructionJob

**Files to create:**

| File | Purpose |
|------|---------|
| `app/Jobs/EndOfDayReconstructionJob.php` | Scheduled job to reconstruct yesterday's entries |
| `tests/Feature/TimeEntry/EndOfDayReconstructionJobTest.php` | Tests for the job |

**Files to modify:**

| File | Change |
|------|--------|
| `routes/console.php` | Schedule the job daily at midnight (or configurable) |

---

## Key Existing Files to Reuse

- `app/Actions/Action.php` — base class for all actions
- `app/Models/TimeEntry.php` — already exists with relationships + casts
- `app/Http/Resources/TimeEntryResource.php` — already exists, needs enhancement
- `database/factories/TimeEntryFactory.php` — already exists
- `app/Enums/TimeEntrySource.php` — Manual/Session/Reconstructed
- `app/Enums/RoundingStrategy.php` — Quarter(15)/HalfHour(30)/Hour(60)
- `app/Models/Project.php` — has `rounding` cast, `timeEntries()` relationship
- `app/Models/Session.php` — has `timeEntries()` relationship
- `app/Actions/Session/StopSession.php` — will be modified to auto-generate time entry
- `app/Http/Controllers/SessionController.php` — pattern reference
- `resources/js/components/PageHeader.vue` — reuse for all new pages
- `resources/js/components/StartSessionDialog.vue` — reuse on dashboard
- `resources/js/components/SessionTimer.vue` — reuse on dashboard

## Implementation Order

1. **RoundMinutes** action + unit tests (no dependencies)
2. **CreateTimeEntry / UpdateTimeEntry / DeleteTimeEntry** actions + DTO + tests
3. **GenerateTimeEntryFromSession** action + modify StopSession + tests
4. **ReconstructDayEntries** action + tests
5. **TimeEntryController + FormRequests + routes** + controller tests
6. **TimelineController** (index + show) + tests
7. **TypeScript types** (TimeEntry)
8. **MonthCalendar.vue + timeline/Index.vue** page
9. **TimeEntryRow.vue + TimeEntrySheet.vue + timeline/Show.vue** page
10. **Dashboard.vue + update DashboardController**
11. **AppSidebar** — add Timeline + Dashboard nav items
12. **EndOfDayReconstructionJob** + schedule + tests
13. **ReconstructTimeEntriesController** (manual trigger) + test
14. Run `composer test:all`, fix issues

## Verification

1. `php artisan test --compact` — all new + existing tests pass
2. `vendor/bin/pint --dirty --format agent` — code style
3. `composer test:all` — full suite (Pint, Rector, PHPStan, Pest)
4. Manual test: stop a session → verify time entry auto-created
5. Manual test: navigate to /timeline → verify month calendar renders
6. Manual test: click a day → verify day detail with entries
7. Manual test: add manual time entry, edit, delete
8. Manual test: / dashboard shows today's summary
