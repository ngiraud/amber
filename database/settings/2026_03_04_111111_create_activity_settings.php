<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('activity.idle_timeout_minutes', 30);
        $this->migrator->add('activity.untracked_threshold_minutes', 15);
        $this->migrator->add('activity.scan_interval_minutes', 2);
        $this->migrator->add('activity.block_end_padding_minutes', 15);

        $this->migrator->add('activity.git_enabled', true);
        $this->migrator->add('activity.git_author_emails', []);
        $this->migrator->add('activity.github_enabled', true);
        $this->migrator->add('activity.claude_code_enabled', true);
        $this->migrator->add('activity.fswatch_enabled', true);
        $this->migrator->add('activity.fswatch_debounce_seconds', 3);
        $this->migrator->add('activity.fswatch_excluded_patterns', [
            '\.git/',
            '\.idea/',
            'node_modules/',
            'vendor/',
            '\.DS_Store',
            'storage/',
            '\.php-cs-fixer\.cache',
            '\.sqlite',
            '\.cache',
        ]);
        $this->migrator->add('activity.fswatch_allowed_extensions', [
            'php', 'js', 'ts', 'vue', 'jsx', 'tsx',
            'css', 'scss', 'sass', 'less',
            'html', 'blade.php',
            'json', 'yaml', 'yml', 'toml', 'env',
            'md', 'mdx',
            'py', 'rb', 'go', 'rs', 'java', 'kt', 'swift',
            'sh', 'bash', 'zsh',
            'sql',
        ]);
    }
};
