<?php

namespace Modules\Story\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Story\Http\Requests\AddStoryRequest;
use Modules\Story\Http\Requests\AddTaskRequest;
use Modules\Story\Http\Requests\AddTaskCommentRequest;
use Modules\Story\Http\Requests\AssigneeDetailsRequest;
use Modules\Story\Http\Requests\UpdateTaskStatusRequest;
use Modules\Story\Http\Requests\UpdateStoryRequest;
use Modules\Story\Http\Requests\AddStoryPageRequest;
use Modules\Story\Http\Requests\AddPageFileRequest;
use Modules\Story\Http\Requests\AddAssigneeRequest;
use Modules\Story\Http\Requests\EditTaskRequest;
use Modules\Story\Http\Requests\EditStoryPageRequest;
use Modules\Story\Http\Requests\UpdateLaunchTimeRequest;
use Modules\Story\Http\Requests\StoryApprovedRequest;
use Modules\Story\Services\StoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Story\Models\Story;
use Modules\Story\Models\SubscriberStory;
use Modules\Author\Models\Author;
use Illuminate\Http\JsonResponse;
use Modules\User\Models\User;
use Modules\Story\Models\StoryTask;
use Modules\Story\Models\StoryPage;
use Carbon\Carbon;
use App\Helpers\Helper;


class StoryController extends Controller
{
    protected $storyService;

    public function __construct(StoryService $storyService)
    {
        $this->storyService = $storyService;
    }

