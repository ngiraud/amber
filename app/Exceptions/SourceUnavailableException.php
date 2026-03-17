<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ActivityEventSourceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RuntimeException;

class SourceUnavailableException extends RuntimeException
{
    public function __construct(public readonly ActivityEventSourceType $source)
    {
        $instructions = $source->installationInstructions();
        $command = $instructions[0]['command'] ?? null;

        $message = Str::of("{$source->label()} is not available on this system.")
            ->when($command !== null, fn ($message) => $message->append(" Install with: {$command}"))
            ->toString();

        parent::__construct($message);
    }

    public function render(Request $request): RedirectResponse
    {
        Inertia::flash('error', $this->getMessage());

        return back();
    }
}
