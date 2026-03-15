<?php

declare(strict_types=1);

namespace App\Services\ActivitySources\Concerns;

trait ResolvesHomePath
{
    protected function expandTilde(string $path): string
    {
        if (! str_starts_with($path, '~')) {
            return $path;
        }

        $home = $_SERVER['HOME'] ?? getenv('HOME');

        return $home.mb_substr($path, 1);
    }
}
