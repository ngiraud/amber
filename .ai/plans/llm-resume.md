# Feature: Résumé intelligent avec LLM

## Contexte

L'application génère des rapports d'activité (CRA) mensuels pour les clients. Actuellement, les descriptions des lignes de rapport sont des concaténations brutes de commits, branches, PRs et prompts Claude. On veut produire des résumés professionnels via un LLM, adaptés à un document client.

## Décisions d'architecture

### Outil : Laravel AI SDK (`laravel/ai`) — déjà installé

- SDK officiel Laravel, système d'Agents avec structured output (`HasStructuredOutput`)
- Enum `Laravel\Ai\Enums\Lab` = tous les providers (Anthropic, OpenAI, Mistral, Ollama, Gemini, DeepSeek, Groq, xAI, OpenRouter...)
- On réutilise `Lab` directement dans notre enum `AiProvider` — pas besoin de mapper manuellement
- Config `config/ai.php` déjà publiée avec tous les providers
- Embeddings + vector search intégrés pour Phase 2
- Provider/model passés dynamiquement à `->prompt(provider:, model:)`

### Providers exposés à l'utilisateur

On ne montre pas tous les providers de `Lab`. On expose un sous-ensemble pertinent pour la text generation :

- **Anthropic** (Claude) — défaut
- **OpenAI** (GPT)
- **Mistral**
- **Ollama** (local, gratuit)
- **Gemini**

Chacun a un modèle par défaut suggéré et un flag `requires_api_key` (false pour Ollama).

### Niveau de résumé : par ligne de rapport (projet + jour), batché

- Un rapport = ~20 lignes → **un seul appel LLM**
- L'Agent utilise `HasStructuredOutput` pour retourner un JSON typé
- Input ~2-3K tokens, output ~1K tokens → très économique

### Stockage : colonne `summary` sur `activity_report_lines`

- `description` = version brute (inchangée)
- `summary` = version LLM, nullable
- Affichage/exports : `summary ?? description`

### Opt-in avec choix par rapport

- Désactivé par défaut
- Settings > AI : provider, model, API key, langue
- **Bouton "Verify"** : teste la connexion au provider (pattern `TestActivitySourceConnection`)
- À la création du rapport : bandeau rappel + checkbox pour désactiver ponctuellement

---

## Flow utilisateur

1. **Configuration** (une fois) : Settings > AI → activer, choisir provider, clé API, modèle, langue → **Verify** pour tester
2. **Création de rapport** : Bandeau "Les résumés seront générés par IA (Anthropic / claude-sonnet-4-20250514)" + checkbox "Utiliser les résumés IA" (coché par défaut si AI activé, décochable)
3. **Génération** : Le job fait son flow normal + étape Summarizing si `useAiSummary=true`
4. **Résultat** : `summary ?? description` affiché. Éditable manuellement
5. **Sans AI** : Descriptions brutes comme avant

---

## Fichiers à créer

| Fichier | Description |
|---------|------------|
| `app/Settings/AiSettings.php` | Settings group `ai` : `enabled`, `provider` (string, backed par `AiProvider`), `model`, `api_key`, `summary_language` |
| `database/settings/xxxx_create_ai_settings.php` | Migration Spatie Settings |
| `app/Enums/AiProvider.php` | Enum : `Anthropic`, `OpenAI`, `Mistral`, `Ollama`, `Gemini` — chaque case a `label()`, `defaultModel()`, `requiresApiKey()`, `toLab(): Lab` |
| `app/Ai/Agents/ReportSummarizer.php` | Agent `HasStructuredOutput` — instructions + schema (array de `{id, summary}`) |
| `app/Actions/ActivityReport/SummarizeReportLines.php` | Charge les lignes, construit le prompt, appelle `ReportSummarizer->prompt(provider:, model:)`, bulk-update summaries |
| `app/Actions/Settings/UpdateAiSettings.php` | Pattern `UpdateGeneralSettings` |
| `app/Actions/Settings/TestAiConnection.php` | Envoie un prompt simple ("Say hello") au provider configuré, retourne bool — pattern `TestActivitySourceConnection` |
| `app/Http/Requests/Settings/UpdateAiSettingsRequest.php` | Validation |
| `app/Http/Controllers/Settings/AiSettingsController.php` | edit/update + test endpoint |
| `resources/js/pages/settings/Ai.vue` | Toggle, provider select, model input, API key (masqué), langue, bouton Verify |
| `database/migrations/xxxx_add_summary_to_activity_report_lines.php` | `summary` nullable text |

