# Reset Database Feature

## Goal

Add a "Reset all data" action in Settings > General that runs `migrate:fresh` and redirects to the dashboard, protected by a confirmation modal requiring the user to type "RESET".

## Decisions

- No seed after reset (clean empty DB)
- Redirect to dashboard (`home` route) after reset
- Double confirmation: AlertDialog + text input requiring "RESET"
- Placed as a "Danger Zone" section at the bottom of Settings > General (no new tab)

## Steps

- [x] Create `ResetDatabaseController` (invokable) — runs `Artisan::call('migrate:fresh')`, redirects to `home`
- [x] Add POST route `settings.reset` in `routes/web.php`
- [x] Regenerate Wayfinder types (`php artisan wayfinder:generate`)
- [x] Add Danger Zone section to `resources/js/pages/settings/General.vue`
  - AlertDialog with text confirmation input (must type "RESET")
  - Destructive button variant
  - Calls `router.post(settingsRoutes.reset().url)` on confirm
- [ ] Run `vendor/bin/pint --dirty --format agent`
- [ ] Run `composer test:all`

## Files

- `app/Http/Controllers/Settings/ResetDatabaseController.php`
- `routes/web.php`
- `resources/js/routes/settings/index.ts` (auto-generated)
- `resources/js/pages/settings/General.vue`
