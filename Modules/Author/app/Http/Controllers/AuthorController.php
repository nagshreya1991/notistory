<?php

namespace Modules\Author\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Author\Models\Author;
use Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Modules\Author\Services\AuthorService;
use Illuminate\Support\Facades\Auth;
use Modules\Author\Http\Requests\UpdateProfileRequest;
use Modules\Author\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;
use Modules\Author\Models\Skill;
use Modules\Story\Models\StoryTask;
use Modules\Story\Models\Story;
use Modules\Story\Models\StoryAssignee;
use Illuminate\Support\Facades\DB;
use Modules\Story\Models\Notification;



class AuthorController extends Controller
{
   
    protected $authorService;

    public function __construct(AuthorService $authorService)
    {
        $this->authorService = $authorService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('author::index');
    }
     /**
     * Get the profile of the authenticated author.
     *
     * @return JsonResponse
     */
    public function getProfile(): JsonResponse
    {
    $user = Auth::user();
    $author = $this->authorService->getAuthorProfileByUserId($user->id);

    if (!$author) {
        return response()->json([
            'status' => false,
            'message' => __('Author profile not found.'),
        ], 404);
    }

    // Check if the user is NOT authorized to view the profile
    if ($user->cannot('view', $author)) {
        return response()->json([
            'status' => false,
            'message' => __('You are not authorized to view this profile.'),
        ], 403);
    }

    return response()->json([
        'status' => true,
        'message' => __('Profile retrieved successfully.'),
        'data' =>$author,
    ]);
    }
   

