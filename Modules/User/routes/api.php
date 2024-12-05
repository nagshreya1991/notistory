<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/



Route::middleware('auth:api')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::delete('{id}', [UserController::class, 'destroy']);
        Route::post('store-device-token', [UserController::class, 'storeDeviceToken']);
    });
});

Route::prefix('users')->group(function () {
    Route::post('send-notification', [UserController::class, 'sendNotification']);
    Route::post('register', [UserController::class, 'register']);
    Route::post('login', [UserController::class, 'login']);
    Route::post('email/verify', [UserController::class, 'verifyEmail']);
 //   Route::post('password/forgot', [UserController::class, 'forgotPassword']);
   // Route::post('password/reset', [UserController::class, 'resetPassword']);
    Route::post('forgot-password', [UserController::class, 'forgotPassword']);
    Route::post('verify-otp', [UserController::class, 'verifyOtp']);
    Route::post('reset-password', [UserController::class, 'resetPassword']);
});
