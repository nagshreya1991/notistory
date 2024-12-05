<?php

use Illuminate\Support\Facades\Route;
use Modules\Author\Http\Controllers\AuthorController;
use Modules\Author\Http\Middleware\AuthorMiddleware;

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

Route::prefix('author')->group(function () {
    // Route::post('skill-list', [AuthorController::class, 'skillList']);
});
Route::middleware(['auth:api', AuthorMiddleware::class])->group(function () {
    Route::prefix('author')->group(function () {
        Route::post('skill-list', [AuthorController::class, 'skillList']);
        Route::post('change-password', [AuthorController::class, 'changePassword']);
        Route::post('my-profile', [AuthorController::class, 'getProfile']);

        Route::post('dashboard', [AuthorController::class, 'getDashboardData']);
        Route::post('dashboard/lists', [AuthorController::class, 'getDashboardLists']);
        //  Route::post('{id}', [AuthorController::class, 'update']);
        Route::post('add-bio', [AuthorController::class, 'addBio']);
        Route::post('{id}', [AuthorController::class, 'update'])->where('id', '[0-9]+');
        Route::post('story-list', [AuthorController::class, 'storyList']);
        Route::post('update-iban', [AuthorController::class, 'updateIban']);
        Route::post('all-task', [AuthorController::class, 'allTask']);//For Assignee
        Route::post('task-details', [AuthorController::class, 'taskDetails']); //For Assignee
        Route::post('story-details', [AuthorController::class, 'storyDetails']); //For Assignee
        Route::post('earnings', [AuthorController::class, 'getEarnings']);
        Route::post('payment-history', [AuthorController::class, 'paymentHistory']);
        Route::get('notifications', [AuthorController::class, 'getNotifications']);
        Route::post('read-notifications', [AuthorController::class, 'readNotifications']);
        Route::delete('delete-notification/{notification}', [AuthorController::class, 'deleteNotification']);



    });
});

