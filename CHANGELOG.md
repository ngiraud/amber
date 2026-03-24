# Changelog

All notable changes to Amber will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Stats, tabs, and client notes on project and client detail pages — each now shows time tracked, billable amounts, recent sessions, and notes
- Context menu on report lines — right-click individual entries to perform quick actions directly from the report view

### Changed

- Session reconstruction is now automatic — sessions are rebuilt every few minutes in the background; no manual "Reconstruct" action needed during normal use
- Reconstruct actions moved to Settings > Activity with an explanatory note; removed from Dashboard and Timeline headers
- Activity backfill (when the app was closed) now auto-reconstructs sessions and shows a toast with event and session counts instead of prompting the user to reconstruct manually
- Manual sync from Settings > Sources now triggers a single reconstruction pass after all sources complete, preventing redundant rebuilds when syncing multiple sources in parallel
- Reports and sessions UI improvements: layout, spacing, and interaction polish across report and session views
- Enum options are now resolved on the backend and passed to the frontend, removing client-side enum logic

### Fixed

- AI report summarization now limits context size and batches requests to prevent token overflow on large datasets
- Release notes are now read directly from `CHANGELOG.md` instead of the GitHub tag API
- PHPStan errors in ViewModels resolved by using `toArray()` for raw query results

---

## [0.5.0] — 2026-03-22

### Added

- Screen lock/wake handling: active manual sessions are automatically stopped when the screen locks, preventing idle time from being billed during sleep or absence
- fswatch is restarted on screen unlock to ensure file activity is tracked correctly after wake
- `php artisan release` now creates annotated Git tags and populates GitHub release notes from the corresponding `CHANGELOG.md` section

---

## [0.4.0] — 2026-03-21

### Added

- Multi-language support (English, French, German) — all UI strings, flash messages, CSV headers, and enum labels are fully translated
- Locale selector in Settings > General — switching language updates the entire UI reactively without a page reload
- Frontend translation composable (`useTranslation`) reads from server-injected `window.__translations`; supports named replacements and pipe-based pluralisation
- Locale-aware month names and weekday labels throughout the app (Timeline calendar, report month picker, date labels) using `Intl.DateTimeFormat`

### Changed

- Clicking "Reconstruct sessions" in the activity backfill toast now opens the reconstruction dialog directly instead of navigating to the Timeline page first

---

## [0.3.0] — 2026-03-20

### Added

- Date format preference in Settings > General — choose between DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD, or DD Mon YYYY; applied everywhere dates are displayed
- Time format preference in Settings > General — toggle between 24h (14:30) and 12h (02:30 PM); applied everywhere times are displayed
- "Sync past activity" step in onboarding checklist with spotlight on the Sources page sync button

### Fixed

- Onboarding create sheet no longer auto-opens when navigating to Clients or Projects pages after spotlight interaction

---

## [0.2.0] — 2026-03-20

### Added

- `php artisan release` command — interactive release wizard with preflight checks (branch, clean tree, sync with origin), current state display (commits since last tag), full test suite run, patch/minor/major version selection, CHANGELOG update, and tag creation/push

---

## [0.1.0] — 2026-03-20 — Initial Public Release

### Added

- Auto-updater (when `nativephp.updater.enabled` is true): version chip in title bar shows current version and update state (checking, downloading, ready) with a popover for
  release notes and install action
- Software Updates card in Settings > General with inline status, download progress bar, and "Restart & Install" button when auto-updater is enabled
- Manual GitHub release check (when auto-updater is disabled): "Check for updates" button in title bar popover and Settings > General fetches the latest GitHub release and links to
  it
- "Check for updates" command in the command palette — delegates to auto-updater or GitHub check based on config
- Automatic update check on app launch (production only) and every 2 hours via scheduler (skipped when auto-updater is disabled)
- `EnsureUpdaterEnabled` middleware guards auto-updater routes and returns 403 when the updater is disabled
- Deep link support extended: `session/toggle`, `session/switch`, `navigate/{page}` (dashboard, timeline, reports, clients, projects, sessions, activity, settings), and
  `activity/sync` (scans all sources with start/completion native notifications)
- Rich text editor (Tiptap) for session notes — available on session detail page, log past session sheet, live session sheet, and the title bar note dialog
- Session notes dialog with full-height editor, auto-focus, and discard protection (blocks accidental close)
- Rich text editor: headings (H1–H3), task lists, code blocks with syntax highlighting, links, typography, tables with row/column controls, and YouTube embed
- Activity Reports (CRA) with LLM-powered summarization
- Multi-provider AI support: Mistral, OpenAI, Anthropic, Ollama
- PDF/Excel export for activity reports
- Report finalization and status tracking (draft / finalized / sent)
- Git branch and diff stats on activity events
- GitHub PR association via `gh` CLI
- Claude Code prompt context in activity events
- Opencode AI activity source
- Timeline view for reviewing and editing time entries
- ReconstructDailySessions and ReconstructSessionsFromDate actions
- Time entries with rounding and validation
- Settings > Reset Database with confirmation dialog
- Settings > Sources with centralized sync management (per-source progress)
- Onboarding checklist on Dashboard with spotlight mechanism
- Deep link support (`amber://`) to start/stop sessions from Raycast or external tools
- Git activity source (git log with diff stats, branch switches)
- GitHub activity source (pull requests via `gh` CLI)
- Claude Code activity source (parses `~/.claude/projects/` JSONL files)
- Filesystem activity source (fswatch file change events)
- Gemini AI activity source
- Mistral Vibe activity source
- Session management: start, stop, switch project
- macOS menu bar integration
- Session notes
- Session validation workflow
- OS-aware installation instructions per activity source
- Timeline month stats, week breakdown, and stat component refactor
- Command palette and native menu hotkeys
- Quick note field on active session title bar
- Native folder picker for repository and source paths
- Reconstruct dialog button on dashboard
- Clients CRUD
- Projects CRUD with color, TJM, hourly rate, daily reference hours, rounding
- Project repositories management
- App layout with sidebar navigation
- NativePHP + Electron desktop app scaffold
- SQLite embedded database

### Changed

- Removed `scan_interval_minutes` setting — scan window is now hardcoded (3 min) since the cron schedule is fixed
- Replaced native date/time inputs with calendar popover pickers (DatePicker and DateTimePicker components)
- Replaced Add Session buttons with Log Session using LogPastSessionSheet
- Moved session actions into TitleBar with split sheets and create dropdown
- Clients ordered alphabetically by name
- Centralized active projects ordering via ActiveProjectsProps

### Fixed

- Report lines rendered by date descending
- AI config API key sync when needed
- Rounding and daily_reference_hours no longer mandatory
