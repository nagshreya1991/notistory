<?php 
 
namespace Modules\Subscriber\Services; 
 
use Modules\Subscriber\Models\Subscriber; 
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\User\Models\User; 
use App\Mail\PasswordResetOtpMail; 
use Illuminate\Support\Facades\Hash; 
use Modules\Story\Models\Story; 
use Modules\Story\Models\StoryPage; 
use Modules\Story\Models\SubscriberStory;
use Modules\Story\Models\StoryPurchase;
 
 
class SubscriberService 
{ 
    protected $subscriber; 
 
    public function __construct(Subscriber $subscriber) 
    { 
        $this->subscriber = $subscriber; 
    } 
 
    public function handleForgotPassword($email) 
    {

    $user = User::where('email', $email)->where('role', User::ROLE_SUBSCRIBER)->first();

    if (!$user) {
        return ['status' => false, 'message' => __('Subscriber not found.')];
    }

    $otp = mt_rand(1000, 9999);
    Cache::put('otp_' . $user->email, $otp, now()->addMinutes(10));

    try {
        Mail::to($user->email)->send(new PasswordResetOtpMail($otp));
    } catch (\Exception $e) {
        \Log::error('Failed to send OTP email to ' . $user->email . ': ' . $e->getMessage());
        return [
            'status' => false,
            'message' => __('Failed to send OTP email. Please try again later.')
        ];
    }

    return [
        'status' => true,
        'message' => __('OTP has been sent to your email address.'),
        'otp' => $otp  // For testing purposes
    ];
    }
    public function verifyOtp($email, $otp) 
    {  
        $cachedOtp = Cache::get('otp_' . $email); 
        if ($cachedOtp && $cachedOtp == $otp) { 
          return ['status' => true, 'message' => __('OTP verified successfully.')]; 
        } 
        return ['status' => false, 'message' => __('Invalid OTP or OTP has expired.')]; 
    } 
    public function resetPassword($email, $otp, $password) 
    { 
         
        $cachedOtp = Cache::get('otp_' . $email); 
 
        if (!$cachedOtp || $cachedOtp != $otp) { 
            return ['status' => false, 'message' => __('Invalid OTP or OTP has expired.')]; 
        } 
        $user = User::where('email', $email)->where('role', User::ROLE_SUBSCRIBER)->first(); 
 
        if (!$user) { 
            return ['status' => false, 'message' => __('Subscriber not found.')]; 
        } 
        $user->password = Hash::make($password); 
        $user->save(); 
        Cache::forget('otp_' . $email); 
 
        return ['status' => true, 'message' =>  __('Password has been reset successfully.')]; 
    } 
    public function getSubscriberProfileByUserId(int $userId) 
    { 
        return $this->subscriber->where('user_id', $userId)->first(); 
    } 
    public function getSubscriberProfileById(int $id) 
    { 
        return $this->subscriber->where('id', $id)->first(); 
    } 
     /** 
     * Update the subscriber's profile with new data. 
     * 
     * @param int $userId 
     * @param array $data 
     * @return Subscriber|null 
     */ 
    public function updateProfile(int $id, array $data) 
    { 
        $subscriber = $this->subscriber->where('id', $id)->first(); 
     
        if ($subscriber) { 
            $subscriber->update([ 
                
                'phone_number' => $data['phone_number'] ?? $subscriber->phone_number, 
                 
            ]); 
     
            if (isset($data['skills'])) { 
                $subscriber->skills()->sync($data['skills']); 
            } 
            return $subscriber; 
        } 
     
        return null; 
    } 
    /** 
     * Change the subscriber's password. 
     * 
     * @param User $user 
     * @param string $currentPassword 
     * @param string $newPassword 
     * @return bool 
     */ 
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool 
    { 
       
        if (!Hash::check($currentPassword, $user->password)) { 
            return false; 
        } 
        $user->password = Hash::make($newPassword); 
        $user->save(); 
 
        return true; 
    } 
     /** 
     * Retrieve the list of a story by status. 
     * 
     * @param int $status 
     */ 
    public function getStoriesByStatus(int $status, ?string $searchTerm = null, int $perPage = 15, int $page = 1)
    {
        $query = Story::where('status', $status)
            ->where('is_launched', 1);
    
        // If a search term is provided, apply the filter
        if ($searchTerm) {
            $query->where(function($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            });
        }
    
        // Get the results with pagination
        return $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }
    
    public function getRecentStories(int $status, int $limit)
    {
        // Fetch recent stories with name, cover, and author details
        return Story::where('status', $status)
        ->where('is_launched', 1)
        ->orderByDesc('id')
        ->limit($limit)
        ->with('author.user') // Eager load author and user relationships
        ->get(['id', 'name', 'cover', 'author_id']) // Include author_id for relation lookup
        ->map(function ($story) {
            return [
                'name' => $story->name,
                'cover' => $story->cover,
                'author_name' => $story->author->user->name ?? null, // Ensure author and user are available
            ];
        });
    }
    public function countStoriesByStatus(int $status)
    {
     // Count stories with the specified status
     return Story::where('status', $status)
        ->where('is_launched', 1)
        ->count();
    }
     /** 
     * Retrieve the details of a story by story Id and status. 
     * 
     * @param int $storyId 
     * @param int $status 
     */ 
    public function getStoryDetailsByIdAndStatus(int $storyId, int $status) 
    { 
        return Story::with(['author.user', 'assignees.author.user'])  
        ->where('id', $storyId) 
        ->where('status', $status) 
        ->where('is_launched', 1)
        ->first(); 
    } 
     /** 
     * Retrieve the pages of a specific story by story ID. 
     * 
     * @param int $storyId 
     */ 
   
