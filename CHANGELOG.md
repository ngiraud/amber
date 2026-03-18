# Changelog

All notable changes to Amber will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Rich text editor (Tiptap) for session notes — available on session detail page, log past session sheet, live session sheet, and the title bar note dialog
- Session notes dialog with full-height editor, auto-focus, and discard protection (blocks accidental close)
- OS-aware installation instructions per activity source
- Timeline month stats, week breakdown, and stat component refactor
- Command palette and native menu hotkeys
- Quick note field on active session title bar
- Native folder picker for repository and source paths
- Reconstruct dialog button on dashboard

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

---

## [0.5.0] — Activity Reports & AI Summarization

### Added
- Activity Reports (CRA) with LLM-powered summarization
- Multi-provider AI support: Mistral, OpenAI, Anthropic, Ollama
- PDF/Excel export for activity reports
- Report finalization and status tracking (draft / finalized / sent)

---

## [0.4.5] — Enriched Activity Context

### Added
- Git branch and diff stats on activity events
- GitHub PR association via `gh` CLI
- Claude Code prompt context in activity events
- Opencode AI activity source

---

## [0.4.0] — Timeline & Reconstruction

### Added
- Timeline view for reviewing and editing time entries
- ReconstructDailySessions and ReconstructSessionsFromDate actions
- Time entries with rounding and validation
- Settings > Reset Database with confirmation dialog
- Settings > Sources with centralized sync management (per-source progress)
- Onboarding checklist on Dashboard with spotlight mechanism
- Deep link support (`amber://`) to start/stop sessions from Raycast or external tools

---

## [0.3.0] — Activity Sources

### Added
- Git activity source (git log with diff stats, branch switches)
- GitHub activity source (pull requests via `gh` CLI)
- Claude Code activity source (parses `~/.claude/projects/` JSONL files)
- Filesystem activity source (fswatch file change events)
- Gemini AI activity source
- Mistral Vibe activity source

---

## [0.2.0] — Sessions & Menu Bar

### Added
- Session management: start, stop, switch project
- macOS menu bar integration
- Session notes
- Session validation workflow

---

## [0.1.0] — Foundation

### Added
- Clients CRUD
- Projects CRUD with color, TJM, hourly rate, daily reference hours, rounding
- Project repositories management
- App layout with sidebar navigation
- NativePHP + Electron desktop app scaffold
- SQLite embedded database
