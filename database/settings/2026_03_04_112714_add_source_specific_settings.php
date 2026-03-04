<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('activity.github_username', null);
        $this->migrator->add('activity.claude_code_projects_path', '~/.claude/projects');
    }
};
