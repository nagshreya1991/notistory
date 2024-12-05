<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Middleware\AdminMiddleware;

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

//Route::middleware(['auth:api', AdminMiddleware::class])->prefix('admin')->group(function () {
//
//    // Author Management Routes
//    Route::prefix('authors')->controller(AdminController::class)->group(function () {
//        Route::post('/', 'listAuthors')->name('admin.authors.index');                    // Retrieve a list of authors (with filters)
//        Route::get('{author}', 'showAuthor')->name('admin.authors.show');                  // Retrieve a specific author by ID
//        Route::get('{author}/stories', 'getStoriesByAuthor')->name('admin.authors.stories'); // Retrieve stories by a specific author
//        Route::post('{author}/percentage', 'editPercentage')->name('admin.authors.editPercentage'); // Edit the percentage for a specific author
//        Route::post('update_percentage', 'updatePercentage')->name('admin.authors.updatePercentage'); // Update percentage (consider a better route structure)
//        Route::post('check_marked', 'checkMarked')->name('admin.authors.checkMarked');     // Check marked authors
//    });
//});


Route::middleware(['auth:api', AdminMiddleware::class])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::post('dashboard', [AdminController::class, 'getDashboardData']);
        Route::post('dashboard/lists', [AdminController::class, 'getDashboardLists']);
        Route::get('dashboard/story-stats', [AdminController::class, 'getStoryStats']);
        // Routes for Authors
        Route::post('authors', [AdminController::class, 'listAuthors']);
        Route::get('authors/{id}', [AdminController::class, 'showAuthor']);
        Route::get('stories_by_author/{author_id}', [AdminController::class, 'getStoriesByAuthor']);
        Route::get('edit_percentage/{author_id}', [AdminController::class, 'editPercentage']);
        Route::post('update_percentage', [AdminController::class, 'updatePercentage']);
        Route::post('check_marked', [AdminController::class, 'checkMarked']);
        // Routes for Subscribers
        Route::post('subscribers', [AdminController::class, 'listSubscribers']);
        Route::get('stories_by_subscriber/{subscriber_id}', [AdminController::class, 'getStoriesBySubscriber']);
        Route::get('subscribers/{id}', [AdminController::class, 'showSubscriber']);

        // Routes for Stories
        Route::post('stories', [AdminController::class, 'listStories']);
        Route::get('stories/{id}', [AdminController::class, 'showStory']);
        Route::post('assignee-list', [AdminController::class, 'assigneeList']);
        Route::post('page-list', [AdminController::class, 'pageList']);
        Route::post('page-details', [AdminController::class, 'pageDetails']);
        Route::post('update-page', [AdminController::class, 'updatePage']);
        Route::post('task-list', [AdminController::class, 'taskList']);
        Route::post('task-details', [AdminController::class, 'taskDetails']);
        Route::post('story_approved', [AdminController::class, 'storyApproved']);
        Route::post('rejected', [AdminController::class, 'rejected']);
        Route::post('finance', [AdminController::class, 'finance']);
        Route::post('bills', [AdminController::class, 'generateBills']);

        // Routes for Notifications
        Route::get('notifications', [AdminController::class, 'listNotifications']);
    });
});
