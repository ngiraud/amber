# Phase 3 — Activity Detection Schemas

## Testing file watcher againsqdfsqdqsdsqdsfds
---
Scan d'activité (toutes les minutes)

Scheduler
└─ Interroge toutes les sources d'activité depuis la dernière fois
│
├─ Git : nouveaux commits sur les repos des projets actifs ?
└─ Claude Code : nouvelles sessions ou fichiers touchés ?
│
└─ Pour chaque activité trouvée :
├─ Y a-t-il une session de travail en cours sur ce projet ?
│ OUI → lier l'activité à la session
│ NON → session_id = null
└─ Enregistrer l'activité en base
sss
  ---
Surveillance temps réel (fswatch)

Au démarrage de l'app
└─ Lance un processus qui surveille les dossiers des projets actifs
│
[un fichier est modifié]
│
└─ Enregistre immédiatement l'activité
├─ Quel projet ? → déduit du chemin du fichier
├─ Quelle session ? → session active à cet instant, ou null
└─ Sauvegarde en base

  ---
Détection d'inactivité (toutes les minutes)

Scheduler
└─ Y a-t-il une session en cours ?
NON → rien à faire
OUI → Depuis combien de temps aucune activité sur ce projet ?
< 15 min → rien à faire
≥ 15 min → Arrêter automatiquement la session

+ Notifier l'utilisateur

--------------------------------------------------------------------------------------------------

⏺ Flow 1 — activity:scan (toutes les minutes)

Schedule → activity:scan
└── ScanAllSources::handle(now() - interval)
│
├── [1 query] ProjectRepository::forActiveProjects()->get()  → $repos
├── [1 query] Session::findActive()                          → $activeSession
│
├── discoverSources()  →  [GitActivitySource, ClaudeCodeActivitySource]
│
├── GitActivitySource::scan($since, $repos)
│ └── foreach $repo:
│ Process::run(['git', '-C', path, 'log', '--after=...'])
│ → parseLine() → ActivityEventData(GitCommit, projectId, repoId)
│
├── ClaudeCodeActivitySource::scan($since, $repos)
│ └── glob ~/.claude/projects/*/ → foreach .jsonl:
│ resolveCwd() → match $repos par local_path
│ → ActivityEventData(ClaudeSessionStart | ClaudeFileTouch)
│
├── unique([type, occurredAt, sourceType])   ← déduplication
│
└── foreach ActivityEventData → RecordActivityEvent::handle($data, $activeSession)
│
├── projectId déjà set → skip DetectActiveProject
├── sessionId = activeSession->project_id === projectId ? id : null
├── ActivityEvent::create([session_id nullable])
└── ActivityDetected::dispatch($event)

  ---
Flow 2 — Watcher temps réel (fswatch, push)

NativeAppServiceProvider::boot()
└── FileWatcherService::start()
└── ChildProcess::start('fswatch -r [paths]', alias: 'file-watcher')

[fichier modifié]
└── MessageReceived(alias: 'file-watcher', data: '/path/file.php')
└── HandleFileWatcherMessage::handle()
└── RecordActivityEvent::handle(ActivityEventData(FileChange, filePath))
│
├── [query] DetectActiveProject::handle(filePath)
│ └── forActiveProjects()->get() → match longest local_path
├── [query] Session::findActive()   ← résolu indépendamment
├── ActivityEvent::create([session_id nullable])
└── ActivityDetected::dispatch($event)

  ---
Flow 3 — activity:check-idle (toutes les minutes)

Schedule → activity:check-idle
└── CheckIdleStatus::handle()
│
├── [1 query] Session::findActive()  → null → return early
│
├── [1 query] ActivityEvent::where(project_id)->latest('occurred_at')->first()
│
├── $lastActivityAt = $lastEvent?->occurred_at ?? $session->started_at
│                      (déjà CarbonImmutable via cast 'datetime')
│
├── $idleMinutes = $lastActivityAt->diffInMinutes(now())
│
└── if $idleMinutes >= config('activity.idle_timeout_minutes'):
IdleTimeoutReached::dispatch($session, $lastActivityAt)
└── HandleIdleTimeout::handle()
├── StopSession::handle($session)
└── Notification::title('Session Auto-Stopped')...->show()

  ---
Points clés à retenir :

- $repos et $activeSession sont fetchés une seule fois dans ScanAllSources et passés en paramètre
- session_id est nullable — l'event est toujours créé pour les projets actifs
- Le file watcher (fswatch) appelle Session::findActive() indépendamment (pas de session partagée)

## Watcher Test

Testing file change detection — should record FileChange event
