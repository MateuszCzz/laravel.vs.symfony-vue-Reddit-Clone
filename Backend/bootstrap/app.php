<?php

use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {

            // Check if the request is for the API
            if ($request->is('api/*') && $e->getMessage() === 'Unauthenticated.') {

                $token = $request->bearerToken();

                // Find the token using model
                $accessToken = $token ? PersonalAccessToken::findToken($token) : null;

                // Choose error message
                $message = match (true) {
                    !$token => 'Unauthenticated.',
                    !$accessToken => 'Unauthenticated.',
                    Carbon::parse($accessToken->expires_at)->isPast() => 'Unauthenticated - The token is expired.',
                    default => 'Unauthenticated.'
                };

                return response()->json([
                    'message' => $message
                ], 401);
            }

            return $e->render($request);
        });

        // Handle wrong ability exceptions
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            return $e->render($request);
        });

    })
    ->create();