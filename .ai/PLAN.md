# Amber — Remaining Roadmap

macOS activity tracker that automatically tracks development work and generates monthly reports (CRA). Built on Laravel 12, NativePHP, Inertia/Vue 3, Tailwind v4.

---

## Native macOS

1. **System notification for long sessions** — alert after N hours without a break (configurable in Settings)
2. **System notification when a background sync completes**

## Robustness

1. **Auto-sync on startup** — optional toggle in Settings > Sources, triggers a source scan when the app launches
2. **Error handling** — missing repo, fswatch not found: clear UI error messages instead of silent failures
3. **SQLite backup/restore** — manual export/import of the database
4. **Auto-updater**
