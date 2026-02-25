<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum ActivityEventType: string
{
    use EnhanceEnum;

    case GitCommit = 'git-commit';
    case FileChange = 'file-change';
    case ClaudeSessionStart = 'claude-session-start';
    case ClaudeSessionEnd = 'claude-session-end';
    case ClaudeFileTouch = 'claude-file-touch';

    public function label(): string
    {
        return match ($this) {
            self::GitCommit => 'Commit',
            self::FileChange => 'File change',
            self::ClaudeSessionStart => 'Session start',
            self::ClaudeSessionEnd => 'Session end',
            self::ClaudeFileTouch => 'File touch',
        };
    }
}
