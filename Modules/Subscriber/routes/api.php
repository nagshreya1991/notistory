<?php

use Illuminate\Support\Facades\Route;
use Modules\Subscriber\Http\Controllers\SubscriberController;
use Modules\Subscriber\Http\Middleware\SubscriberMiddleware;
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

Route::prefix('subscriber')->group(function () {
    Route::post('forgot-password', [SubscriberController::class, 'forgotPassword']);
    Route::post('verify-otp', [SubscriberController::class, 'verifyOtp']);
    Route::post('reset-password', [SubscriberController::class, 'resetPassword']);
    
   
});

 Route::middleware(['auth:api', SubscriberMiddleware::class])->group(function () {
        Route::prefix('subscriber')->group(function () {
        Route::post('story-list', [SubscriberController::class, 'storyList']);
        Route::post('story-details', [SubscriberController::class, 'storyDetails']);
        Route::post('page-list', [SubscriberController::class, 'pageList']);
        Route::post('page-details', [SubscriberController::class, 'pageDetails']);
        Route::post('story-subscribed', [SubscriberController::class, 'storySubscribed']);
        Route::post('story-purchase', [SubscriberController::class, 'storyPurchase']);
        Route::post('purchase-history', [SubscriberController::class, 'purchaseHistory']);
        Route::post('change-password', [SubscriberController::class, 'changePassword']);
        Route::post('my-profile', [SubscriberController::class, 'getProfile']);
        Route::post('{id}', [SubscriberController::class, 'update']);
    });
});
Route::get('subscriber/launch-story-pages', [SubscriberController::class, 'launchStoryPages']);
Route::get('subscriber/language-content', [SubscriberController::class, 'getLanguageContent']);
