<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Services\AdminService;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Story\Models\Story;
use Modules\Story\Models\StoryAssignee;
use Modules\Story\Models\StoryPage;
use Modules\Story\Models\StoryTask;
use Modules\Story\Http\Requests\StoryApprovedRequest;
use Modules\Author\Models\Author;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\Helper;


class AdminController extends Controller
{
    protected AdminService $adminService;

    /**
     * AdminController constructor.
     *
     * @param AdminService $adminService
     */
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Display a listing of authors.
     *
     * @return JsonResponse
     */
    public function listAuthors(Request $request): JsonResponse
    {
       
        try {
            // Retrieve the search term
            $searchTerm = $request->input('search_term');
           // Log::info('Search Term:', ['searchTerm' => $searchTerm]);
    
            // Get authors with the search term
            $authors = $this->adminService->getAllAuthors($searchTerm);
            
            return response()->json($authors);
        } catch (Exception $e) {
            Log::error('Failed to retrieve stories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving authors.',
            ], 500);
        }
    }

    /**
     * Display the details of a specific author.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showAuthor(int $id): JsonResponse
    {
        try {
            $response = $this->adminService->getAuthorById($id);

            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('Failed to retrieve author', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving the author.',
            ], 500);
        }
    }

    /**
     * Display a listing of subscribers.
     *
     * @return JsonResponse
     */
    public function listSubscribers(Request $request): JsonResponse
    {
        try {
            $searchTerm = $request->input('search_term');
            $response = $this->adminService->getAllSubscribers($searchTerm);
    
            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('Failed to retrieve subscribers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving subscribers.',
            ], 500);
        }
    }

    /**
     * Display the details of a specific subscriber.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showSubscriber(int $id): JsonResponse
    {
        try {
            $response = $this->adminService->getSubscriberById($id);

            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('Failed to retrieve subscriber', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving the subscriber.',
            ], 500);
        }
    }

    /**
     * Display a listing of stories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listStories(Request $request): JsonResponse
    {
        try {
            $response = $this->adminService->getAllStories($request);

            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('Failed to retrieve stories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving stories.',
            ], 500);
        }
    }

    /**
     * Display the details of a specific story.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showStory(int $id): JsonResponse
    {
        try {
            $response = $this->adminService->getStoryById($id);

            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('Failed to retrieve story', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving the story.',
            ], 500);
        }
    }

    /**
     * Display a listing of notifications.
     *
     * @return JsonResponse
     */
    public function listNotifications(): JsonResponse
    {
        try {
            $response = $this->adminService->getAllNotifications();

            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('Failed to retrieve notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving notifications.',
            ], 500);
        }
    }

    public function assigneeList(Request $request)
    {
        $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
            'accept_status' => 'nullable|integer|in:0,1', 
        ]);

        $story = Story::findOrFail($request->story_id);
      
        $acceptStatus = $request->input('accept_status', null); // Defaults to null if not provided
    
        // Pass the accept_status to the service method
        $assigneeList = $this->adminService->getAssigneeList($story, $acceptStatus);

        return response()->json([
            'success' => true,
            'data' => $assigneeList,
        ], 200);
    }
    public function pageList(Request $request)
    {
       
        $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
        ]);

        $story = Story::findOrFail($request->story_id);
       
        $pages = $this->adminService->getPageList($story);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ], 200);
    }
    public function pageDetails(Request $request)
    {
       
        $request->validate([
            'page_id' => 'required|integer|exists:story_pages,id',
        ]);
        $storyPage = StoryPage::findOrFail($request->page_id);
        $pageDetails = $this->adminService->getPageDetails($storyPage);

        return response()->json([
            'success' => true,
            'data' => $pageDetails,
        ], 200);
    }

    public function updatePage(Request $request)
    {

        $storyPage = StoryPage::findOrFail($request->page_id);

        $storyPage->update([
            'content' => $request->page_content,
            'is_admin_edited' => true,
        ]);


        return response()->json(['success' => true, 'message' => 'Page updated successfully!', 'data' => $storyPage], 200);
    }
    /**
     * Retrieve the list of tasks associated with a specific story.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskList(Request $request)
    {
       
        $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
        ]);

        $story = Story::findOrFail($request->story_id);
        $tasks = $this->adminService->getTasksByStoryId($story->id);
       
        return response()->json([
            'success' => true,
            'data' => $tasks,
        ], 200);
    }
     /**
     * Retrieve the details of a specific task by its ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskDetails(Request $request)
    {
      
       $request->validate([
            'task_id' => 'required|integer|exists:story_tasks,id',
        ]);
        $task = StoryTask::findOrFail($request->task_id);
       
        $taskDetails = $this->adminService->getTaskDetailsById($task->id);
        return response()->json([
            'success' => true,
            'data' => $taskDetails,
        ], 200);
    }

     /**
     * Story Approved and reject by Admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storyApproved(Request $request)     //Story Approved and reject by Admin
   {
    $story = Story::findOrFail($request->story_id);
    $story->status = $request->status;
    if ($request->status == 1) {
        $story->approved_at = now();
        $story->status = 1;
        $author = Author::findOrFail($story->author_id);
        $message = __("Your story ':storyName' has been approved!", ['storyName' => $story->name]);
        Helper::sendNotification(
            $author->user_id,
            'StoryApproval',
            $message
        );
    }else {
        $story->approved_at = null;
        $story->is_launched = 0;
    }


    $story->save();

    return response()->json([
        'success' => true,
        'message' => 'Story approval status updated successfully!',
        'data' => $story
    ], 200);
  }
  public function rejected(Request $request)
   {
    $story = Story::findOrFail($request->story_id);
    $story->status = $request->status;
    if ($request->status == 0) {
        //$story->approved_at = now();
        $story->status = 2;
        $author = Author::findOrFail($story->author_id);
        Helper::sendNotification(
            $author->user_id,
            'StoryReject',
            "Your story '{$story->name}' has been rejected!"
        );
    }
    $story->save();

    return response()->json([
        'success' => true,
        'message' => 'Story reject successfully!',
        'data' => $story
    ], 200);
  }
  public function getStoriesByAuthor(int $authorId): JsonResponse
  {
    try {
        // Validate the author_id directly
        $this->validateAuthorId($authorId);

        // Fetch stories along with their assignees
        $stories = Story::leftJoin('story_assignees', 'stories.id', '=', 'story_assignees.story_id')
            ->where('story_assignees.author_id', $authorId)
            ->select([
                'stories.*',
                'story_assignees.id as assignee_id',
                'story_assignees.author_id',
                'story_assignees.role',
                'story_assignees.offer_type',
                'story_assignees.offer_amount',
                'story_assignees.accept_status',
                'story_assignees.created_at as assignee_created_at',
                'story_assignees.updated_at as assignee_updated_at',
            ])
            ->get();

        // Process each story to include formatted assignee details and status
        foreach ($stories as $story) {
            // Initialize variables for role details
            $author = '--';  // Default value if not available
            $illustrator = '--';  // Default value if not available
            $audioVideoCreator = '--';  // Default value if not available

            // Check the role and prepare the output accordingly
            if ($story->role === 'author') {
                // Author gets percentage
                $author = $story->offer_amount . '%'; // Assuming offer_amount is in percentage
            } elseif ($story->role === 'illustrator') {
                // Illustrator gets amount or percentage
                $illustrator = ($story->offer_type == 1) ?
                    $story->offer_amount :
                    $story->offer_amount . '%'; // If amount, you can just state 'Amount'
            } elseif ($story->role === 'audio_video_creator') {
                // Audio/Video Creator gets amount or percentage
                $audioVideoCreator = ($story->offer_type == 1) ?
                    $story->offer_amount :
                    $story->offer_amount . '%'; // If amount, you can just state 'Amount'
            }

            // Assign formatted details to the story object
            $story->author = $author;
            $story->illustrator = $illustrator;
            $story->audio_video_creator = $audioVideoCreator;

            // Add the status text based on the status value
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
        }

        // Return the formatted response
        return response()->json([
            'status' => true,
            'message' => 'Stories retrieved successfully.',
            'data' => $stories,
        ]);
    } catch (Exception $e) {
        Log::error('Failed to retrieve stories by author', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => false,
            'message' => 'An error occurred while retrieving stories.',
        ], 500);
    }
    }
    // Method to validate author_id
    protected function validateAuthorId($authorId)
    {
        if (!is_numeric($authorId)) {
            throw new \InvalidArgumentException('Author ID must be a number.');
        }
        
        // Optionally check if the author_id exists in the database
        if (!StoryAssignee::where('author_id', $authorId)->exists()) {
            throw new \InvalidArgumentException('Author ID does not exist.');
        }
    }

    public function editPercentage(int $author_id): JsonResponse
    {
        // Implement logic to retrieve and show the edit form if needed
    }

    public function updatePercentage(Request $request): JsonResponse
    {
        try {
            // Find the author by ID from the request
            $author = Author::findOrFail($request->input('author_id'));

            // Update the earning percentage
            $author->earning_percentage = $request->input('earning_percentage');
            $author->save();

            return response()->json([
                'status' => true,
                'message' => 'Earning percentage updated successfully.',
                'data' => $author, // Optional: return the updated author details
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the percentage.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

        public function checkMarked(Request $request)
    {
        $storyPage = StoryPage::where('story_id', $request->story_id)
                            ->where('id', $request->page_id)
                            ->first();

    
        if ($storyPage) {
            $storyPage->launch_status = $request->launch_status;
            $storyPage->save();

            return response()->json([
                'status' => true,
                'message' => 'Launch status updated successfully.',
                'data' => [
                    'story_id' => $storyPage->story_id,
                
                    'launch_status' => $storyPage->launch_status
                ]
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Story page not found.',
        ], 404);
    }

    public function getStoriesBySubscriber($subscriber_id): JsonResponse
    {
        try {
            $response = $this->adminService->getStoriesBySubscriber($subscriber_id);

            return response()->json($response, 200);
            } catch (Exception $e) {
            Log::error('Failed to retrieve stories for subscriber', [
                'subscriber_id' => $subscriber_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving stories for the subscriber.',
            ], 500);
            }
    } 


    /**
     * Get dashboard summary data.
     *
     * @return JsonResponse
     */
    public function getDashboardData(): JsonResponse
    {
      
        try {
           
            $response = $this->adminService->getDashboardData();
    
            return response()->json($response, 200);
        } catch (Exception $e) {
            Log::error('Failed to retrieve dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving dashboard data.',
            ], 500);
        }
    }

    /**
     * Get dashboard all lists .
     *
     * @return JsonResponse
     */
    public function getDashboardLists(): JsonResponse
    {
        try {
            $response = $this->adminService->getDashboardLists();

            return response()->json($response, 200);
        } catch (Exception $e) {
            Log::error('Failed to retrieve dashboard lists', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving dashboard lists.',
            ], 500);
        }
    }
    /**
     * Get dashboard Story Status .
     *
     * @return JsonResponse
     */
    public function getStoryStats()
    {
    try {
        // Fetch counts based on the provided conditions
        $launchedCount = Story::where('status', 1)
            ->where('is_launched', 1)
            ->count();

        $ongoingCount = Story::where('status', 1)
            ->count();

        $approvalCount = Story::whereNotNull('approved_at')
            ->count();

        // Return the results in a response
        return response()->json([
            'success' => true,
            'data' => [
                'launched_notistory' => $launchedCount,
                'ongoing_notistory' => $ongoingCount,
                'waiting_approval_notistory' => $approvalCount,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch story stats.',
            'error' => $e->getMessage(),
        ], 500);
    }
    }
      /**
     * Fetch finance details for authors.
     */
    public function finance(Request $request)
    {
        $searchTerm = $request->input('search_term');
        $filterMonth = $request->input('month');
        $filterYear = $request->input('year');

        $data = $this->adminService->getFinanceDetails($searchTerm, $filterMonth, $filterYear);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    public function generateBills(Request $request)
    {
        // Default to the current month and year if not provided
        $month = $request->input('month') ?: now()->month;
        $year = $request->input('year') ?: now()->year;
        $searchTerm = $request->input('search_term', ''); // Capture the search term
    
        // Delegate the bill generation to AdminService with search term
        $bills = $this->adminService->generateBills($month, $year, $searchTerm);
    
        return response()->json([
            'success' => true,
            'data' => $bills,
        ]);
    }
    
}
