<?php

declare(strict_types=1);

namespace App\Events\Native;

class SwitchProjectFromMenu
{
    public function __construct(
        public readonly array $item = [],
        public readonly array $combo = [],
    ) {}
}
