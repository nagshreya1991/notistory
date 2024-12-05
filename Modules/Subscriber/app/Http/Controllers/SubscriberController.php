<?php

namespace Modules\Subscriber\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Subscriber\Services\SubscriberService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Modules\User\Models\User;
use Modules\Story\Models\Story;
use Modules\Story\Models\StoryPage;
use Illuminate\Contracts\Validation\Validator;
use App\Mail\PasswordResetOtpMail;
use Modules\Subscriber\Http\Requests\ForgotPasswordRequest;
use Modules\Subscriber\Http\Requests\VerifyOtpRequest;
use Modules\Subscriber\Http\Requests\ResetPasswordRequest;
use Modules\Subscriber\Http\Requests\UpdateSubscriberRequest;
use Modules\Subscriber\Http\Requests\ChangeSubscriberPasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Modules\Story\Models\SubscriberStory;
use Modules\Subscriber\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\UserDeviceToken;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;
use Modules\Story\Models\StoryPurchase;



class SubscriberController extends Controller
{
    protected $subscriberService;
    protected $firebaseService;
    public function __construct(SubscriberService $subscriberService, FirebaseService $firebaseService)
    {
        $this->subscriberService = $subscriberService;
        $this->firebaseService = $firebaseService;
    }
  

    /**
     * Handle the forgot password request.
     * Send OTP to the user's email.
     * @param ForgotPasswordRequest $request
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $result = $this->subscriberService->handleForgotPassword($request->email);

        if (!$result['status']) {
            return response()->json([ 'status' => false,'message' => $result['message']], 404);
        }
        return response()->json([
            'status' => true,
            'message' => $result['message'],
            'otp' => $result['otp']  // Return OTP here for testing
        ],200);
    }
    /**
     * Handle the OTP verification request.
     *  @param VerifyOtpRequest $request
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $result = $this->subscriberService->verifyOtp($request->email, $request->otp);

        if (!$result['status']) {
            return response()->json([ 'status' => false,'message' => $result['message']], 400);
        }

        return response()->json([
            'status' => true,
            'message' => $result['message'],
        ],200);
    }
     /**
     * Handle the password reset request.
     * 
     *  * @param ResetPasswordRequest $request
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        
        $result = $this->subscriberService->resetPassword($request->email, $request->otp, $request->password);

        if (!$result['status']) {
            return response()->json(['status' => false,'message' => $result['message']], 400);
        }

        return response()->json(['status' => true,'message' => $result['message']],200);
    }
    /**
     * Get the subscriber's profile.
     */
    public function getProfile(): JsonResponse
    {
       
        $user = Auth::user(); // Get the currently authenticated user
        
        $subscriber = $this->subscriberService->getSubscriberProfileByUserId($user->id);

        if (!$subscriber) {
            return response()->json([
                'status' => false,
                'message' => __('Subscriber profile not found.'),
            ], 404);
        }

        if ($user->cannot('view', $subscriber)) {
            return response()->json([
                'status' => false,
                'message' => __('You are not authorized to view this profile.'),
            ], 403);
        }
        
        return response()->json([
            'status' => true,
            'message' => __('Profile retrieved successfully.'),
            'user' => $user,
            'data' => $subscriber,
        ]);
    }

    
    
