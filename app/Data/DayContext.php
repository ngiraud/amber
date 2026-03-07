<?php

declare(strict_types=1);

namespace App\Data;

class DayContext
{
    public function __construct(
        /** @var list<string> Short identifiers: branch names, PR titles, etc. */
        public readonly array $labels,
        /** @var list<string> Detailed items: commit messages, Claude prompts, etc. */
        public readonly array $details,
        public readonly int $filesChanged,
    ) {}
}
