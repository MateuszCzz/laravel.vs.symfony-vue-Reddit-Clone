<?php

use App\Enum\TokenAbility;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubredditController;
use Illuminate\Support\Facades\Route;

// Routes dealing with authentication
Route::prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        // Token creation routes:
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('refresh-token', 'refreshToken')->middleware(
            ['auth:sanctum', 'ability:' . TokenAbility::REFRESH_EXPIRATION->value]
        );

        // Token removal routes:
        Route::post('logout', 'logout')->middleware(
            ['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]
        );
        Route::post('logout-all', 'logoutAll')->middleware(
            ['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]
        );
        Route::post('logout-all-credentials', 'logoutAllCredentials');

        // Nickname routes:
        Route::post('generate-nickname', 'generateNickname');
        Route::get('check-nickname/{nickname}', 'checkNickname');
    });

Route::prefix('subreddits')
    ->controller(SubredditController::class)
    ->group(function () {
        Route::post('/', 'store')->middleware(
            ['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]
        );
    });
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })
