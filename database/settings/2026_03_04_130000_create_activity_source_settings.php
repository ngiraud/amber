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
            'enabled' => true,
            'author_emails' => [],
        ]);

        $this->migrator->add('activity_sources.github', [
            'enabled' => true,
            'username' => '',
        ]);

        $this->migrator->add('activity_sources.claude_code', [
            'enabled' => true,
            'projects_path' => '~/.claude/projects',
        ]);

        $this->migrator->add('activity_sources.fswatch', [
            'enabled' => true,
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
