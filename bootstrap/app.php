<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Authorization errors: flash a message and stay on the current page
        $exceptions->render(function (AuthorizationException $e, Request $request): RedirectResponse {
            Inertia::flash('error', 'You do not have permission to perform this action.');

            return back();
        });

        // Generic HTTP error handling by status code
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            $status = $response->getStatusCode();

            // CSRF expiry: flash and redirect back
            if ($status === 419) {
                Inertia::flash('error', 'The page expired, please try again.');

                return back();
            }

            // All other HTTP errors: show a dedicated error page (skip JSON responses)
            if ($status >= 400 && ! $request->expectsJson()) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