     /**
     * Update the profile of the authenticated subscriber.
     *
     * @param UpdateSubscriberRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateSubscriberRequest $request, int $id): JsonResponse
    {
       
        $user = Auth::user();
        
        $validatedData = $request->validated();
    
        // Update the user's information
        $userUpdate = $user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ]);
        
        if (!$userUpdate) {
            return response()->json([
                'status' => false,
                'message' => __('Failed to update user information.'),
            ], 500);
        }
        $subscriber = $this->subscriberService->getSubscriberProfileById($id);
    
        if (!$subscriber) {
            return response()->json([
                'status' => false,
                'message' => __('Subscriber profile not found.'),
            ], 404);
        }
        if ($user->cannot('update', $subscriber)) {
            return response()->json([
                'status' => false,
                'message' => __('You are not authorized to update this profile.'),
            ], 403);
        }
    
        $updatedSubscriber = $this->subscriberService->updateProfile($id, $validatedData);
    
        return response()->json([
            'status' => true,
            'message' => __('Profile updated successfully.'),
            'data' => $updatedSubscriber,
            'user' => $user,
        ]);
    }
   
     /**
     * Handle the request to change the subscriber's password.
     *
     * @param ChangeSubscriberPasswordRequest $request
     * @return JsonResponse
     */
    public function changePassword(ChangeSubscriberPasswordRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        // Optional: Apply a policy check if needed
        // if ($user->cannot('changePassword', $user)) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'You are not authorized to change this password.',
        //     ], 403);
        // }

        $currentPassword = $request->input('current_password');
        $newPassword = $request->input('new_password');

        $passwordChanged = $this->subscriberService->changePassword($user, $currentPassword, $newPassword);

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
    * Handle the request to retrieve the list of  Story by status.
    *
    * @param Request $request
    * @return JsonResponse
   */
  public function storyList(Request $request)
  {
      $request->validate([
          'status' => 'required|integer',
          'search_term' => 'nullable|string|max:255',
          'page' => 'nullable|integer|min:1', // Optional page number, defaults to 1
          'per_page' => 'nullable|integer|min:1|max:100', // Optional items per page, defaults to 15
      ]);
  
      //$subscriber = auth()->user();
      $user = auth()->user();
      $subscriber = Subscriber::where('user_id', $user->id)->firstOrFail();
      $subscriber_id = $subscriber->id;
      
      // Get pagination parameters or set defaults
      $page = $request->input('page', 1);
      $perPage = $request->input('per_page', 2); // Default to 15 items per page
  
      // Fetch stories with pagination
      $stories = $this->subscriberService->getStoriesByStatus($request->status, $request->input('search_term'), $perPage, $page);
      
      // Transform stories into the desired format
      $formattedStories = $stories->map(fn($story) => array_merge(
          $story->toArray(),
          ['author_name' => optional($story->author?->user)->name ?? 'Unknown Author']
      ));
  
      // Fetch total count of stories with status = 1
      $totalStories = $this->subscriberService->countStoriesByStatus(1);
  
      $recentStories = $this->subscriberService->getRecentStories($request->status, 3);
        $formattedGalleries = $recentStories->map(function ($story) {
            return [
                'name' => $story['name'],
                'cover' => $story['cover'],
                'author_name' => $story['author_name'],
            ];
        });
  
        $newReleases = $this->subscriberService->getNewReleases();
        $continueReading = $this->subscriberService->getContinueReading($subscriber_id, 5);
        // Fetch purchased stories
        $purchasedStories = $this->subscriberService->getPurchasedStories($subscriber_id);

      return response()->json([
          'status' => true,
          'data' => [
              'stories' => $formattedStories,
              'galleries' => $formattedGalleries,
              'total_stories' => $totalStories,
              'new_releases' => $newReleases,
              'continue_reading' => $continueReading,
              'purchased_stories' => $purchasedStories, 
              
          ],
      ], 200);
  }

   /**
    * Handle the request to retrieve the details of a specific Story by story ID.
    *
    * @param Request $request
    * @return JsonResponse
   */
    public function storyDetails(Request $request)
    {
      $request->validate([
          'story_id' => 'required|integer',
      ]);
  
      // Get the authenticated subscriber
      $user = auth()->user();
      $subscriber = Subscriber::where('user_id', $user->id)->firstOrFail();
      $subscriber_id = $subscriber->id;
  
      // Fetch story details based on story ID and status
      $story = $this->subscriberService->getStoryDetailsByIdAndStatus($request->story_id, 1);
  
      if (!$story) {
          return response()->json([
              'status' => false,
              'message' => __('Story not found or unauthorized.'),
          ], 404);
      }
  
      // Check if the current subscriber is subscribed to the current story
      $isSubscribed = SubscriberStory::where('story_id', $request->story_id)
          ->where('subscriber_id', $subscriber_id)
          ->exists();

     $isPurchased = StoryPurchase::where('subscriber_id', $subscriber_id)
          ->where('story_id', $request->story_id)
          ->exists();
  
      return response()->json([
          'status' => true,
          'data' => [
              'story' => $story,
              'subscribed' => $isSubscribed, // true if subscribed, false otherwise
              'is_purchased' => $isPurchased, 
          ],
      ], 200);
    }
    /**
     * Handle the request to retrieve the list of a specific page by story ID.
     *
     * @param Request $request
     * @return JsonResponse
     */ 

     public function pageList(Request $request)
    {
    // Validate the incoming request
    $request->validate([
        'story_id' => 'required|integer',
    ]);

    // Get the currently authenticated user
    $user = auth()->user();
    $subscriber = Subscriber::where('user_id', $user->id)->firstOrFail();
    $subscriber_id = $subscriber->id;

    // Use SubscriberService to get the pages
    $pages = $this->subscriberService->getPagesByStoryId($request->story_id, $subscriber_id);

    // Check if pages are found
    if ($pages->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => __('No pages found for the subscriber.'),
        ], 404);
    }