      /**
     * Update the profile of the authenticated author.
     *
     * @param UpdateProfileRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request, $id): JsonResponse
    {
        $id = (int)$id; // Cast $id to integer
        $user = Auth::user();
        $validatedData = $request->validated();
    
        // Check if 'name' exists in validated data before updating
        if (isset($validatedData['name'])) {
            $user->update([
                'name' => $validatedData['name'],
            ]);
        }
    
        $author = $this->authorService->getAuthorProfileById($id);
    
        if (!$author) {
            return response()->json([
                'status' => false,
                'message' => __('Author profile not found.'),
            ], 404);
        }
    
        // Authorization check
        if ($user->cannot('update', $author)) {
            return response()->json([
                'status' => false,
                'message' => __('You are not authorized to update this profile.'),
            ], 403);
        }
    
        // Update author profile
        $updatedAuthor = $this->authorService->updateProfile($id, $validatedData);
    
        return response()->json([
            'status' => true,
            'message' => __('Profile updated successfully.'),
           'data' => $updatedAuthor->load('skills'),
            'user' => $user,
        ]);
    }
    
    /**
     * Handle the request to change the user's password.
     *
     * @param ChangePasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();
        // if ($user->cannot('changePassword', $user)) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'You are not authorized to change this password.',
        //     ], 403);
        // }

        $currentPassword = $request->input('current_password');
        $newPassword = $request->input('new_password');

        $passwordChanged = $this->authorService->changePassword($user, $currentPassword, $newPassword);

        if (!$passwordChanged) {
            return response()->json([
                'status' => false,
                'message' => __('The current password is incorrect.'),
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('Password updated successfully.'),
        ]);
    }
     /**
     * Retrieve a list of user names associated with a given skill ID.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

     public function skillList(Request $request)
    {
    
    $request->validate([
        'skill_id' => 'required|integer|exists:skills,id',
    ]);

   
    $authenticatedUserId = auth()->id();

    
    $authorIds = $this->authorService->getAuthorIdsBySkill($request->skill_id);

    
    $authorUserMapping = $this->authorService->getUserIdsByAuthorIds($authorIds)
        ->filter(function ($mapping) use ($authenticatedUserId) {
            return $mapping->user_id !== $authenticatedUserId;
        });

    
    $users = $authorUserMapping->map(function ($mapping) {
        $user = User::find($mapping->user_id);
        return [
            'author_id' => $mapping->author_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
        ];
    })->values()->toArray();

    
    return response()->json([
        'success' => true,
        'data' => $users, 
    ], 200);
   }


     /**
     * Get dashboard summary data.
     *
     * @return JsonResponse
     */
    public function getDashboardData(): JsonResponse
    {
      
        try {
           
            $response = $this->authorService->getDashboardData();
    
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
     * Get dashboard task lists .
     *
     * @return JsonResponse
     */
    public function getDashboardLists(): JsonResponse
    {
        try {
            $response = $this->authorService->getDashboardLists();

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

    public function storyList(Request $request)
    {
   
    $request->validate([
        'status' => 'required|integer',
    ]);

   
    $user = Auth::user();
    $author = Author::where('user_id', $user->id)->firstOrFail();

    
    $stories = $this->authorService->getStoriesByStatusAndAuthor($request->status, $author->id);

    return response()->json([
        'status' => true,
        'data' => $stories,
    ], 200);
   }

     /**
     * Update the IBAN for the authenticated author.
     */
    public function updateIban(Request $request)
    {
        $validatedData = $request->validate([
            'iban' => 'required|string|max:255',
        ]);

        // Pass the IBAN data to the service to handle the logic
        $author = $this->authorService->updateIban(auth()->user()->id, $validatedData['iban']);

        return response()->json([
            'message' => __('IBAN updated successfully.'),
            'author' => $author,
        ]);
    }

    public function allTask(Request $request): JsonResponse
   {
    try {
        // Get the current authenticated user
        $user = Auth::user();
        $author = Author::where('user_id', $user->id)->firstOrFail();
        $searchTerm = $request->input('search_term');
        $sortOrder = $request->input('sort_order', 'newest'); // Default to 'newest'
 
        $tasks = $this->authorService->getAllTasksForAuthor($author->id, $searchTerm, $sortOrder);
 

        return response()->json([
            'status' => true,
            'message' => __('Tasks retrieved successfully.'),
            'data' => $tasks,
        ], 200);
    } catch (Exception $e) {
        Log::error('Failed to retrieve tasks', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => false,
            'message' => __('An error occurred while retrieving tasks.'),
        ], 500);
    }
    }
    public function taskDetails(Request $request): JsonResponse
    {
    $request->validate([
        'task_id' => 'required|integer|exists:story_tasks,id', // Ensure task_id is valid
    ]);

    try {
        // Get the current authenticated user
        $user = Auth::user();
        $author = Author::where('user_id', $user->id)->firstOrFail();
        $taskId = $request->input('task_id');

        // Fetch task details for the specific author and task
        $taskDetails = $this->authorService->getTaskDetailsForAuthor($author->id, $taskId);

        return response()->json([
            'status' => true,
            'message' => __('Task details retrieved successfully.'),
            'data' => $taskDetails,
        ], 200);
    } catch (Exception $e) {
        Log::error('Failed to retrieve task details', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => false,
            'message' => __('An error occurred while retrieving task details.'),
        ], 500);
    }
   }
   public function storyDetails(Request $request)
   {
       $request->validate([
           'story_id' => 'required|integer|exists:stories,id',
       ]);
       $story = Story::findOrFail($request->story_id);
   
       $user = Auth::user();
       // if ($user->cannot('view', $story)) {
       //     abort(403, 'Unauthorized action.');
       // }
       
       // Retrieve story details as an array
       $storyDetails = $this->authorService->getStoryDetails($story);
       $defaultImageUrl = config('app.url') . 'storage/app/images/no-image.jpg';
   
       // Use array notation to set logo and cover URLs
       $storyDetails['logo'] = !empty($storyDetails['logo']) ? config('app.url') . 'storage/app/' . $storyDetails['logo'] : $defaultImageUrl;
       $storyDetails['cover'] = !empty($storyDetails['cover']) ? config('app.url') . 'storage/app/' . $storyDetails['cover'] : $defaultImageUrl;
   
       return response()->json([
           'success' => true,
           'data' => $storyDetails,
       ], 200);
   }
   public function getEarnings(Request $request)
    {
        $user = Auth::user();

        // Call the service method and pass the user and request parameters
        $response = $this->authorService->getEarnings($user, $request);

        return response()->json($response);
    }
    public function paymentHistory(Request $request)
    {
        $user = Auth::user();

        // Call the service method and pass the user and request parameters
        $response = $this->authorService->paymentHistory($user, $request);

        return response()->json($response);
    }

    public function getNotifications()
    {
        $user = Auth::user();
    $notifications = Notification::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $notifications,
    ], 200);
   }
   public function readNotifications(Request $request)
   {
    // Validate the notification_id
    $request->validate([
        'notification_id' => 'required|exists:notifications,id',
    ]);

    // Retrieve the notification
    $notification = Notification::findOrFail($request->notification_id);

    // Check if the notification is unread
    if ($notification->is_read == 0) {
        $notification->is_read = 1; // Mark as read
        $notification->save(); // Save the change
    }

    return response()->json([
        'success' => true,
        'message' => __('Notification marked as read successfully!'),
        'data' => $notification
    ], 200);
    }
    public function deleteNotification(Notification $notification)
    {
    // Ensure the notification belongs to the authenticated user
    if ($notification->user_id !== auth()->id()) {
        return response()->json([
            'success' => false,
            'message' => __('Unauthorized to delete this notification'),
        ], 403);
    }

    // Delete the notification
    $notification->delete();

    return response()->json([
        'success' => true,
        'message' => __('Notification deleted successfully!'),
    ], 200);
   }
}
