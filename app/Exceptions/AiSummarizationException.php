<?php

declare(strict_types=1);

namespace App\Exceptions;

use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use RuntimeException;
use Throwable;

class AiSummarizationException extends RuntimeException
{
    public static function fromAiException(AiException $e): self
    {
        $message = match (true) {
            $e instanceof RateLimitedException => 'API rate limit reached. Please try again later.',
            $e instanceof ProviderOverloadedException => 'The AI provider is currently overloaded. Please try again later.',
            default => self::messageFromGenericException($e),
        };

        return new self($message, previous: $e);
    }

    public static function fromUnexpected(Throwable $e): self
    {
        return new self('An unexpected error occurred while summarizing with AI.', previous: $e);
    }

    private static function messageFromGenericException(AiException $e): string
    {
        $raw = $e->getMessage();

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
