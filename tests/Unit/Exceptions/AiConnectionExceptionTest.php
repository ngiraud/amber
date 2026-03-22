<?php

declare(strict_types=1);

use App\Exceptions\AiConnectionException;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;

pest()->group('exceptions');

describe('AiConnectionException', function () {
    describe('fromAiException', function () {
        it('maps RateLimitedException to a user-friendly message', function () {
            $e = RateLimitedException::forProvider('openai');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('API rate limit reached. Please try again later.')
                ->and($exception->getPrevious())->toBe($e);
        });

        it('maps ProviderOverloadedException to a user-friendly message', function () {
            $e = new ProviderOverloadedException('AI provider [anthropic] is overloaded.');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('The AI provider is currently overloaded. Please try again later.')
                ->and($exception->getPrevious())->toBe($e);
        });

        it('maps a 401 AiException to an invalid API key message', function () {
            $e = new AiException('401 Unauthorized');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('Invalid API key. Please check your AI settings.');
        });

        it('maps an "unauthorized" AiException to an invalid API key message', function () {
            $e = new AiException('Unauthorized access denied');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('Invalid API key. Please check your AI settings.');
        });

        it('maps an "invalid api key" AiException to an invalid API key message', function () {
            $e = new AiException('Invalid API Key provided');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('Invalid API key. Please check your AI settings.');
        });

        it('maps a 429 AiException to a rate limit message', function () {
            $e = new AiException('429 Too Many Requests');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('API rate limit reached. Please try again later.');
        });

        it('maps a 503 AiException to an overloaded message', function () {
            $e = new AiException('503 Service Unavailable');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('The AI provider is currently overloaded. Please try again later.');
        });

        it('maps an "overload" AiException to an overloaded message', function () {
            $e = new AiException('Provider is currently overloaded');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('The AI provider is currently overloaded. Please try again later.');
        });

        it('maps an unrecognized AiException to a generic provider error message', function () {
            $e = new AiException('Something went wrong');

            $exception = AiConnectionException::fromAiException($e);

            expect($exception->getMessage())->toBe('The AI provider returned an error. Please check your settings and try again.');
        });
    });

    describe('fromUnexpected', function () {
        it('wraps any throwable with a generic message', function () {
            $e = new RuntimeException('Connection refused');

            $exception = AiConnectionException::fromUnexpected($e);

            expect($exception->getMessage())->toBe('Unable to connect to the AI provider. Please check your settings.')
                ->and($exception->getPrevious())->toBe($e);
        });
    });

});
