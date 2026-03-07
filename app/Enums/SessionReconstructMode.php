<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\EnhanceEnum;
use Illuminate\Support\Str;

enum SessionReconstructMode: string
{
    use EnhanceEnum;

    case Gaps = 'gaps';
    case Replace = 'replace';

    public function label(): string
    {
        return Str::headline($this->name);
    }
}