## Fichiers à modifier

| Fichier | Modification |
|---------|-------------|
| `config/ai.php` | La clé API sera injectée dynamiquement depuis `AiSettings` via `AppServiceProvider` |
| `app/Enums/ActivityReportStep.php` | `case Summarizing` |
| `app/Jobs/GenerateActivityReportJob.php` | Reçoit `$useAiSummary`, appelle `SummarizeReportLines` (try/catch) |
| `app/Actions/ActivityReport/GenerateActivityReport.php` | Passe `$useAiSummary` au job |
| `app/Http/Resources/ActivityReportLineResource.php` | `summary`, `display_description` |
| `resources/js/pages/report/Show.vue` | `display_description` + tooltip raw |
| `resources/js/pages/report/Index.vue` ou dialog | Bandeau AI + checkbox `useAiSummary` |
| `resources/js/components/settings/SettingsLayout.vue` | Onglet "AI" |
| `resources/js/types/resources.ts` | Types `AiSettings`, `summary` sur line, step `summarizing` |
| `routes/web.php` | Routes settings AI |
| Exports PDF/CSV | `summary ?? description` |
| `app/Providers/AppServiceProvider.php` | Injection dynamique de la clé API dans `config('ai.providers.X.key')` au boot |

## Agent ReportSummarizer

```php
#[MaxTokens(2048)]
#[Temperature(0.3)]
class ReportSummarizer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        $language = app(AiSettings::class)->summary_language;
        return "You are a professional activity report writer for software developers.
        For each line provided, write a concise summary (1-2 sentences) suitable for a client-facing report.
        Write summaries in {$language}. Focus on what was accomplished, not technical noise.
        Do not mention commit hashes, branch names, or file paths unless meaningful to the client.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summaries' => $schema->array()->items(
                $schema->object()->properties([
                    'id' => $schema->string()->required(),
                    'summary' => $schema->string()->required(),
                ])
            )->required(),
        ];
    }
}
```

Provider et model passés dynamiquement : `(new ReportSummarizer)->prompt($data, provider: $provider, model: $model)`

## Séquencement

1. `AiSettings` + migration + enum `AiProvider`
2. Config dynamique dans `AppServiceProvider`
3. `UpdateAiSettings` + `TestAiConnection` + request + controller + routes
4. Page settings AI (Vue) + SettingsLayout
5. Migration `summary` sur `activity_report_lines`
6. `ReportSummarizer` agent + `SummarizeReportLines` action
7. Intégration `GenerateActivityReport` + Job (flag `useAiSummary`)
8. UI création rapport (bandeau + checkbox)
9. Affichage (Show.vue, resources, exports)
10. Tests

## Tests

- **Unit** : `SummarizeReportLinesTest` — skip si disabled, mock agent response, assignation, erreurs
- **Feature** : `GenerateActivityReportWithSummaryTest` — flow avec `SummarizeReportLines::fake()`
- **Feature** : `UpdateAiSettingsTest` — CRUD + validation
- **Feature** : `TestAiConnectionTest` — succès/échec connexion

## Vérification

```bash
composer test:all  # Pint + Rector + PHPStan + Pest
php artisan native:serve  # Test manuel
```

## Phase 2 (future) : Vectorisation / recherche sémantique

- Laravel AI SDK `Embeddings::for()` + `whereVectorSimilarTo`
- Colonne embedding sur `activity_events`
- Recherche sémantique : "qu'ai-je fait sur l'auth ?"
- Ne bloque pas la Phase 1
