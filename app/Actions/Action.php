<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Fakeable;
use Illuminate\Container\Attributes\Singleton;

#[Singleton]
abstract class Action
{
    use Fakeable;

    public static function make(): static
    {
        return app(static::class);
    }
}
