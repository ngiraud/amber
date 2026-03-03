<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;

enum ActivityEventType: string
{
    use EnhanceEnum;

    case GitCommit = 'git-commit';
    case GitBranchSwitch = 'git-branch-switch';
    case GitPrOpened = 'git-pr-opened';
    case GitPrMerged = 'git-pr-merged';
    case FileChange = 'file-change';
    case ClaudeSessionStart = 'claude-session-start';
    case ClaudeSessionEnd = 'claude-session-end';
    case ClaudeFileTouch = 'claude-file-touch';
    case ClaudeUserPrompt = 'claude-user-prompt';

    public function label(): string
    {
        return match ($this) {
            self::GitCommit => 'Commit',
            self::GitBranchSwitch => 'Branch switch',
            self::GitPrOpened => 'PR opened',
            self::GitPrMerged => 'PR merged',
            self::FileChange => 'File change',
            self::ClaudeSessionStart => 'Session start',
            self::ClaudeSessionEnd => 'Session end',
            self::ClaudeFileTouch => 'File touch',
            self::ClaudeUserPrompt => 'User prompt',
        };
    }
}
