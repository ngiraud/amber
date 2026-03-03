# Phase 4.5 — Context Sources Enrichment

## Context

The app tracks developer activity events but currently captures only basic data: git commit hash/email/message, Claude Code session starts and file touches. Phase 5 (Activity Report generation) needs richer context to describe **what was done** — branch names, diff stats, PR info, and user prompts. This phase enriches the existing sources and adds a GitHub source.

---

## Sub-feature A: Git Enrichment (branch + diff stats)

### Files to modify

- `app/Services/ActivitySources/GitActivitySource.php` — change `git log` format to include branch + numstat
- `app/Enums/ActivityEventType.php` — add `GitBranchSwitch` case
- `app/Models/ActivityEvent.php` — update `detail` accessor for new metadata fields + branch switch
- `tests/Feature/Activity/GitActivitySourceTest.php` — new tests for enriched metadata + branch switch

### Implementation

1. **Enrich `GitCommit` metadata** — change the `git log` command to use `--format=%H|%ae|%aI|%s` + `--numstat` and parse multi-line output. Each commit block becomes:
   - `hash`, `author_email`, `message` (existing)
   - `branch` — run a separate `git -C {path} branch --contains {hash} --format='%(refname:short)'` per commit, or more efficiently: run one `git -C {path} log --format=%H|%ae|%aI|%D|%s --numstat` where `%D` gives ref decorations. For simplicity, also capture current branch via `git -C {path} rev-parse --abbrev-ref HEAD` once per repo and attach to all commits.
   - `added_lines` (int), `removed_lines` (int), `changed_files` (int) — parsed from `--numstat` lines

   **Approach**: Use `git log --format=COMMIT_SEP%H|%ae|%aI|%s --numstat` with a unique separator. Parse blocks. Get current branch once per repo with `git rev-parse --abbrev-ref HEAD`.

2. **New `GitBranchSwitch` event type** — scan `git reflog` for `checkout:` entries:
   - Command: `git -C {path} reflog --format=%ae|%gI|%gs --after={since}`
   - Filter lines matching `checkout: moving from {branch} to {branch}`
   - Metadata: `{ from_branch, to_branch, author_email }`
   - Also filter by author emails

3. **Update `ActivityEvent.detail`** accessor to show branch info: `[abc1234] (feature/foo) Initial commit`

---

## Sub-feature B: GitHub Activity Source (PRs)

### Files to create

- `app/Services/ActivitySources/GitHubActivitySource.php`
- `tests/Feature/Activity/GitHubActivitySourceTest.php`

### Files to modify

- `app/Enums/ActivityEventSourceType.php` — add `GitHub = 'github'` case + color
- `app/Enums/ActivityEventType.php` — add `GitPrOpened`, `GitPrMerged` cases
- `config/activity.php` — add `'GitHub'` source config (key must match enum `->name` used in `isEnabled()`)
- `app/Models/ActivityEvent.php` — update `detail` accessor for PR events
- `database/seeders/AppSettingSeeder.php` — add `github_username` default

### Implementation

1. **Enum additions**:
   - `ActivityEventSourceType::GitHub` with `color()` → `'text-purple-400'`
   - `ActivityEventType::GitPrOpened` (`'git-pr-opened'`) + `GitPrMerged` (`'git-pr-merged'`)

2. **Config** — add to `config/activity.php`:
   ```php
   'GitHub' => [
       'enabled' => (bool) env('ACTIVITY_GITHUB_ENABLED', true),
   ],
   ```
   Note: config key is `GitHub` (matching enum case name) because `isEnabled()` uses `$this->name`.