    return response()->json([
        'status' => true,
        'data' => $pages,
    ], 200);
    }
     
    /**
     * Handle the request to retrieve the details of a specific page by ID and story ID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pageDetails(Request $request)
    {
    $request->validate([
        'story_id' => 'required|integer',
        'id' => 'required|integer', 
    ]);

    // Get page details along with the story name
    $page = $this->subscriberService->getPageDetailsByIdAndStoryId($request->id, $request->story_id);

    if (!$page) {
        return response()->json(['message' => __('Page not found for the provided story ID and page ID.')], 404);
    }

    return response()->json([
        'status' => true,
        'data' => [
            'page_id' => $page->id,
            'story_id' => $page->story_id,
            'story_name' => $page->story->name ?? 'Unknown Story', // Story name
            'page_number' => $page->page_number,
            'title' => $page->title,
            'pitch_line' => $page->pitch_line,
            'source' => $page->source,
            'author_note' => $page->author_note,
            'content' => $page->content,
            'status' => $page->status,
            'launch_status' => $page->launch_status,
            'launch_sequence' => $page->launch_sequence,
            'launch_time' => $page->launch_time,
            'author_name' => optional($page->author?->user)->name ?? 'Unknown Author', // Author details
            'created_at' => $page->created_at,
        ],
    ], 200);
    }

    public function storySubscribed(Request $request)
   {
    $user = Auth::user();
    $subscriber = Subscriber::where('user_id', $user->id)->firstOrFail();
    $subscriber_id = $subscriber->id;

    $request->validate([
        'story_id' => 'required|exists:stories,id',
    ]);

    $story = Story::findOrFail($request->story_id);
    $storyPages = StoryPage::where('story_id', $request->story_id)->get();

    $insertedData = [];
    $alreadySubscribed = false;

    foreach ($storyPages as $storyPage) {
        $existingSchedule = SubscriberStory::where('story_id', $request->story_id)
            ->where('story_page_id', $storyPage->id)
            ->where('subscriber_id', $subscriber_id)
            ->first();

        if ($existingSchedule) {
            $alreadySubscribed = true;
            break;
        }

        $launchStatus = $storyPage->launch_sequence == 0 ? 1 : 0;

        // Calculate scheduled date based on period and launch sequence
        $scheduledDate = Carbon::now();
        if ($launchStatus == 0) {
            switch ($story->period) {
                case 1: // Daily
                    $scheduledDate->addDays($storyPage->launch_sequence);
                    break;
                case 2: // Weekly
                    $scheduledDate->addWeeks($storyPage->launch_sequence);
                    break;
                case 3: // Monthly
                    $scheduledDate->addMonths($storyPage->launch_sequence);
                    break;
            }
        }

        // Parse launch_time and combine with calculated scheduledDate
        $launchTime = Carbon::parse($storyPage->launch_time);
        $scheduledTime = $scheduledDate->setTime(
            $launchTime->hour,
            $launchTime->minute,
            $launchTime->second
        );

        $newSchedule = SubscriberStory::create([
            'story_id' => $request->story_id,
            'story_page_id' => $storyPage->id,
            'subscriber_id' => $subscriber_id,
            'launch_status' => $launchStatus,
            'launch_sequence' => $storyPage->launch_sequence,
            'scheduled_time' => $scheduledTime,
            'period' => $story->period,
        ]);

        $insertedData[] = $newSchedule;
    }

    if ($alreadySubscribed) {
        return response()->json([
            'status' => false,
            'message' => __('Story pages are already subscribed to by the subscriber.'),
            'data' => $insertedData,
        ], 200);
    }

    // Send Notification to the Subscriber
    $deviceTokens = UserDeviceToken::where('user_id', $user->id)->pluck('device_token')->toArray();
    if (!empty($deviceTokens)) {
        $title = __('New Story Subscription');
        $body = __("You have successfully subscribed to the story '{$story->name}'!");
        $data = [
            'story_id' => $story->id,
            'subscriber_id' => $subscriber_id,
        ];

        if ($this->firebaseService->sendPushNotification($deviceTokens, $title, $body, $data)) {
            Log::info('Notification sent successfully.');
        } else {
            Log::warning('Failed to send notification.');
        }
    }

    return response()->json([
        'status' => true,
        'message' => __('Story pages successfully subscribed for the subscriber.'),
        'data' => $insertedData,
    ], 200);
   }

   public function launchStoryPages()
   {
       Log::info('Executing launchStoryPages...');

       // Find all subscriber stories with launch_status 0 and scheduled_time <= now
       $affectedRows = SubscriberStory::where('launch_status', 0)
           ->where('scheduled_time', '<=', Carbon::now())
           ->update(['launch_status' => 1]);

       Log::info("Updated $affectedRows subscriber story pages with launch_status 1.");
   }

    /**
     * Fetch content from the language JSON file.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLanguageContent(Request $request)
    {
        $locale = $request->get('locale', 'en'); // Default to 'en' if no locale is provided

        // Path to the JSON file
        $filePath = resource_path("lang/{$locale}.json");

        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json(['error' => 'Language file not found'], 404);
        }

        // Get the content of the file
        $content = File::get($filePath);
        $decodedContent = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON format'], 500);
        }

        return response()->json($decodedContent);
    }
    public function storyPurchase(Request $request)
   {
    // Validate the request
    $validated = $request->validate([
        'story_id' => 'required|exists:stories,id',
    ]);
   
    $story = Story::findOrFail($validated['story_id']);
    $amount = $story->price; // Retrieve the price from the story
    
    // Get the current subscriber ID
    $user = auth()->user();
    $subscriber = Subscriber::where('user_id', $user->id)->firstOrFail();
    $subscriber_id = $subscriber->id;

    // Check if the story is already purchased
    if ($this->subscriberService->isStoryAlreadyPurchased($subscriber_id, $validated['story_id'])) {
        return response()->json([
            'success' => false,
            'message' => __('You have already purchased this story.'),
        ], 400);
    }

    // Delegate the creation logic to the service
    $purchase = $this->subscriberService->createStoryPurchase($subscriber_id, $validated['story_id'], $amount);

    // Return success response
    return response()->json([
        'success' => true,
        'message' => __('Story purchased successfully.'),
        'data' => $purchase,
    ], 201);
  }
  public function purchaseHistory(Request $request)
  {
    // Get the current subscriber's ID
    $user = auth()->user();
    $subscriber = Subscriber::where('user_id', $user->id)->firstOrFail();
    $subscriberId = $subscriber->id;

    // Retrieve the purchase history for the current subscriber
    $purchases = $this->subscriberService->getPurchaseHistory($subscriberId);

    // Return the purchase history as JSON
    return response()->json([
        'status' => true,
        'data' => $purchases
    ], 200);
  }
}