    public function getPagesByStoryId(int $storyId, int $subscriberId)
    {
        // Fetch the story page IDs based on the subscriber's subscription and launch status
        $storyPageIds = SubscriberStory::where('story_id', $storyId)
        ->where('launch_status', 1)
        ->where('subscriber_id', $subscriberId)
        ->orderBy('launch_sequence', 'desc')
        ->pluck('story_page_id');

        // Fetch the StoryPages based on the retrieved IDs
        return StoryPage::whereIn('id', $storyPageIds)->get();
    }
     /** 
     * Retrieve the details of a specific page by ID and story ID. 
     * 
     * @param int $id 
     * @param int $storyId 
     */ 
    public function getPageDetailsByIdAndStoryId(int $id, int $storyId)
    {
        return StoryPage::with('story') // Eager load the story relationship
                        ->where('id', $id)
                        ->where('story_id', $storyId)
                        ->first();
    }

    public function getContinueReading(int $subscriberId)
    {
        $periods = [1 => 'Daily', 2 => 'Weekly', 3 => 'Monthly'];
    
        return Story::join('subscriber_stories', 'stories.id', '=', 'subscriber_stories.story_id')
            ->where('subscriber_stories.subscriber_id', $subscriberId)
            ->where('stories.is_launched', 1) // Ensure the story is launched
            ->select('stories.*', 'subscriber_stories.story_id') // Select all columns from stories and specific columns from subscriber_stories
            ->groupBy('subscriber_stories.story_id') // Group by story_id
            ->orderByDesc('stories.id') // Order by story ID in descending order
            ->with('author.user') // Ensure related author and user are eager-loaded
            ->get()
            ->map(function ($story) use ($periods) {
                return [
                    'id' => $story->id,
                    'name' => $story->name,
                    'logo' => $story->logo,
                    'cover' => $story->cover,
                    'number_of_pages' => $story->number_of_pages,
                    'price' => $story->price,
                    'minimum_age' => $story->minimum_age,
                    'period' => $story->period,
                    'period_text' => $periods[$story->period] ?? 'Unknown', // Translate period
                    'author_name' => optional($story->author->user)->name ?? 'Unknown Author', // Safely retrieve author name
                ];
            });
    }
    public function getNewReleases()
    {
        $periods = [1 => 'Daily', 2 => 'Weekly', 3 => 'Monthly'];
    
        return Story::where('is_launched', 1)
            ->orderByDesc('created_at') // Order by creation date in descending order
            ->with('author.user') // Eager load author and user relationships
            ->get()
            ->map(function ($story) use ($periods) { // Pass $periods into the closure
                return [
                    'id' => $story->id,
                    'name' => $story->name,
                    'logo' => $story->logo,
                    'cover' => $story->cover,
                    'number_of_pages' => $story->number_of_pages,
                    'price' => $story->price,
                    'minimum_age' => $story->minimum_age,
                    'period' => $story->period,
                    'period_text' => $periods[$story->period] ?? 'Unknown', // Translate period
                    'author_name' => optional($story->author->user)->name ?? 'Unknown Author', // Safely retrieve author name
                ];
            });
    }
    public function isStoryAlreadyPurchased(int $subscriberId, int $storyId): bool
    {
        return StoryPurchase::where('subscriber_id', $subscriberId)
            ->where('story_id', $storyId)
            ->exists();
    }

    // Create the story purchase record
    public function createStoryPurchase(int $subscriberId, int $storyId,  $amount)
    {
        
        return StoryPurchase::create([
            'story_id' => $storyId,
            'subscriber_id' => $subscriberId,
            'amount' => $amount,
        ]);
    }
    public function getPurchasedStories(int $subscriberId)
    {
    // Define period mapping
    $periods = [1 => 'Daily', 2 => 'Weekly', 3 => 'Monthly'];

    return StoryPurchase::where('subscriber_id', $subscriberId)
        ->with('story.author.user') // Eager load story, author, and user relationships
        ->get()
        ->map(function ($purchase) use ($periods) {
            $story = $purchase->story;

            return [
                'id' => $story->id,
                'name' => $story->name,
                'logo' => $story->logo,
                'cover' => $story->cover,
                'number_of_pages' => $story->number_of_pages,
                'price' => $story->price,
                'minimum_age' => $story->minimum_age,
                'period' => $story->period,
                'period_text' => $periods[$story->period] ?? 'Unknown', // Translate period
                'amount' => $purchase->amount,
                'author_name' => optional($story->author?->user)->name ?? 'Unknown Author', // Safely retrieve author name
            ];
        });
    }
    public function getPurchaseHistory(int $subscriberId)
    {
    return StoryPurchase::where('subscriber_id', $subscriberId)
        ->with('story') // Eager load the related story
        ->get()
        ->map(function ($purchase) {
            return [
                'story_name' => $purchase->story->name, // Get the story name
                'amount' => $purchase->amount,
                'purchase_date' => $purchase->created_at->toDateString(), // Purchase date in 'YYYY-MM-DD' format
                'author_name' => optional($purchase->story->author?->user)->name ?? 'Unknown Author', // Author name
                'cover' => $purchase->story->cover, // Story cover
            ];
        });
    }
     
} 
