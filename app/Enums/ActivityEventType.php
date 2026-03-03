<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

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

    public function parseDetailsFromMetadata(array $metadata): string
    {
        return match ($this) {
            ActivityEventType::GitCommit => Str::of($metadata['message'] ?? '')
                ->when(
                    ! empty($metadata['hash']),
                    fn (Stringable $str) => $str->prepend(sprintf('[%s] ', Str::of($metadata['hash'])->substr(0, 7)->toString()))
                )
                ->when(
                    ! empty($metadata['branch']),
                    fn (Stringable $str) => $str->append(sprintf(' (%s)', $metadata['branch']))
                )
                ->toString(),
            ActivityEventType::GitBranchSwitch => sprintf(
                '%s → %s',
                $metadata['from_branch'] ?? '',
                $metadata['to_branch'] ?? '',
            ),
            ActivityEventType::GitPrOpened, ActivityEventType::GitPrMerged => sprintf(
                'PR #%s: %s',
                $metadata['number'] ?? '',
                $metadata['title'] ?? '',
            ),
            ActivityEventType::FileChange, ActivityEventType::ClaudeFileTouch => $metadata['file_path'] ?? '',
            ActivityEventType::ClaudeUserPrompt => mb_substr($metadata['prompt'] ?? '', 0, 80),
            default => '',
        };
    }
}