3. **`GitHubActivitySource`**:
   - `isAvailable()`: `Process::run(['gh', 'auth', 'status'])->successful()`
   - `scan()`: for each repo, detect GitHub remote dynamically via `git -C {path} remote get-url origin`, parse `owner/repo` from GitHub URL pattern
   - Use `gh pr list --author {username} --state all --json number,title,body,state,createdAt,mergedAt,url,headRefName --repo {owner/repo} --limit 100` per repo
   - Filter PRs where `createdAt` or `mergedAt` is after `$since`
   - Emit `GitPrOpened` for each PR created after `$since` — metadata: `{ number, title, body, url, branch, repo }`
   - Emit `GitPrMerged` for each PR merged after `$since` — metadata: same + `merged_at`
   - Username from `AppSetting::get('github_username')` or fallback to `gh api user --jq .login`

4. **`AppSetting` seeder** — add `github_username` (null default)

5. **`detail` accessor** — PRs: `PR #{number}: {title}`

---

## Sub-feature C: Claude Code User Prompts

### Files to modify

- `app/Services/ActivitySources/ClaudeCodeActivitySource.php` — detect `type=human` entries
- `app/Enums/ActivityEventType.php` — add `ClaudeUserPrompt` case
- `app/Models/ActivityEvent.php` — update `detail` accessor
- `tests/Feature/Activity/ClaudeCodeActivitySourceTest.php` — new tests

### Implementation

1. **New `ClaudeUserPrompt` event** (`'claude-user-prompt'`) — one per user message
2. In `scanFile()`, detect entries where `type === 'human'`:
   - Extract prompt text from `message.content` — can be a string or array of `{type: 'text', text: '...'}` blocks
   - Truncate to 500 chars to keep metadata manageable
   - Metadata: `{ session_id, prompt }`
3. **`detail` accessor** — show truncated prompt text (first 80 chars)

---

## Execution Order

### Phase 1 — Enums & config (foundation)
1. Add `GitBranchSwitch`, `GitPrOpened`, `GitPrMerged`, `ClaudeUserPrompt` to `ActivityEventType`
2. Add `GitHub` to `ActivityEventSourceType` with `color()` and `toArray()`
3. Add GitHub config to `config/activity.php`
4. Add `github_username` to AppSetting seeder
5. Run Pint

### Phase 2 — Git enrichment
1. Modify `GitActivitySource::scanRepository()` for branch + numstat
2. Add `scanReflog()` method for `GitBranchSwitch` events
3. Update `ActivityEvent.detail` for git enrichments
4. Write tests for enriched git metadata + branch switch
5. Run Pint + tests

### Phase 3 — GitHub source
1. Create `GitHubActivitySource` class
2. Update `ActivityEvent.detail` for PR events
3. Write tests (Process::fake for `gh` CLI)
4. Run Pint + tests

### Phase 4 — Claude Code prompts
1. Add `ClaudeUserPrompt` detection in `ClaudeCodeActivitySource::scanFile()`
2. Update `ActivityEvent.detail` for prompts
3. Write tests
4. Run Pint + tests

### Phase 5 — Validation
1. `composer test:all` (Pint, Rector, PHPStan, Pest)
2. Fix any issues

---

## Key Files Reference

| File | Role |
|------|------|
| `app/Contracts/ActivitySource.php` | Interface: `scan(since, repos)` |
| `app/Data/ActivityEventData.php` | DTO for events |
| `app/Enums/ActivityEventSourceType.php` | Source type enum with auto-discovery |
| `app/Enums/ActivityEventType.php` | Event type enum |
| `app/Services/ActivitySources/GitActivitySource.php` | Git source |
| `app/Services/ActivitySources/ClaudeCodeActivitySource.php` | Claude source |
| `app/Models/ActivityEvent.php` | Model with `detail` accessor |
| `app/Actions/Activity/RecordActivityEvent.php` | Dedup key: `project_id, repo_id, source_type, type, occurred_at` |
| `config/activity.php` | Source config + enabled flags |

## Verification

1. `php artisan test --compact --filter=GitActivitySource` — enriched metadata tests pass
2. `php artisan test --compact --filter=GitHubActivitySource` — new source tests pass
3. `php artisan test --compact --filter=ClaudeCodeActivitySource` — prompt detection tests pass
4. `php artisan test --compact --filter=ScanAllSources` — GitHub source discovered + scanned
5. `composer test:all` — full suite green
