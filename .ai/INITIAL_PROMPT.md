# CRA Tracker — Plan Projet

## Vision

App desktop macOS (NativePHP + Electron) qui tracke automatiquement l'activité de développement locale via un système de sessions intelligentes, et génère des Comptes Rendus
d'Activité (CRA) mensuels prêts à envoyer aux clients.

L'app combine un tracking explicite (start/stop de session) avec une détection d'activité intelligente qui rattrape l'utilisateur quand il oublie de démarrer ou stopper une
session.

---

## Stack technique

| Couche          | Techno                                       |
|-----------------|----------------------------------------------|
| Framework       | Laravel 12                                   |
| Desktop         | NativePHP (Electron) v1.3+                   |
| Frontend        | Vue.js 3 + Inertia.js                        |
| CSS             | Tailwind CSS                                 |
| Base de données | SQLite (locale, embarquée)                   |
| PDF             | DomPDF ou Laravel Snappy                     |
| Scheduler       | Laravel native (via NativePHP)               |
| File watching   | fswatch (natif macOS, via process NativePHP) |

**Pourquoi SQLite ?** Parfait pour une app desktop locale — zéro config, un seul fichier, performant pour ce volume de données.

---

## Objectif de cette session

Je veux que tu m'aides à **planifier** ce projet avant de coder quoi que ce soit. On doit définir :

1. L'architecture globale du projet
2. La liste des features à implémenter
3. L'ordre d'implémentation (dépendances entre features)

---

## Fonctionnalités principales envisagées

### 1. Gestion des projets & clients

- Créer/éditer des clients (nom, adresse, contact)
- Créer/éditer des projets liés à un client
- Associer un ou plusieurs repos Git locaux à un projet ou associer les chemins des dossiers pour récupérer les repos Git locaux
- Configurer le TJM ou taux horaire, et le nombre d'heures par jour de référence
- Configurer l'arrondi (quart d'heure par défaut)

### 2. Système de sessions intelligentes

C'est le cœur de l'app. Le flow utilisateur :

#### Démarrage automatique (détection d'activité)

```
L'utilisateur commence à coder sans lancer de session
        ↓
L'ActivityMonitor détecte un event :
  - Modification de fichier dans un repo surveillé (fswatch)
  - Commit git
  - Session Claude Code qui démarre
        ↓
Notification macOS :
  "Activité détectée sur Projet X — Démarrer la session ?"
  [Démarrer] [Ignorer] [Autre projet]
        ↓
Si "Démarrer" → La session est créée avec started_at = timestamp du premier event détecté
(pas le moment du clic, pour ne pas perdre le temps avant la notification)
```

#### Démarrage manuel

- Depuis la menu bar : clic → choisir le projet → session démarrée
- Raccourci clavier global configurable

#### Pendant la session

