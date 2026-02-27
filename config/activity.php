<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Idle Timeout
    |--------------------------------------------------------------------------
    |
    | The number of minutes of inactivity after which the current session is
    | considered idle and may be automatically stopped.
    |
    */

    'idle_timeout_minutes' => (int) env('ACTIVITY_IDLE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Untracked Activity Threshold
    |--------------------------------------------------------------------------
    |
    | The minimum number of minutes of detected activity required before it is
    | considered significant enough to prompt the user to track it.
    |
    */

    'untracked_threshold_minutes' => (int) env('ACTIVITY_UNTRACKED_THRESHOLD', 15),

    /*
    |--------------------------------------------------------------------------
    | Scan Interval
    |--------------------------------------------------------------------------
    |
    | How often (in minutes) the application scans all activity sources to
    | detect and record new activity events.
    |
    */

    'scan_interval_minutes' => (int) env('ACTIVITY_SCAN_INTERVAL', 2),

    /*
    |--------------------------------------------------------------------------
    | Block End Padding
    |--------------------------------------------------------------------------
    |
    | When reconstructing sessions from activity events, this padding (in
    | minutes) is added after the last recorded event in a block to account
    | for work that continued beyond the final detected activity.
    |
    */

    'block_end_padding_minutes' => (int) env('ACTIVITY_BLOCK_END_PADDING', 15),

    /*
    |--------------------------------------------------------------------------
    | Git Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting activity from Git commits. Provide a
    | comma-separated list of author emails to match against commit history.
    |
    */

    'git' => [
        'author_emails' => env('ACTIVITY_GIT_AUTHOR_EMAILS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Watcher (fswatch)
    |--------------------------------------------------------------------------
    |
    | Configuration for the fswatch-based file system activity detector.
    | You can toggle it, set a debounce delay, exclude path patterns,
    | and restrict detection to specific file extensions.
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Claude Code Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for detecting activity from Claude Code session logs.
    | Set the path to the directory where Claude Code stores project data.
    |
    */

    'claude' => [
        'projects_path' => env('ACTIVITY_CLAUDE_PROJECTS_PATH', '~/.claude/projects'),
    ],

];