    /**
     * Handles the creation of a new story.
     *
     * @param AddStoryRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function addStory(AddStoryRequest $request)
    {

        $user = Auth::user();
        $author = Author::where('user_id', $user->id)->firstOrFail();
        $author_id = $author->id;
        $story = new Story(['author_id' => $author_id, 'name' => $request->input('name'), //            'logo' => $request->file('logo'),
//            'cover' => $request->file('cover'),
//            'period' => $request->input('period'),
        ]);
        if ($user->cannot('create', $story)) {
            abort(403, __('Unauthorized action.'));
        }

        $story = $this->storyService->createStory($user, $request->all());

        return response()->json(['success' => true, 'data' => $story,], 200);
    }

    public function addAssignee(Request $request)
    {
        $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
            'author_id' => 'required|integer|exists:authors,id',
            'role' => 'required|string|in:illustrator,audio_video_creator',
            'offer_type' => 'required|integer|in:1,2', // 1 for Amount, 2 for Percentage
            'offer_amount' => 'required|numeric|min:0',]);

        $user = Auth::user();
        $author = Author::where('user_id', $user->id)->firstOrFail();
        $author_id = $author->id;
        $story = new Story([
            'author_id' => $author_id,
            'name' => $request->input('name'),
            'logo' => $request->file('logo'),
            'cover' => $request->file('cover'),
            'period' => $request->input('period')
        ]);

        // Authorization check using a policy
        if ($user->cannot('addAssignee', $story)) {
            return response()->json(['success' => false, 'message' =>  __('Unauthorized action.')], 403);
        }
        try {
            // Process the assignee through storyService
            $assignee = $this->storyService->addAssignee($request->all());

            return response()->json(['success' => true, 'data' => $assignee], 200);
        } catch (\Exception $e) {
            // Catch any exception thrown in the service and return it as a JSON response
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400); // You can adjust the status code as needed
        }
    }


    /**
     * Handles adding a new task to a story.
     *
     * @param AddTaskRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTask(AddTaskRequest $request)
    {

        $user = Auth::user();

        $story = Story::findOrFail($request->story_id);

        if ($user->cannot('createTask', $story)) {
            abort(403,  __('Unauthorized action.'));
        }

        $task = $this->storyService->createTask($request->all());
        //dd($task);
        return response()->json(['success' => true, 'data' => $task,], 201);
    }

    /**
     * Handles adding a new comment at task to a story.
     *
     * @param AddTaskCommentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTaskComment(AddTaskCommentRequest $request)
    {
        $user = Auth::user();
        $task = StoryTask::with('story')->findOrFail($request->input('story_task_id'));

        $story = Story::findOrFail($task->story->id);
        // Check if the user is authorized to add a comment to this task
        if ($user->cannot('addTaskComment', $story)) {
            abort(403,  __('Unauthorized action.'));
        }

        // Proceed with adding the task comment using StoryService
        $comment = $this->storyService->addTaskComment($user, $request->all());

        return response()->json(['success' => true, 'data' => $comment,], 201);
    }

    /**
     * Handles getting assignee details.
     *
     * @param AssigneeDetailsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assigneeDetails(AssigneeDetailsRequest $request)
    {
        $story = Story::findOrFail($request->story_id);

        $user = Auth::user();
        if ($user->cannot('viewAssigneeDetails', $story)) {
            abort(403,  __('Unauthorized action.'));
        }

        $assigneeDetails = $this->storyService->getAssigneeDetails($story, $request->author_id);

        return response()->json(['success' => true, 'data' => $assigneeDetails,], 200);
    }

    /**
     * Retrieve the list of tasks associated with a specific story.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskList(Request $request)
    {

        $request->validate(['story_id' => 'required|integer|exists:stories,id',]);

        $story = Story::findOrFail($request->story_id);

        $user = Auth::user();
        if ($user->cannot('viewTaskList', $story)) {
            abort(403,  __('Unauthorized action.'));
        }

        $tasks = $this->storyService->getTasksByStoryId($story->id);

        return response()->json(['success' => true, 'data' => $tasks,], 200);
    }

    /**
     * Retrieve the details of a specific task by its ID.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskDetails(Request $request)
    {
        $request->validate(['task_id' => 'required|integer|exists:story_tasks,id',]);
        $task = StoryTask::findOrFail($request->task_id);

        $story_id = $task->story_id;
        $story = Story::findOrFail($story_id);
        $user = Auth::user();
        if ($user->cannot('viewTaskDetails', $story)) {
            abort(403,  __('Unauthorized action.'));
        }
        $taskDetails = $this->storyService->getTaskDetailsById($task->id);
        return response()->json(['success' => true, 'data' => $taskDetails,], 200);
    }

    /**
     * Change Task Status.
     *
     * @param \Illuminate\Http\UpdateTaskStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function taskStatus(UpdateTaskStatusRequest $request)
    {
        $task = StoryTask::findOrFail($request->task_id);
        $story_id = $task->story_id;
        $story = Story::findOrFail($story_id);
        $user = Auth::user();
        if ($user->cannot('changeTaskStatus', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $updatedTask = $this->storyService->updateTaskStatus($task, $request->status);

        return response()->json(['success' => true, 'data' => $updatedTask,], 200);
    }

    public function storyDetails(Request $request)
    {
        $request->validate(['story_id' => 'required|integer|exists:stories,id',]);

        $story = Story::findOrFail($request->input('story_id'));

        $user = Auth::user();
        if ($user->cannot('view', $story)) {
            return response()->json(['success' => false, 'message' => __('Unauthorized action.')], 403);
        }

        $storagePath = config('app.url') . 'storage/app/';
        $defaultImageUrl = $storagePath . 'images/no-image.jpg';

        $story->logo = $story->logo ? $storagePath . $story->logo : $defaultImageUrl;
        $story->cover = $story->cover ? $storagePath . $story->cover : $defaultImageUrl;
        $periods = [1 => 'Daily', 2 => 'Weekly', 3 => 'Monthly'];
        $story->period_text = $periods[$story->period] ?? 'Unknown';
        switch ($story->status) {
            case 0:
                $story->status_text = 'Pending Approval';
                break;
            case 1:
                $story->status_text = $story->is_finished ? 'Finished' : ($story->is_launched ? 'Launched' : 'Approved');
                break;
            case 2:
                $story->status_text = 'Rejected';
                break;
            default:
                $story->status_text = 'Unknown Status';
                break;
        }

        return response()->json(['success' => true, 'data' => $story,], 200);
    }

    public function updateInfo(UpdateStoryRequest $request)
    {
        $data = $request->validated();
        $story = Story::findOrFail($request->story_id);
        $user = Auth::user();
        if ($user->cannot('update', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $updatedStory = $this->storyService->updateStoryInfo($story, $data);
        $defaultImageUrl = config('app.url') . 'storage/app/images/no-image.jpg';
        $updatedStory->logo = !empty($updatedStory->logo) ? config('app.url') . 'storage/app/' . $updatedStory->logo : $defaultImageUrl;
        $updatedStory->cover = !empty($updatedStory->cover) ? config('app.url') . 'storage/app/' . $updatedStory->cover : $defaultImageUrl;

        return response()->json(['success' => true, 'data' => $updatedStory,], 200);
    }

    public function assigneeList(Request $request)
    {
        $request->validate(['story_id' => 'required|integer|exists:stories,id', 'accept_status' => 'nullable|integer|in:0,1',]);

        $story = Story::findOrFail($request->story_id);
        $user = Auth::user();
        if ($user->cannot('viewAssigneeList', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $acceptStatus = $request->input('accept_status', null); // Defaults to null if not provided

        // Pass the accept_status to the service method
        $assigneeList = $this->storyService->getAssigneeList($story, $acceptStatus);

        return response()->json(['success' => true, 'data' => $assigneeList,], 200);
    }

    public function addPage(AddStoryPageRequest $request)
    {
        $user = Auth::user();
        $story = Story::findOrFail($request->story_id);

        if ($user->cannot('addPage', $story)) {
            abort(403, __('Unauthorized action.'));
        }

        $author = Author::where('user_id', $user->id)->firstOrFail();

        // Automatically calculate the next page number
        $latestPage = StoryPage::where('story_id', $request->story_id)->max('page_number');
        $pageNumber = $latestPage ? $latestPage + 1 : 1;  // Start from 1 if no pages exist

        // Set default status to 1 if not provided
        $data = $request->validated();
        $data['author_id'] = $author->id;
        $data['page_number'] = $pageNumber;
        $data['status'] = $data['status'] ?? 1;

        $page = $this->storyService->addPage($data, $author);

        return response()->json(['success' => true, 'message' => 'Page added successfully!', 'data' => $page], 201);
    }

    public function addPageFile(AddPageFileRequest $request)
    {

        $storyPage = StoryPage::findOrFail($request->story_page_id);
        $story = Story::findOrFail($request->story_id);

        $user = Auth::user();
        if ($user->cannot('addPageFile', $story)) {
            abort(403, __('Unauthorized action.'));
        }


        $pageFile = $this->storyService->addPageFile($request->all(), $storyPage);

        return response()->json(['success' => true, 'data' => $pageFile,], 200);
    }

    public function pageList(Request $request)
    {

        $request->validate(['story_id' => 'required|integer|exists:stories,id',]);

        $story = Story::findOrFail($request->story_id);
        $user = Auth::user();
        if ($user->cannot('viewPages', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $pages = $this->storyService->getPageList($story);

        return response()->json(['success' => true, 'data' => $pages,], 200);
    }

    public function pageDetails(Request $request)
    {

        $request->validate(['page_id' => 'required|integer|exists:story_pages,id',]);
        $storyPage = StoryPage::findOrFail($request->page_id);
        $story = Story::findOrFail($request->story_id);
        $user = Auth::user();
        if ($user->cannot('viewPageDetails', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $pageDetails = $this->storyService->getPageDetails($storyPage);

        return response()->json(['success' => true, 'data' => $pageDetails,], 200);
    }

    public function editTask(EditTaskRequest $request, StoryTask $task)
    {
        $user = Auth::user();
        $story = Story::findOrFail($task->story_id);

        if ($user->cannot('editTask', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $updatedTask = $this->storyService->updateTask($task, $request->all());

        return response()->json(['success' => true, 'data' => $updatedTask,], 200);
    }

    public function deleteTask(StoryTask $task)
    {
        $user = Auth::user();
        $story = Story::findOrFail($task->story_id);

        if ($user->cannot('deleteTask', $story)) {
            abort(403, __('Unauthorized action.'));
        }

        $this->storyService->deleteTask($task);

        return response()->json(['success' => true, 'message' => 'Task deleted successfully.',], 200);
    }

    /**
     * Fetch all comments for a specific task.
     *
     * @param int $story_task_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchTaskComments($story_task_id)
    {

        $user = Auth::user();
        $task = StoryTask::with('story')->findOrFail($story_task_id);
        $story = Story::findOrFail($task->story->id);


        if ($user->cannot('viewTaskComments', $story)) {
            abort(403, __('Unauthorized action.'));
        }


        $comments = $this->storyService->getTaskComments($story_task_id);

        return response()->json(['success' => true, 'data' => $comments,], 200);
    }

    public function editPage(EditStoryPageRequest $request)
    {
        $user = Auth::user();
        $storyPage = StoryPage::findOrFail($request->page_id);


        if ($user->cannot('editPage', $storyPage->story)) {
            abort(403, __('Unauthorized action.'));
        }

        $author = Author::where('user_id', $user->id)->firstOrFail();


        $updatedPage = $this->storyService->editPage($request->validated(), $storyPage, $author);

        return response()->json(['success' => true, 'message' => 'Page updated successfully!', 'data' => $updatedPage], 200);
    }

    /**
     * Handle the request to retrieve the list of  Story.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function storyList(Request $request)
    {
        try {
            $user = Auth::user();

            // Retrieve the author based on the user_id
            $author = Author::where('user_id', $user->id)->first();

            if (!$author) {
                return response()->json(['status' => false, 'message' => 'Unauthorized access.',], 403);
            }

            // Call the service to retrieve stories by status and author
            $response = $this->storyService->getStoriesByStatusAndAuthor($request, $author->id);


            return response()->json($response, 200);  // Use 200 for successful requests
        } catch (Exception $e) {
            Log::error('Failed to retrieve stories', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(),]);

            return response()->json(['status' => false, 'message' => __('An unexpected error occurred while retrieving stories.'),], 500);
        }
    }

    public function updateLaunchTime(UpdateLaunchTimeRequest $request)
    {
        $user = Auth::user();
        $author = Author::where('user_id', $user->id)->firstOrFail();
        $story = Story::findOrFail($request->story_id);

        $storyPage = StoryPage::where('id', $request->page_id)->where('story_id', $request->story_id)->firstOrFail();
        if ($user->cannot('updateLaunchTime', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $updatedPage = $this->storyService->updateLaunchTime($storyPage, $request->all());

        return response()->json(['success' => true, 'data' => $updatedPage,], 200);
    }

    public function pageLaunchConfiguration(Request $request)
    {

        $request->validate(['story_id' => 'required|integer|exists:stories,id',]);
        $story = Story::findOrFail($request->story_id);
        $user = Auth::user();
        if ($user->cannot('viewLaunchPages', $story)) {
            abort(403, __('Unauthorized action.'));
        }
        $pages = $this->storyService->getPageLaunchConfiguration($story);
        return response()->json(['success' => true, 'data' => $pages,], 200);
    }

    public function storyApproved(StoryApprovedRequest $request)
    {

        $user = Auth::user();
        $story = Story::findOrFail($request->story_id);

        if ($user->cannot('approveStory', $story)) {
            abort(403, __('Unauthorized action.'));
        }

        $story->is_launched = $request->is_launched;
        $story->approved_at = now();
        $story->save();

        return response()->json(['success' => true, 'message' => 'Story approval status updated successfully!', 'data' => $story], 200);
    }

    public function storyLaunched(Request $request)
    {
        // Validate the request data
        $request->validate(['story_id' => 'required|exists:stories,id', // Ensure the story exists
            'type' => 'required|in:submit,achieve' // Ensure type is either 'submit' or 'achieve'
        ]);

        // Find the story by ID
        $story = Story::findOrFail($request->story_id);

        // Handle based on the type
        if ($request->type === "submit") {
            // Check if the story's status is 0 (approved)
            if ($story->status === 0) {
                // Update the is_launched flag to 1 and set submitted_at
                 $story->is_launched = 1;
                $story->submitted_at = now();
                $story->save();
                Helper::sendNotification(
                    1,//admin
                    'StoryLaunched',
                    " '{$story->name}' story request for approve!"
                );

                return response()->json(['success' => true, 'message' => __('Story launched successfully!'), 'data' => $story,], 200);
            }

            return response()->json(['success' => false, 'message' => __('Story cannot be launched. Status is not approved.'), 'data' => $story,], 400);

        } elseif ($request->type === "achieve") {
            // Mark story as finished
            $story->is_finished = 1;
            $story->save();

            return response()->json(['success' => true, 'message' => __('Story marked as achieved successfully!'), 'data' => $story,], 200);
        } elseif ($request->type === "launched") {
            $story->is_launched = 1;
            $story->save();
            Helper::sendNotification(
                1,//admin
                'StoryLaunched',
                " '{$story->name}' story has been launched!"
            );
            return response()->json(['success' => true, 'message' => __('Story marked as launched successfully!'), 'data' => $story,], 200);

        }
    }

    public function createOrUpdatePage(Request $request)
   {
    $user = Auth::user();
    $data = $request->all();

    // Find the author associated with the current user
    $author = Author::where('user_id', $user->id)->firstOrFail();

    // Check if this is an update or create operation based on the presence of `page_id`
    if (isset($data['page_id'])) {
        // Update existing page
        $storyPage = StoryPage::findOrFail($data['page_id']);

        if ($user->cannot('editPage', $storyPage->story)) {
            abort(403, __('Unauthorized action.'));
        }

        $updatedPage = $this->storyService->createOrUpdatePage($data, $author, $storyPage);
        $message = __('Page updated successfully!');
    } else {
        // Add new page
        $story = Story::findOrFail($data['story_id']);

        if ($user->cannot('addPage', $story)) {
            abort(403, __('Unauthorized action.'));
        }

        // Automatically calculate the next page number
        $latestPage = StoryPage::where('story_id', $data['story_id'])->max('page_number');
        $data['page_number'] = $latestPage ? $latestPage + 1 : 1;
        $data['status'] = $data['status'] ?? 1;

        $newPage = $this->storyService->createOrUpdatePage($data, $author);
        $message = __('Page added successfully!');
    }

    return response()->json(['success' => true, 'message' => $message, 'data' => $updatedPage ?? $newPage], isset($data['page_id']) ? 200 : 201);
   }


}