- Menu bar : icône active + nom du projet + chrono (ex: "Projet X — 2h15")
- Switch de projet rapide depuis la menu bar (stop l'ancienne, start la nouvelle)
- L'ActivityMonitor continue de loguer les events et met à jour `last_activity_at`

#### Arrêt intelligent (détection d'inactivité)

```
Aucune activité filesystem/git/claude détectée depuis X minutes (défaut: 20 min)
        ↓
Notification macOS :
  "Toujours sur Projet X ? Inactif depuis 20 min."
  [Continuer] [Stopper]
        ↓
Si pas de réponse après Y minutes supplémentaires (défaut: 10 min)
        ↓
Auto-stop : ended_at = timestamp du dernier event détecté
(on tronque le temps d'inactivité, on ne le facture pas)
```

#### Arrêt manuel

- Menu bar : clic → "Stopper la session"
- Raccourci clavier global

#### Filet de sécurité (aucune session active)

Même si l'utilisateur n'a jamais démarré de session de la journée, l'app collecte quand même les données git et Claude Code en background. En fin de journée, elle peut proposer de
créer les TimeEntries a posteriori à partir des données brutes.

### 3. Scan des sources d'activité

#### Git (repos locaux)

- Scanner les repos configurés via `git log`
- Extraire : commits, fichiers modifiés, lignes ajoutées/supprimées, branches, timestamps
- Filtrer sur l'email ou les emails git de l'utilisateur
- Utilisé pour la détection d'activité ET pour enrichir le détail des sessions

#### Claude Code (sessions locales)

- Parser les fichiers JSON dans `~/.claude/projects/`
- Extraire : début/fin de session, fichiers touchés, résumé des échanges
- Matcher au projet via le chemin du dossier
- Source fiable de timestamps de début/fin

#### Filesystem (fswatch)

- Surveiller les dossiers des repos configurés
- Détecter toute modification de fichier en temps réel
- Utilisé principalement pour la détection d'activité (start/stop sessions)
- Très léger, natif macOS via FSEvents

### 4. Timeline & calendrier

- Vue calendrier mensuel avec total d'heures par jour et par projet
- Code couleur par projet
- Vue détaillée par jour : sessions avec horaires, commits, fichiers touchés
- Résumé auto par jour (ex: "3h45 — 12 commits, 34 fichiers modifiés")
- Édition rapide : ajuster les horaires d'une session, ajouter une entrée manuelle
- Ajout d'entrées manuelles pour l'activité non trackée (réunions, appels, specs…)

### 5. Génération de CRA

- Template de CRA configurable par client
- Génération PDF mensuelle
- Contenu par défaut :
    - Mois, client, projet
    - Tableau des jours travaillés avec heures par jour
    - Total heures, conversion en jours (basé sur daily_hours du projet)
    - Montant HT
- Annexe optionnelle : détail de l'activité par jour
- Export PDF et/ou Excel
- Gestion du statut (brouillon / envoyé)

### 6. Menu bar & notifications

- Icône permanente dans la barre de menu macOS
- États visuels :
    - Inactif (gris) : aucune session en cours
    - Actif (couleur) : session en cours + chrono
- Menu déroulant :
    - Session en cours : projet, durée, bouton stop
    - Démarrer une session (liste des projets)
    - Résumé du jour : "Aujourd'hui : 4h30 (Projet X: 3h, Projet Y: 1h30)"
    - Accès rapide : ajouter une entrée manuelle
    - Ouvrir l'app
- Notifications :
    - Activité détectée sans session → proposer de démarrer
    - Inactivité prolongée → proposer de stopper
    - Rappel fin de mois : "CRA de janvier pas encore généré"

---

## Logique d'arrondi au quart d'heure

```
Durée brute → Arrondi au quart d'heure supérieur

Exemples :
  1-15 min   → 15 min (0.25h)
  16-30 min  → 30 min (0.50h)
  31-45 min  → 45 min (0.75h)
  46-60 min  → 60 min (1.00h)

Configurable par projet : quarter (15), half_hour (30), hour (60)
```

---

## Logique de reconstruction (filet de sécurité) - Exemple non exhaustif

Quand il n'y a pas eu de session active mais que des events existent :

```
1. Récupérer tous les events du jour pour le projet
2. Trier par date d'occurrence
3. Grouper en plages : events espacés de moins de [gap_threshold] minutes (défaut: 90 min)
4. Pour chaque plage :
   - started_at = premier event - [padding] minutes (défaut: 15 min)
   - ended_at = dernier event
   - Arrondir selon la config du projet
5. Créer les entrées avec source = "reconstructed"
6. Notifier l'utilisateur pour validation
```

---

## Exemple de config par défaut (table Settings)

```
idle_timeout_minutes: 20        → délai avant notification d'inactivité
idle_auto_stop_minutes: 30      → délai total avant auto-stop (idle + grâce)
activity_start_padding: 15      → minutes ajoutées avant le 1er event détecté
reconstruction_gap: 90          → gap max entre events pour les grouper en plage
default_daily_hours: 7          → heures/jour de référence pour conversion en jours
default_rounding: "quarter"     → arrondi par défaut
git_author_emails: [""]            → email git global (override possible par repo)
claude_code_path: "~/.claude"   → chemin des logs Claude Code
```

---

## Livrables attendus de cette session de planification

À la fin de cette session, je veux avoir :

1. **Un document d'architecture** décrivant la structure du projet
2. **Une roadmap** avec les features priorisées et leurs dépendances
3. **Des user stories** ou specs basiques pour chaque feature majeure

## Comment procéder

1. **Commence par me poser des questions** si tu as besoin de clarifications
2. **Propose-moi un plan** que je pourrai valider ou ajuster
3. **On itère** jusqu'à avoir une vision claire
4. **Tu génères un plan dans `.ai/PLAN.md`**

---

**Note** : On est en mode PLANIFICATION uniquement. Pas de code pour l'instant, juste de la réflexion et de la documentation.
