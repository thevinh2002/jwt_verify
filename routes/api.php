<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('jwt.auth')->get('/user', function (Request $request) {
    return $request->user();
});

 Route::prefix('auth')->group(function () {
     Route::post('/register', [AuthController::class, 'register']);
     Route::post('/login', [AuthController::class, 'login']);
     Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('jwt.auth');
     Route::post('/logout', [AuthController::class, 'logout']);
     Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1'); // 3 requests per minute
     Route::post('/reset-password', [AuthController::class, 'resetPassword']);

     Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
         ->middleware(['signed', 'throttle:6,1'])
         ->name('verification.verify');

     Route::middleware('jwt.auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1');
    });
 });
