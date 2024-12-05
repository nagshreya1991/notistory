<?php

use Illuminate\Support\Facades\Route;
use Modules\Story\Http\Controllers\StoryController;

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
    Route::prefix('story')->group(function () {
        Route::get('/', [StoryController::class, 'index']);
        Route::post('add-story', [StoryController::class, 'addStory']);//lang
        Route::post('add-assignee', [StoryController::class, 'addAssignee']);
        Route::post('add-task', [StoryController::class, 'addTask']);
        Route::delete('delete-task/{task}', [StoryController::class, 'deleteTask']);
        Route::post('edit-task/{task}', [StoryController::class, 'editTask']);
        Route::post('add-task-comment', [StoryController::class, 'addTaskComment']);
        Route::get('task-comments/{story_task_id}', [StoryController::class, 'fetchTaskComments']);
        Route::post('assignee-details', [StoryController::class, 'assigneeDetails']);
        Route::post('task-list', [StoryController::class, 'taskList']);
        Route::post('task-details', [StoryController::class, 'taskDetails']);
        Route::post('task-status', [StoryController::class, 'taskStatus']);
        Route::post('story-details', [StoryController::class, 'storyDetails']);
        Route::post('update-info', [StoryController::class, 'updateInfo']); //Update Story
        Route::post('assignee-list', [StoryController::class, 'assigneeList']);
        Route::post('add-page', [StoryController::class, 'addPage']);
        Route::post('edit-page', [StoryController::class, 'editPage']);
        Route::post('create-or-update-page', [StoryController::class, 'createOrUpdatePage']); //insteed of addPage & editPage//lang
        Route::post('add-page-file', [StoryController::class, 'addPageFile']);//
        Route::post('page-list', [StoryController::class, 'pageList']);//
        Route::post('page-details', [StoryController::class, 'pageDetails']);
        Route::post('story-list', [StoryController::class, 'storyList']);
        Route::post('update-launch-time', [StoryController::class, 'updateLaunchTime']);
        Route::post('page-launch-configuration', [StoryController::class, 'pageLaunchConfiguration']);
        Route::post('story_approved', [StoryController::class, 'storyApproved']);//admin
        Route::post('story_launched', [StoryController::class, 'storyLaunched']);
        
       // Route::post('set-launch-schedule', [StoryController::class, 'setLaunchSchedule']);
    });
});

