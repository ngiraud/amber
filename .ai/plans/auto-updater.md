# Feature: Auto-Updater

## Context

Amber est une app NativePHP desktop. NativePHP expose un `AutoUpdater` facade avec `checkForUpdates()`, `downloadUpdate()`, `quitAndInstall()`, et diffuse des événements lifecycle
sur le channel `nativephp`. Le config `config/nativephp.php` est déjà configuré (provider `spaces`). Le scheduler NativePHP tourne bien (voir `routes/console.php` avec
`menubar:update-timer` chaque minute). On wire les routes, le controller, le check auto (boot + periodic), et le frontend (chip TitleBar + popover + card Settings).

---

## Flow

### Auto-check (silencieux)

- **Au boot** : `NativeAppServiceProvider::boot()` → `AutoUpdater::checkForUpdates()` (en production seulement)
- **Périodique** : commande `updates:check` schedulée `everyTwoHours()` — pas de doublon car le scheduler ne déclenche pas au T=0

### Update disponible

- Un **chip** apparaît dans la TitleBar (entre le spacer traffic lights et le bouton Cmd+K)
- Click → **popover** avec : version dispo, release notes, bouton "Download"
- Pendant le download → progress % dans le popover
- Quand prêt → bouton "Restart & Install"

### Manuel (Settings > General > Software Updates card)

- Bouton "Check for updates" → POST check
- Card affiche le statut inline

---

## State (`useUpdater.ts` composable — singleton module-level)

```ts
type UpdaterStatus = 'idle' | 'checking' | 'up-to-date' | 'available' | 'downloading' | 'ready' | 'error'

const status: Ref<UpdaterStatus>
const updateInfo: Ref<{ version, releaseDate, releaseName, releaseNotes } | null>
const downloadProgress: Ref<number>  // 0–100
const errorMessage: Ref<string | null>
```

Méthodes : `checkForUpdates()`, `downloadUpdate()`, `installUpdate()`, `dismiss()`

Le composable enregistre les listeners `useNativeEvent` pour tous les événements AutoUpdater et met à jour le state. **Pas de toast** — tout passe par le chip/popover et la card.

---

## Files to Create

### Backend

**`app/Http/Controllers/Settings/UpdateController.php`**

```php
public function check()    { AutoUpdater::checkForUpdates(); return back(); }
public function download() { AutoUpdater::downloadUpdate();  return back(); }
public function install()  { AutoUpdater::quitAndInstall();  return back(); }
```

**`app/Console/Commands/CheckForUpdatesCommand.php`** (commande `updates:check`)

```php
public function handle(): void { AutoUpdater::checkForUpdates(); }
```

Schedulée dans `routes/console.php` : `Schedule::command('updates:check')->everyTwoHours();`

### Frontend

**`resources/js/composables/useUpdater.ts`**
State réactif partagé (module-level) + listeners `useNativeEvent` pour tous les événements AutoUpdater + méthodes d'action via Wayfinder routes.

**`resources/js/components/UpdateChip.vue`**

- Visible seulement quand `status ∈ { available, downloading, ready }`
- Chip compact : icône ↑ + version (ex: "1.2.3")
- Ouvre un `Popover` (shadcn/ui) avec :
    - Titre contextuel : "Update available" / "Downloading…" / "Ready to install"
    - Release notes (si disponibles)
    - Progress bar (quand `downloading`)
    - Bouton CTA : "Download" / "Restart & Install"

---

## Files to Modify

**`routes/web.php`** (dans le groupe `settings`)

```php
Route::post('/updates/check',    [UpdateController::class, 'check'])->name('updates.check');
Route::post('/updates/download', [UpdateController::class, 'download'])->name('updates.download');
Route::post('/updates/install',  [UpdateController::class, 'install'])->name('updates.install');
```

**`routes/console.php`**

```php
Schedule::command('updates:check')->everyTwoHours();
```

**`app/Providers/NativeAppServiceProvider.php`** (`boot()`)

```php
if (app()->isProduction()) {
    AutoUpdater::checkForUpdates();
}
```

**`resources/js/composables/useNativeAppEvents.ts`**
Ajouter `useUpdater()` pour initialiser les event listeners globalement.

**`resources/js/components/TitleBar.vue`**
Insérer `<UpdateChip />` entre le spacer traffic lights et le bouton Cmd+K.

**`resources/js/pages/Settings/General.vue`**
Ajouter une card "Software Updates" (au-dessus du Danger Zone) :

- Version courante (prop `currentVersion` depuis le controller)
- Statut inline via `useUpdater()` : idle / checking / "Up to date" / version dispo + notes / progress bar / prêt
- Bouton "Check for updates" (disabled quand status actif)

**`app/Http/Controllers/Settings/GeneralSettingsController.php`** (`edit()`)
Passer `currentVersion: config('nativephp.version')` à la page Inertia.

---

## Verification

1. `vendor/bin/pint --dirty --format agent`
2. `php artisan test --compact`
3. En production build : vérifier le check au boot dans les logs
4. Simuler un event `UpdateAvailable` → vérifier chip TitleBar + popover
5. Simuler `DownloadProgress` + `UpdateDownloaded` → vérifier progression + bouton install
