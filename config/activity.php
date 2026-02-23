<?php

declare(strict_types=1);

return [
    'idle_timeout_minutes' => (int) env('ACTIVITY_IDLE_TIMEOUT', 22),

    'scan_interval_minutes' => (int) env('ACTIVITY_SCAN_INTERVAL', 2),

    'git' => [
        'author_email' => env('ACTIVITY_GIT_AUTHOR_EMAIL'),
    ],

    'fswatch' => [
        'enabled' => (bool) env('ACTIVITY_FSWATCH_ENABLED', true),
        'debounce_seconds' => (int) env('ACTIVITY_FSWATCH_DEBOUNCE', 3),
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
    ],

    'claude' => [
        'projects_path' => env('ACTIVITY_CLAUDE_PROJECTS_PATH', '~/.claude/projects'),
    ],
];
