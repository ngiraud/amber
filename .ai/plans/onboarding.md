# Onboarding — In-Context Guidance

## Context

L'app est fonctionnelle mais un nouveau utilisateur arrive sur un Dashboard vide sans guidance. Le plan précédent (wizard multi-étapes sur une page `/onboarding` dédiée) est rejeté. L'approche retenue : guider l'utilisateur **sur les vraies pages** avec une checklist sur le Dashboard et un mécanisme de spotlight pour attirer l'attention sur les bons éléments.

---

## Concept

3 mécanismes complémentaires :

1. **Dashboard checklist** — Widget proéminent avec les étapes de setup, chaque étape linkant vers la bonne page
2. **Spotlight** — Quand on clique un item de la checklist, on navigue vers la page cible et un ring pulsant met en évidence l'élément pertinent (via query param `?spotlight=key`)
3. **Complétion data-driven** — Chaque étape est "complète" quand la donnée existe vraiment (pas de tracking de clics). Un seul flag user : `onboarding_dismissed`

### Étapes

| # | Label | Complété quand | URL cible | Spotlight |
|---|-------|----------------|-----------|-----------|
| 1 | Set up company info | `GeneralSettings::company_name` non vide | `/settings/general?spotlight=company` | Section Company |
| 2 | Create your first client | `Client::exists()` | `/clients?spotlight=new-client` | Bouton "New client" |
| 3 | Create a project with a repo | `Project::whereHas('repositories')->exists()` | `/projects?spotlight=new-project` | Bouton "New project" |
| 4 | Configure activity sources | Au moins une source activée et configurée | `/settings/sources?spotlight=sources` | Première source card |
| 5 | Configure AI summaries | `AiSettings::enabled` | `/settings/ai?spotlight=ai` | Card AI (optionnel) |
| 6 | Start your first session | `Session::exists()` | `/` (Dashboard) | Bouton "Start session" |

---

## Fichiers à créer

### Backend

| Fichier | Rôle |
|---------|------|
| `database/settings/2026_03_11_000000_add_onboarding_dismissed_to_general_settings.php` | Migration settings |
| `app/Actions/Onboarding/GetOnboardingState.php` | Calcule l'état de chaque étape depuis les vraies données |
| `app/Actions/Onboarding/DismissOnboarding.php` | Met `onboarding_dismissed = true` |
| `app/Http/Controllers/DismissOnboardingController.php` | Invokable, POST `/onboarding/dismiss` |

### Frontend

| Fichier | Rôle |
|---------|------|
| `resources/js/composables/useSpotlight.ts` | Lit le query param `spotlight`, expose `isSpotlightTarget(key)`, auto-clear après 5s |
| `resources/js/components/OnboardingChecklist.vue` | Card checklist avec progress bar, bouton dismiss, orchestre les StepItems |
| `resources/js/components/OnboardingStepItem.vue` | Ligne individuelle : icône status, label, description, lien vers la page cible |

### Tests

| Fichier | Rôle |
|---------|------|
| `tests/Feature/Onboarding/GetOnboardingStateTest.php` | Vérifie la logique de complétion |
| `tests/Feature/Onboarding/DismissOnboardingTest.php` | Vérifie le endpoint dismiss |

## Fichiers à modifier

| Fichier | Modification |
|---------|-------------|
| `app/Settings/GeneralSettings.php` | Ajouter `public bool $onboarding_dismissed;` |
| `routes/web.php` | Ajouter `POST /onboarding/dismiss` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Ajouter shared prop `onboarding` |
| `resources/css/app.css` | Ajouter animation `spotlight-active` (ring pulsant) |
| `resources/js/types/resources.ts` | Ajouter `OnboardingStep` et `OnboardingState` |
| `resources/js/pages/Dashboard.vue` | Render conditionnel du `OnboardingChecklist` |
| `resources/js/pages/settings/General.vue` | `useSpotlight()` + attribut sur section Company |
| `resources/js/pages/client/Index.vue` | Spotlight sur bouton "New client" |
| `resources/js/pages/project/Index.vue` | Spotlight sur bouton "New project" |
| `resources/js/pages/settings/Sources.vue` | Spotlight sur les source cards |
| `resources/js/pages/settings/Ai.vue` | Spotlight sur la card AI |
| `resources/js/pages/Dashboard.vue` | Spotlight sur bouton "Start session" (étape 6) |

---

## Phases d'implémentation

### Phase 1 — Backend
1. Migration settings `onboarding_dismissed`
2. Ajouter propriété dans `GeneralSettings.php`
3. `GetOnboardingState` action
4. `DismissOnboarding` action
5. `DismissOnboardingController` + route
6. Shared prop `onboarding` dans `HandleInertiaRequests`
7. `php artisan wayfinder:generate`

### Phase 2 — Spotlight
8. `useSpotlight.ts` composable
9. CSS `spotlight-active` dans `app.css`

### Phase 3 — Dashboard checklist
10. Types TS (`OnboardingStep`, `OnboardingState`)
11. `OnboardingStepItem.vue`
12. `OnboardingChecklist.vue`
13. Intégration dans `Dashboard.vue`

### Phase 4 — Spotlight sur les pages cibles
14. `settings/General.vue` — section Company
15. `client/Index.vue` — bouton New client
16. `project/Index.vue` — bouton New project
17. `settings/Sources.vue` — source cards
18. `settings/Ai.vue` — AI card
19. `Dashboard.vue` — bouton "Start session"

### Phase 5 — Tests
20. `GetOnboardingStateTest.php`
21. `DismissOnboardingTest.php`
22. `composer test:all`

---

## Design technique

**Spotlight = query param + CSS ring.** Un composable `useSpotlight()` lit `?spotlight=key` dans l'URL, expose `isSpotlightTarget(key): boolean`, et nettoie l'URL via `history.replaceState`. L'animation est un ring pulsant CSS (2-3 pulsations puis arrêt) — simple, non-bloquant, pas d'overlay.

**Shared prop, pas lazy.** Les queries sont toutes des `exists()` sur SQLite local — négligeable. L'avantage : la checklist se met à jour automatiquement après chaque action.

**Pas de middleware, pas de redirect.** L'onboarding ne bloque jamais. C'est juste un widget conditionnel sur le Dashboard.

---

## Vérification

1. Premier lancement : Dashboard affiche la checklist avec 0/6 complétées
2. Clic sur "Set up company info" → navigue vers Settings/General avec spotlight sur la section Company
3. Remplir company name → retour Dashboard → étape cochée
4. Même flow pour client, projet, sources, AI
5. Bouton "Dismiss" → checklist disparaît, ne revient pas
6. `php artisan test --compact --filter=Onboarding`
7. `composer test:all`
