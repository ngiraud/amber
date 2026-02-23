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
}
