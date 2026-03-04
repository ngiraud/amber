<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        /** @param  string  $name */
        $read = fn (string $name): mixed => json_decode(
            DB::table('settings')->where('group', 'activity')->where('name', $name)->value('payload') ?? 'null',
            true
        );

        $this->migrator->add('activity_sources.git', [
            'enabled' => $read('git_enabled') ?? true,
            'author_emails' => $read('git_author_emails') ?? [],
        ]);

        $this->migrator->add('activity_sources.github', [
            'enabled' => $read('github_enabled') ?? true,
            'username' => $read('github_username'),
        ]);

        $this->migrator->add('activity_sources.claude_code', [
            'enabled' => $read('claude_code_enabled') ?? true,
            'projects_path' => $read('claude_code_projects_path') ?? '~/.claude/projects',
        ]);

        $this->migrator->add('activity_sources.fswatch', [
            'enabled' => $read('fswatch_enabled') ?? true,
            'debounce_seconds' => $read('fswatch_debounce_seconds') ?? 3,
            'excluded_patterns' => $read('fswatch_excluded_patterns') ?? [],
            'allowed_extensions' => $read('fswatch_allowed_extensions') ?? [],
        ]);

        // Remove individual source settings from activity group
        $this->migrator->delete('activity.git_enabled');
        $this->migrator->delete('activity.git_author_emails');
        $this->migrator->delete('activity.github_enabled');
        $this->migrator->delete('activity.github_username');
        $this->migrator->delete('activity.claude_code_enabled');
        $this->migrator->delete('activity.claude_code_projects_path');
        $this->migrator->delete('activity.fswatch_enabled');
        $this->migrator->delete('activity.fswatch_debounce_seconds');
        $this->migrator->delete('activity.fswatch_excluded_patterns');
        $this->migrator->delete('activity.fswatch_allowed_extensions');
    }
};
