# Amber — Remaining Roadmap

macOS activity tracker that automatically tracks development work and generates monthly reports (CRA). Built on Laravel 12, NativePHP, Inertia/Vue 3, Tailwind v4.

---

## Future

- **SQLite backup/restore** — manual export/import of the database
- **Auto-updater**
- **OAuth Activity Sources** — extend the existing `ActivitySource` pattern with an OAuth connection step (one-time auth, token stored locally/encrypted), then sync via API using
  the same interface as current sources. Target apps:
    - **Slack** — sent messages, active channels
    - **Linear** — assigned issues, status changes, comments
    - **Notion** — edited pages
    - **Jira** — issues and transitions
    - **Zoom** — past meetings
    - **Microsoft Teams** — past meetings
    - **Figma** — edited files, comments
    - **Google Calendar** — past events (Linux + macOS fallback)
    - **Harvest / Toggl** — import from existing timers
    - OAuth callback via custom URL scheme (`amber://oauth/callback`) or temporary local HTTP server
