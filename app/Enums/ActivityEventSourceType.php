<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;

enum ActivityEventSourceType: string
{
    use EnhanceEnum;

    case Git = 'git';
    case ClaudeCode = 'claude-code';
    case Fswatch = 'fswatch';

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
}
