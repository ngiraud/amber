# Onboarding Wizard

## Context

L'app Activity Record est fonctionnelle (clients, projets, sessions, timeline, reports, AI) mais l'utilisateur arrive directement sur un Dashboard vide sans aucune guidance. L'onboarding va guider les nouveaux utilisateurs à travers la configuration initiale pour qu'ils puissent commencer à tracker leur activité immédiatement.

**Format** : Wizard multi-étapes avec stepper visuel
**Comportement** : Obligatoire au 1er lancement, skippable, rejouable depuis Settings

---

## Architecture

### Décisions clés

1. **Flag `has_completed_onboarding`** ajouté à `GeneralSettings` (pas de nouveau settings group pour un seul booléen)
2. **Single page + child components** : Une page Inertia `/onboarding` orchestre les steps côté client. Chaque step submit indépendamment vers les routes existantes (réutilisation des Actions/FormRequests)
3. **Persistence par step** : Chaque step sauve ses données immédiatement (client créé = client persisté). Le flag onboarding n'est mis à `true` qu'à la fin
4. **Layout dédié** `OnboardingLayout.vue` sans sidebar (juste TitleBar + contenu centré)
5. **Middleware** `EnsureOnboardingCompleted` redirige vers `/onboarding` si le flag est `false`

### Steps du wizard

| # | Step | Données | Route backend | Skippable |
|---|------|---------|---------------|-----------|
| 0 | Welcome | — | — | Non |
| 1 | Company Info | company_name, company_address, timezone | `settings.general.update` | Oui |
| 2 | First Client | name, notes | `clients.store` | Oui |
| 3 | First Project + Repo | project name, color, client_id, repo path | `projects.store` + `projects.repositories.store` | Oui |
| 4 | AI Config | enabled, provider, api_key, summary_language | `settings.ai.update` | Oui |
| 5 | Activity Config | idle_timeout, reconstruction_gap | `settings.activity.update` | Oui |
| 6 | Completion | Résumé + "Go to Dashboard" | `onboarding.complete` | Non |

---

## Fichiers à créer

### Backend

| Fichier | Rôle |
|---------|------|
| `database/settings/2026_03_10_000000_add_onboarding_to_general_settings.php` | Migration : ajoute `general.has_completed_onboarding` (default: false) |
| `app/Http/Middleware/EnsureOnboardingCompleted.php` | Redirige vers `/onboarding` si pas complété, exclut les routes `onboarding.*` |
| `app/Http/Controllers/OnboardingController.php` | `index()` rend la page avec settings + données existantes, `complete()` et `skip()` marquent onboarding terminé |
| `app/Actions/Onboarding/CompleteOnboarding.php` | Action qui set `has_completed_onboarding = true` |

### Frontend

| Fichier | Rôle |
|---------|------|
| `resources/js/layouts/OnboardingLayout.vue` | Layout minimal : TitleBar + contenu centré + Toaster |
| `resources/js/pages/onboarding/Index.vue` | Page orchestratrice : gère `currentStep`, passe les props aux steps |
| `resources/js/components/onboarding/WizardStepper.vue` | Stepper visuel (cercles numérotés + lignes de connexion) |
| `resources/js/components/onboarding/WelcomeStep.vue` | Écran d'accueil avec CTA |
| `resources/js/components/onboarding/CompanyStep.vue` | Form infos entreprise (réutilise pattern settings/General) |
| `resources/js/components/onboarding/ClientStep.vue` | Form création client (réutilise pattern ClientSheet) |
| `resources/js/components/onboarding/ProjectStep.vue` | Form création projet + repo (réutilise pattern ProjectSheet + RepositorySheet) |
| `resources/js/components/onboarding/AiConfigStep.vue` | Form config AI (réutilise pattern settings/Ai) |
| `resources/js/components/onboarding/ActivityConfigStep.vue` | Form config activité (réutilise pattern settings/Activity) |
| `resources/js/components/onboarding/CompletionStep.vue` | Résumé + bouton "Go to Dashboard" |

### Tests

| Fichier | Rôle |
|---------|------|
| `tests/Feature/Onboarding/OnboardingRedirectTest.php` | Redirect si pas complété, pas de boucle, accès après complétion |
| `tests/Feature/Onboarding/CompleteOnboardingTest.php` | POST complete/skip met le flag, redirect vers `/` |

---

## Fichiers à modifier

| Fichier | Modification |
|---------|-------------|
| `app/Settings/GeneralSettings.php` | Ajouter `public bool $has_completed_onboarding;` |
| `bootstrap/app.php` | Enregistrer `EnsureOnboardingCompleted` middleware (append au web stack) |
| `routes/web.php` | Ajouter groupe de routes `onboarding.*` (index, complete, skip) |
| `app/Http/Middleware/HandleInertiaRequests.php` | Partager `has_completed_onboarding` dans les shared props |
| `resources/js/pages/settings/General.vue` | Ajouter bouton "Re-launch setup wizard" en bas de page |

---

## Ordre d'implémentation

### Phase 1 — Backend foundation
1. Settings migration + update `GeneralSettings.php` + run migration
2. `CompleteOnboarding` action
3. `OnboardingController` (index, complete, skip)
4. Routes dans `web.php`
5. `EnsureOnboardingCompleted` middleware + register dans `bootstrap/app.php`
6. Update `HandleInertiaRequests.php` shared props
7. `php artisan wayfinder:generate`

### Phase 2 — Frontend layout & stepper
8. `OnboardingLayout.vue`
9. `WizardStepper.vue`

### Phase 3 — Step components
10. `WelcomeStep.vue`
11. `CompanyStep.vue`
12. `ClientStep.vue`
13. `ProjectStep.vue` (le plus complexe : 2 submissions séquentielles)
14. `AiConfigStep.vue`
15. `ActivityConfigStep.vue`
16. `CompletionStep.vue`

### Phase 4 — Orchestration
17. `onboarding/Index.vue` (page principale qui orchestre les steps)

### Phase 5 — Settings re-launch
18. Ajouter bouton "Re-launch wizard" dans `settings/General.vue`

### Phase 6 — Tests & validation
19. Tests Feature (redirect, complete, skip)
20. `composer test:all`

---

## Vérification

1. **Premier lancement** : Vérifier que l'app redirige vers `/onboarding`
2. **Parcours complet** : Welcome → Company → Client → Project+Repo → AI → Activity → Done → Dashboard
3. **Skip** : Cliquer "Skip" → arrive sur Dashboard → pas de re-redirect
4. **Skip individuel** : Passer des steps intermédiaires (ex: pas de client) → le projet step gère l'absence de client
5. **Re-launch** : Settings → "Re-launch wizard" → le wizard s'ouvre avec données pré-remplies
6. **Persistence** : Quitter l'app au milieu du wizard → au relancement, redirect vers onboarding (flag toujours false), les données déjà créées sont présentes
7. **Tests** : `php artisan test --compact --filter=Onboarding`
8. **Suite complète** : `composer test:all`
