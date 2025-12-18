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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

 Route::prefix('auth')->group(function () {
     Route::post('/register', [AuthController::class, 'register']);
     Route::post('/login', [AuthController::class, 'login']);
     Route::post('/refresh', [AuthController::class, 'refresh']);
     Route::post('/logout', [AuthController::class, 'logout']);

     Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
         ->middleware(['signed', 'throttle:6,1'])
         ->name('verification.verify');

     Route::middleware('auth:api')->group(function () {
         Route::get('/me', [AuthController::class, 'me']);
         Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
             ->middleware('throttle:6,1');
     });
 });
