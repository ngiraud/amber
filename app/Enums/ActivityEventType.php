<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;

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
        return Str::headline($this->name);
    }
}
