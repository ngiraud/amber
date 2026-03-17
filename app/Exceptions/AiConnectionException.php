<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;
use Throwable;

class AiConnectionException extends RuntimeException
{
    public static function fromAiException(AiException $e): self
    {
        $message = match (true) {
            $e instanceof RateLimitedException => 'API rate limit reached. Please try again later.',
            $e instanceof ProviderOverloadedException => 'The AI provider is currently overloaded. Please try again later.',
            default => self::messageFromRaw($e->getMessage()),
        };

        return new self($message, previous: $e);
    }

    public static function fromUnexpected(Throwable $e): self
    {
        return new self('Unable to connect to the AI provider. Please check your settings.', previous: $e);
    }

    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $this->getMessage()], 422);
        }

        Inertia::flash('error', $this->getMessage());

        return back();
    }

    protected static function messageFromRaw(string $raw): string
    {
        if (str_contains($raw, '401') || str_contains(mb_strtolower($raw), 'unauthorized') || str_contains(mb_strtolower($raw), 'invalid api key')) {
            return 'Invalid API key. Please check your AI settings.';
        }

        if (str_contains($raw, '429')) {
            return 'API rate limit reached. Please try again later.';
        }

        if (str_contains($raw, '503') || str_contains(mb_strtolower($raw), 'overload')) {
            return 'The AI provider is currently overloaded. Please try again later.';
        }

        return 'The AI provider returned an error. Please check your settings and try again.';
    }
}
