<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\ActivitySource;
use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;

enum ActivityEventSourceType: string
{
    use EnhanceEnum;

    case Git = 'git';
    case ClaudeCode = 'claude-code';
    case Fswatch = 'fswatch';

    public function isEnabled(): bool
    {
        return config()->boolean("activity.sources.{$this->name}.enabled", false);
    }

    /** @return class-string<ActivitySource>|null */
    public function sourceClass(): ?string
    {
        return $this->guessActivitySource();
    }

    public function label(): string
    {
        return Str::headline($this->name);
    }

    public function color(): string
    {
        return match ($this) {
            self::Git => 'text-green-400',
            self::ClaudeCode => 'text-red-400',
            self::Fswatch => 'text-blue-400',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => Str::ucfirst($this->label()),
            'color' => $this->color(),
        ];
    }

    /** @return class-string<ActivitySource>|null */
    private function guessActivitySource(): ?string
    {
        $class = sprintf('App\\Services\\ActivitySources\\%sActivitySource', $this->name);

        return class_exists($class) ? $class : null;
    }
}
