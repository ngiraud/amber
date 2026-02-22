<?php

declare(strict_types=1);

return [
    'idle_timeout_minutes' => (int) env('ACTIVITY_IDLE_TIMEOUT', 15),

    'scan_interval_minutes' => (int) env('ACTIVITY_SCAN_INTERVAL', 5),

    'git' => [
        'author_email' => env('ACTIVITY_GIT_AUTHOR_EMAIL'),
    ],

    'fswatch' => [
        'enabled' => (bool) env('ACTIVITY_FSWATCH_ENABLED', true),
        'debounce_seconds' => (int) env('ACTIVITY_FSWATCH_DEBOUNCE', 3),
        'excluded_patterns' => [
            '\.git/',
            'node_modules/',
            'vendor/',
            '\.DS_Store',
            'storage/logs/',
            '\.php-cs-fixer\.cache',
        ],
    ],

    'claude' => [
        'projects_path' => env('ACTIVITY_CLAUDE_PROJECTS_PATH', '~/.claude/projects'),
    ],
];
