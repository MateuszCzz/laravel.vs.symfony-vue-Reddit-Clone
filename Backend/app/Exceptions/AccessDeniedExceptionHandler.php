<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Http\Request;

class AccessDeniedExceptionHandler
{
    public function handle(AccessDeniedHttpException $e, Request $request)
    {
        if ($request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }
    }
}
