<?php

declare(strict_types=1);

namespace App\Settings;

use App\Data\ActivitySourceConfigs\ClaudeCodeSourceConfig;
use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;
use App\Data\ActivitySourceConfigs\FswatchSourceConfig;
use App\Data\ActivitySourceConfigs\GeminiSourceConfig;
use App\Data\ActivitySourceConfigs\GitHubSourceConfig;
use App\Data\ActivitySourceConfigs\GitSourceConfig;
use App\Data\ActivitySourceConfigs\MistralVibeSourceConfig;
use App\Data\ActivitySourceConfigs\OpencodeSourceConfig;
use App\Enums\ActivityEventSourceType;
use App\Settings\Casts\SourceConfigCast;
use Illuminate\Support\Arr;
use Spatie\LaravelSettings\Settings;

class ActivitySourceSettings extends Settings
{
    public GitSourceConfig $git;

    public GitHubSourceConfig $github;

    public ClaudeCodeSourceConfig $claude_code;

    public GeminiSourceConfig $gemini;

    public MistralVibeSourceConfig $mistral_vibe;

    public OpencodeSourceConfig $opencode;

    public FswatchSourceConfig $fswatch;

    public static function group(): string
    {
        return 'activity_sources';
    }

    public static function casts(): array
    {
        return Arr::mapWithKeys(
            ActivityEventSourceType::cases(),
            fn (ActivityEventSourceType $type): array => [$type->value => new SourceConfigCast($type->configClass())],
        );
    }

    public function configFor(ActivityEventSourceType $type): SourceConfig
    {
        return $this->{$type->value};
    }

    /** @param array<string, mixed> $data */
    public function mergeConfig(ActivityEventSourceType $type, array $data): self
    {
        // @phpstan-ignore-next-line
        $this->{$type->value} = $type->configClass()::fromArray(array_merge(
            $this->configFor($type)->toArray(),
            $data,
        ));

        return $this;
    }

    /** @param array<string, mixed> $data */
    public function setConfig(ActivityEventSourceType $type, array $data): self
    {
        // @phpstan-ignore-next-line
        $this->{$type->value} = $type->configClass()::fromArray($data);

        return $this;
    }
}
