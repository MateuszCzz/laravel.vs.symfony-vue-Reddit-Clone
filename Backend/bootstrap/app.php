<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Exceptions\AuthenticationExceptionHandler;
use App\Exceptions\AccessDeniedExceptionHandler;
use Illuminate\Http\Request;

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

        // Handle lack or invalid tokens
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            $response = (new AuthenticationExceptionHandler)->handle($e, $request);
            return $response ?: $e->render($request);

        });

        // Handle wrong ability tokens
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            $response = (new AccessDeniedExceptionHandler)->handle($e, $request);
            return $response ?: $e->render($request);
        });
    })
    ->create();
