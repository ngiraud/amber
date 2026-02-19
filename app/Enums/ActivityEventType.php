<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityEventType: string
{
    case GitCommit = 'git-commit';
    case FileChange = 'file-change';
    case ClaudeSessionStart = 'claude-session-start';
    case ClaudeSessionEnd = 'claude-session-end';
    case ClaudeFileTouch = 'claude-file-touch';
}
