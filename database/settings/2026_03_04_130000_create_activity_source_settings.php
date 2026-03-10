<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('activity_sources.git', [
            'enabled' => false,
            'author_emails' => [],
        ]);

        $this->migrator->add('activity_sources.github', [
            'enabled' => false,
            'username' => '',
        ]);

        $this->migrator->add('activity_sources.claude_code', [
            'enabled' => false,
            'projects_path' => '~/.claude/projects',
        ]);

        $this->migrator->add('activity_sources.gemini', [
            'enabled' => false,
            'projects_path' => '~/.gemini/tmp',
        ]);

        $this->migrator->add('activity_sources.mistral_vibe', [
            'enabled' => false,
            'projects_path' => '~/.vibe/logs/session',
        ]);

        $this->migrator->add('activity_sources.opencode', [
            'enabled' => false,
            'projects_path' => '~/.local/share/opencode',
        ]);

        $this->migrator->add('activity_sources.fswatch', [
            'enabled' => false,
            'debounce_seconds' => 3,
            'excluded_patterns' => [
                '\.git/',
                '\.idea/',
                'node_modules/',
                'vendor/',
                '\.DS_Store',
                'storage/',
                '\.php-cs-fixer\.cache',
                '\.sqlite',
                '\.cache',
            ],
            'allowed_extensions' => [
                'php', 'js', 'ts', 'vue', 'jsx', 'tsx',
                'css', 'scss', 'sass', 'less',
                'html', 'blade.php',
                'json', 'yaml', 'yml', 'toml', 'env',
                'md', 'mdx',
                'py', 'rb', 'go', 'rs', 'java', 'kt', 'swift',
                'sh', 'bash', 'zsh',
                'sql',
            ],
        ]);
    }
};
