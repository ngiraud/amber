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
    case GeminiSessionStart = 'gemini-session-start';
    case GeminiSessionEnd = 'gemini-session-end';
    case GeminiFileTouch = 'gemini-file-touch';
    case GeminiUserPrompt = 'gemini-user-prompt';
    case VibeSessionStart = 'vibe-session-start';
    case VibeSessionEnd = 'vibe-session-end';
    case VibeFileTouch = 'vibe-file-touch';
    case VibeUserPrompt = 'vibe-user-prompt';
    case OpencodeSessionStart = 'opencode-session-start';
    case OpencodeSessionEnd = 'opencode-session-end';
    case OpencodeFileTouch = 'opencode-file-touch';
    case OpencodeUserPrompt = 'opencode-user-prompt';

    public function label(): string
    {
        return match ($this) {
            self::GitCommit => 'Commit',
            self::GitBranchSwitch => 'Branch switch',
            self::GitPrOpened => 'PR opened',
            self::GitPrMerged => 'PR merged',
            self::FileChange => 'File change',
            self::ClaudeSessionStart, self::GeminiSessionStart, self::VibeSessionStart, self::OpencodeSessionStart => 'Session start',
            self::ClaudeSessionEnd, self::GeminiSessionEnd, self::VibeSessionEnd, self::OpencodeSessionEnd => 'Session end',
            self::ClaudeFileTouch, self::GeminiFileTouch, self::VibeFileTouch, self::OpencodeFileTouch => 'File touch',
            self::ClaudeUserPrompt, self::GeminiUserPrompt, self::VibeUserPrompt, self::OpencodeUserPrompt => 'User prompt',
        };
    }

    public function isUserPrompt(): bool
    {
        return in_array($this, [
            self::ClaudeUserPrompt,
            self::GeminiUserPrompt,
            self::VibeUserPrompt,
            self::OpencodeUserPrompt,
        ], true);
    }

    public function isFileTouch(): bool
    {
        return in_array($this, [
            self::FileChange,
            self::ClaudeFileTouch,
            self::GeminiFileTouch,
            self::VibeFileTouch,
            self::OpencodeFileTouch,
        ], true);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{label: ?string, detail: ?string}
     */
    public function toContextParts(array $metadata): array
    {
        return match ($this) {
            self::GitCommit => [
                'label' => $metadata['message'] ?? null,
                'detail' => $this->parseDetailsFromMetadata($metadata),
            ],
            self::GitBranchSwitch, self::GitPrOpened, self::GitPrMerged => [
                'label' => $this->parseDetailsFromMetadata($metadata),
                'detail' => null,
            ],
            self::ClaudeUserPrompt, self::GeminiUserPrompt, self::VibeUserPrompt, self::OpencodeUserPrompt => [
                'label' => null,
                'detail' => $this->parseDetailsFromMetadata($metadata),
            ],
            default => [
                'label' => null,
                'detail' => null,
            ],
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
            ActivityEventType::FileChange,
            ActivityEventType::ClaudeFileTouch,
            ActivityEventType::GeminiFileTouch,
            ActivityEventType::VibeFileTouch,
            ActivityEventType::OpencodeFileTouch => $metadata['file_path'] ?? '',
            ActivityEventType::ClaudeUserPrompt,
            ActivityEventType::GeminiUserPrompt,
            ActivityEventType::VibeUserPrompt,
            ActivityEventType::OpencodeUserPrompt => mb_substr($metadata['prompt'] ?? '', 0, 80),
            default => '',
        };
    }
}
