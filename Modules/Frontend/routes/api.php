<?php

use Illuminate\Support\Facades\Route;
use Modules\Frontend\Http\Controllers\FrontendController;

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

// Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
//     Route::apiResource('frontend', FrontendController::class)->names('frontend');
// });
Route::prefix('frontend')->group(function () {
    Route::post('story-list', [FrontendController::class, 'storyList']);
    Route::post('create-testimonial', [FrontendController::class, 'createTestimonial']);
    Route::post('testimonial-list', [FrontendController::class, 'testimonialList']);
    Route::post('homepage-details', [FrontendController::class, 'getHomepageDetails']);
    Route::post('create-support', [FrontendController::class, 'createSupport']);
    Route::post('support-list', [FrontendController::class, 'supportList']);
    Route::post('get-page', [FrontendController::class, 'getPage']);
});



