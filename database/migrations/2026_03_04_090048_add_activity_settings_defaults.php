<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $defaults = [
            'activity.idle_timeout_minutes' => 30,
            'activity.untracked_threshold_minutes' => 15,
            'activity.scan_interval_minutes' => 2,
            'activity.block_end_padding_minutes' => 15,
            'activity.sources.git.enabled' => true,
            'activity.sources.github.enabled' => true,
            'activity.sources.claude_code.enabled' => true,
            'activity.sources.fswatch.enabled' => true,
            'activity.sources.fswatch.debounce_seconds' => 3,
            'activity.sources.fswatch.excluded_patterns' => [
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
            'activity.sources.fswatch.allowed_extensions' => [
                'php', 'js', 'ts', 'vue', 'jsx', 'tsx',
                'css', 'scss', 'sass', 'less',
                'html', 'blade.php',
                'json', 'yaml', 'yml', 'toml', 'env',
                'md', 'mdx',
                'py', 'rb', 'go', 'rs', 'java', 'kt', 'swift',
                'sh', 'bash', 'zsh',
                'sql',
            ],
            'timezone' => null,
            'locale' => null,
        ];

        $rows = collect($defaults)
            ->map(fn ($value, $key) => [
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        DB::table('app_settings')->upsert($rows, ['key'], ['updated_at']);
    }

    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', [
            'activity.idle_timeout_minutes',
            'activity.untracked_threshold_minutes',
            'activity.scan_interval_minutes',
            'activity.block_end_padding_minutes',
            'activity.sources.git.enabled',
            'activity.sources.github.enabled',
            'activity.sources.claude_code.enabled',
            'activity.sources.fswatch.enabled',
            'activity.sources.fswatch.debounce_seconds',
            'activity.sources.fswatch.excluded_patterns',
            'activity.sources.fswatch.allowed_extensions',
            'timezone',
            'locale',
        ])->delete();
    }
};
