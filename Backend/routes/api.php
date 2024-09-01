<?php

use App\Enum\TokenAbility;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Routes dealing with authentication
Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('logout-all', 'logoutAll')->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);
        Route::post('logout', 'logout')->middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]);
        Route::post('generate-nickname', 'generateNickname');
        Route::get('check-nickname/{nickname}', 'checkNickname');
    });
});


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })
