<?php

declare(strict_types=1);

namespace App\Settings;

use App\Data\ActivitySourceConfigs\ClaudeCodeSourceConfig;
use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Data\ActivitySourceConfigs\GitHubSourceConfig;
use App\Data\ActivitySourceConfigs\GitSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Settings\Casts\SourceConfigCast;
use Spatie\LaravelSettings\Settings;

class ActivitySourceSettings extends Settings
{
    public GitSourceConfig $git;

    public GitHubSourceConfig $github;

    public ClaudeCodeSourceConfig $claude_code;

    public FswatchSourceConfig $fswatch;

    public static function group(): string
    {
        return 'activity_sources';
    }

    public static function casts(): array
    {
        return [
            'git' => new SourceConfigCast(GitSourceConfig::class),
            'github' => new SourceConfigCast(GitHubSourceConfig::class),
            'claude_code' => new SourceConfigCast(ClaudeCodeSourceConfig::class),
            'fswatch' => new SourceConfigCast(FswatchSourceConfig::class),
        ];
    }

    public function configFor(ActivityEventSourceType $type): SourceConfig
    {
        return match ($type) {
            ActivityEventSourceType::Git => $this->git,
            ActivityEventSourceType::GitHub => $this->github,
            ActivityEventSourceType::ClaudeCode => $this->claude_code,
            ActivityEventSourceType::Fswatch => $this->fswatch,
        };
    }
}
