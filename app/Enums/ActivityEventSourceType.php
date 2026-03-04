<?php

declare(strict_types=1);

namespace App\Enums;

use App\Data\ActivitySourceConfigs\Contracts\SourceConfig;
use App\Enums\Concerns\EnhanceEnum;
use App\Services\ActivitySources\Contracts\ActivitySource;
use App\Settings\ActivitySourceSettings;
use Illuminate\Support\Str;

enum ActivityEventSourceType: string
{
    use EnhanceEnum;

    case Git = 'git';
    case GitHub = 'github';
    case ClaudeCode = 'claude-code';
    case Fswatch = 'fswatch';

    public function config(): SourceConfig
    {
        return app(ActivitySourceSettings::class)->configFor($this);
    }

    public function isEnabled(): bool
    {
        return $this->config()->isEnabled();
    }

    /** @return class-string<ActivitySource>|null */
    public function sourceClass(): ?string
    {
        return $this->guessActivitySource();
    }

    public function label(): string
    {
        return Str::headline($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Git => 'text-green-400',
            self::GitHub => 'text-purple-400',
            self::ClaudeCode => 'text-red-400',
            self::Fswatch => 'text-blue-400',
        };
    }

    public function requirements(): string
    {
        return match ($this) {
            self::Git => 'Requires git — <code>brew install git</code>',
            self::GitHub => 'Requires GitHub CLI authenticated — <code>brew install gh && gh auth login</code>',
            self::ClaudeCode => 'Requires Claude Code CLI — <code>npm install -g @anthropic-ai/claude-code</code>',
            self::Fswatch => 'Requires fswatch — <code>brew install fswatch</code>',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => Str::ucfirst($this->label()),
            'color' => $this->color(),
            'requirements' => $this->requirements(),
        ];
    }

    /** @return class-string<ActivitySource>|null */
    private function guessActivitySource(): ?string
    {
        $class = sprintf('App\\Services\\ActivitySources\\%sActivitySource', $this->name);

        return class_exists($class) ? $class : null;
    }
}
