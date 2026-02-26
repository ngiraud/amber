# Plan: Flatten Routes

## Objectif

Supprimer le nesting `{client}` des routes projets. Les sessions sont déjà plates. Les routes repositories restent nestées sous `{project}`.

## Résultat attendu

```
GET     /projects                   projects.index
GET     /projects/create            projects.create   (?client_id=xxx en query param)
POST    /projects                   projects.store    (client_id dans le body)
GET     /projects/{project}         projects.show
GET     /projects/{project}/edit    projects.edit
PATCH   /projects/{project}         projects.update
DELETE  /projects/{project}         projects.destroy

POST    /projects/{project}/repositories              projects.repositories.store
DELETE  /projects/{project}/repositories/{repository} projects.repositories.destroy
```

## Phase 1 — Backend

### `routes/web.php`
- Remplacer les routes `/clients/{client}/projects/...` par `/projects/...`
- Supprimer `{client}` de toutes les routes projets sauf repositories

### `app/Http/Controllers/ProjectController.php`
- `create(Client $client)` → `create(Request $request)` — récupère le client via `$request->client_id` pour le pré-remplissage
- `store(Request, Client $client, ...)` → `store(StoreProjectRequest, CreateProject)` — `client_id` vient du body de la requête
- `show(Client, Project)` → `show(Project)` — supprimer `Client $client`
- `edit(Client, Project)` → `edit(Project)` — charger `$project->load('client')` pour passer le client à la vue
- `update(Request, Client, Project, ...)` → `update(UpdateProjectRequest, Project, UpdateProject)` — supprimer `Client`
- `destroy(Client, Project, ...)` → `destroy(Project, DeleteProject)` — supprimer `Client`
- Adapter les redirects : `redirect()->route('projects.show', $project)`

### `app/Http/Requests/Project/StoreProjectRequest.php`
- Ajouter la règle de validation pour `client_id` : `['required', 'string', Rule::exists('clients', 'id')]`

### `app/Actions/Project/CreateProject.php`
- Vérifier que `handle()` accepte toujours un `Client` — si oui, adapter l'appel dans le controller pour résoudre le client depuis `$request->client_id`

## Phase 2 — Frontend

### `resources/js/pages/project/Index.vue`
- `projectRoutes.show({ client: project.client_id, project: project.id })` → `projectRoutes.show(project.id)`

### `resources/js/pages/project/Form.vue`
- Retirer le prop `client` si uniquement utilisé pour la route
- `projectRoutes.update({ client, project })` → `projectRoutes.update(project)`
- `projectRoutes.store(client)` → `projectRoutes.store()`
- `projectRoutes.show({ client, project })` → `projectRoutes.show(project)`
- `projectRoutes.destroy({ client, project })` → `projectRoutes.destroy(project)`
- Adapter le champ caché `client_id` dans le formulaire de création

### `resources/js/pages/project/Show.vue`
- `projectRoutes.destroy({ client, project })` → `projectRoutes.destroy(project)`

### `resources/js/pages/client/Show.vue`
- `projectRoutes.show({ client, project })` → `projectRoutes.show(project)`

### `resources/js/components/ProjectSheet.vue`
- `projectRoutes.update({ client, project })` → `projectRoutes.update(project)`
- `projectRoutes.store(client)` → `projectRoutes.store()`
- Ajouter un champ `client_id` dans le formulaire si ce composant crée des projets

## Phase 3 — Tests

### `tests/Feature/Project/CreateProjectTest.php`
- `route('projects.store', $client)` → `route('projects.store')`
- `route('projects.create', $client)` → `route('projects.create')`
- Passer `client_id` dans le body de la requête POST
- `route('projects.show', [$client, $project])` → `route('projects.show', $project)`

### `tests/Feature/Project/UpdateProjectTest.php`
- `route('projects.update', [$client, $project])` → `route('projects.update', $project)`
- `route('projects.edit', [$client, $project])` → `route('projects.edit', $project)`
- `route('projects.show', [$client, $project])` → `route('projects.show', $project)`

### `tests/Feature/Project/DeleteProjectTest.php`
- `route('projects.destroy', [$client, $project])` → `route('projects.destroy', $project)`

### `tests/Feature/Project/ManageRepositoriesTest.php`
- `route('projects.show', [$client, $project])` → `route('projects.show', $project)` (dans les redirects attendus)

## Notes

- Les tests sessions (`ManageSessionTest`, `StartSessionTest`, `ListSessionsTest`) **ne changent pas** — ils sont déjà plats.
- Après les changements backend, regénérer les types Wayfinder avec `php artisan wayfinder:generate` (ou équivalent) avant d'adapter le frontend.
- Lancer `php artisan test --compact` à la fin pour valider.
