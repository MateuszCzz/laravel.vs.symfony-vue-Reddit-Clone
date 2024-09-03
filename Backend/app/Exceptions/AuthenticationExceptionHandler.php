<?php

namespace App\Exceptions;

use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticationExceptionHandler
{
    public function handle(AuthenticationException $e, Request $request)
    {
        if ($request->is('api/*') && $e->getMessage() === 'Unauthenticated.') {

            $token = $request->bearerToken();

            // Find the token using model
            $accessToken = $token ? PersonalAccessToken::findToken($token) : null;

            // Choose error message
            $message = match (true) {
                !$token => 'Unauthenticated.',
                !$accessToken => 'Unauthenticated.',
                Carbon::parse($accessToken->expires_at)->isPast() => 'Unauthenticated - The token expired.',
                default => 'Unauthenticated.'
            };

            return response()->json([
                'message' => $message
            ], 401);
        }
    }
}
