<?php

namespace Modules\Author\Services;

use Modules\Author\Models\Author;
use Modules\Author\Models\AuthorSkill;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Story\Models\Story;
use Modules\Subscriber\Models\Subscriber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Story\Models\StoryTask;
use Carbon\Carbon;
use Modules\Story\Models\StoryAssignee;
use Illuminate\Support\Facades\DB;



class AuthorService
{
    protected $author;

    public function __construct(Author $author)
    {
        $this->author = $author;
    }

  
    public function getAuthorProfileByUserId(int $userId)
    {
        return $this->author
        ->where('user_id', $userId)
        ->with('skills')
        ->with(['user' => function ($query) {
            $query->select('id', 'name', 'email', 'role'); // Get user details
        }])
        ->first();
    }
    public function getAuthorProfileById(int $id)
    {
        return $this->author->where('id', $id)->with('skills')->first();
    }
     
      /**
     * Update the author's profile with new data.
     *
     * @param int $userId
     * @param array $data
     * @return Author|null
     */
    public function updateProfile(int $id, array $data)
    {
        $author = $this->author->where('id', $id)->first();
    
        if ($author) {
            $author->update([
                'about' => $data['about'] ?? $author->about,
                'phone_number' => $data['phone_number'] ?? $author->phone_number,
                'case_keywords' => $data['case_keywords'] ?? $author->case_keywords,
                'portfolio_link' => $data['portfolio_link'] ?? $author->portfolio_link,
            ]);
    
           
            // if (isset($data['skills'])) {
            //     $author->skills()->sync($data['skills']);
            // }
            if (isset($data['skills'])) {
                \Log::info('Skills to sync: ', $data['skills']);
                $author->skills()->sync($data['skills']);
            }
            return $author;
        }
    
        return null;
    }
    /**
     * Change the user's password.
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
     * Get author IDs from the author_skills table by skill ID.
     *
     * @param int $skillId
     * @return \Illuminate\Support\Collection
     */
    public function getAuthorIdsBySkill($skillId)
    {
        return AuthorSkill::where('skill_id', $skillId)
            ->pluck('author_id');
    }

    /**
     * Get user IDs from the authors table by a collection of author IDs.
     *
     * @param \Illuminate\Support\Collection $authorIds
     * @return \Illuminate\Support\Collection
     */
    public function getUserIdsByAuthorIds($authorIds)
    {
        return Author::whereIn('id', $authorIds)->get(['id as author_id', 'user_id']);
    }
    /**
     * Get dashboard summary data.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        try {
            $user = auth()->user(); // Retrieve the currently authenticated author
            $author = Author::where('user_id', $user->id)->firstOrFail();
            $author_id = $author->id;
    
            // Get the story counts and total earnings
            $totalStoryCount = Story::where('author_id', $author_id)->count();
            $approvedStoryCount = Story::where('author_id', $author_id)->where('status', 1)->count();
            $waitingStoryCount = Story::where('author_id', $author_id)->where('status', 0)->count();
            $rejectedStoryCount = Story::where('author_id', $author_id)->where('status', 2)->count();
            $totalEarning = '00.00';
    
            return [
                'status' => true,
                'message' => 'Dashboard lists retrieved successfully.',
                'data' => [
                    'userName' => $user->name,
                    'totalStoryCount' => $totalStoryCount,
                    'approvedStoryCount' => $approvedStoryCount,
                    'waitingStoryCount' => $waitingStoryCount,
                    'rejectedStoryCount' => $rejectedStoryCount,
                    'totalEarning' => $totalEarning,
                ],
            ];
        } catch (ModelNotFoundException $e) {
            // Handle case where author is not found
            Log::error('Author not found', ['error' => $e->getMessage()]);
    
            return [
                'status' => false,
                'message' => 'Author not found.',
            ];
        } catch (Exception $e) {
            // Handle any other exceptions
            Log::error('Failed to retrieve dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return [
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving dashboard data.',
            ];
        }
    }
    public function getDashboardLists(): array
    {
        try {
            $user = auth()->user(); // Retrieve the currently authenticated author
            $author = Author::where('user_id', $user->id)->firstOrFail();
            $author_id = $author->id;
    
            // Retrieve the latest 5 tasks assigned to the author
            $tasks = StoryTask::where('assignee_id', $author_id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(['title', 'created_at', 'story_id']); // Include 'story_id' for fetching story name

            // Format the task list with human-readable created_at date and truncated title/story name
            $tasklist = $tasks->map(function ($task) {
            $storyName = Story::where('id', $task->story_id)->pluck('name')->first();
                return [
                    'title' => Str::limit($task->title, 20, '..'), // Limit title to 20 characters
                    'story_name' => Str::limit($storyName, 30, '..'), // Limit story name to 30 characters
                    'created_at' => $this->formatDate($task->created_at),
                ];
            });
    
            return [
                'status' => true,
                'message' => 'Dashboard task lists retrieved successfully.',
                'data' => [
                    'tasklist' => $tasklist,
                ],
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve dashboard task lists', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving dashboard task lists.',
                'data' => null,
            ];
        }
    }
    /**
     * Format the created_at date using diffForHumans() for human-readable format.
     *
     * @param Carbon $date
     * @return string
     */
    private function formatDate(Carbon $date): string
    {
    return $date->diffForHumans();  // This will output "Today", "Yesterday", "5 days ago", etc.
    }
    public function getStoriesByStatusAndAuthor(int $status, int $author_id)
   {
    
    $stories = Story::where('status', $status)
                    ->where('author_id', $author_id)
                    ->with('author.user') 
                    ->get();

  
    $defaultImageUrl = config('app.url') . '/storage/app/images/no-image.jpg';

   
    return $stories->map(function ($story) use ($defaultImageUrl) {
        $story->logo = !empty($story->logo) 
            ? config('app.url') . '/storage/app/' . $story->logo 
            : $defaultImageUrl;

        $story->cover = !empty($story->cover) 
            ? config('app.url') . '/storage/app/' . $story->cover 
            : $defaultImageUrl;

       
        $user = $story->author ? $story->author->user : null;
        $story->user_name = $user ? $user->name : 'Unknown';

        // Remove the author object for the response
        unset($story->author);

        return $story;
    });
   }

    /**
     * Update the IBAN for the specified author.
     *
     * @param int $userId
     * @param string $iban
     * @return Author
     */
    public function updateIban($userId, $iban)
    {
        // Find the author associated with the given user ID
        $author = Author::where('user_id', $userId)->first();

        if (!$author) {
            throw new \Exception('Author not found.');
        }

        // Update the IBAN
        $author->iban = $iban;
        $author->save();

        return $author;
    }

    public function getAllTasksForAuthor(int $authorId, ?string $searchTerm = null, string $sortOrder = 'newest'): array
    {
        // Base query to retrieve tasks
        $query = StoryTask::with(['story', 'assignee'])
            ->where('assignee_id', $authorId);

        // Add search filter if searchTerm is provided
        if ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply sorting based on sortOrder
        if ($sortOrder === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc'); // Default to newest
        }

        // Execute the query and map the results
        $tasks = $query->get()->map(function ($task) {
            return [
                'task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'created_at' => $this->formatDate1($task->created_at),
                'due_date' => $this->formatDate1($task->due_date),
                'attachment' => $task->attachment,
                'status' => $this->getStatusText($task->status),
                'story' => [
                    'name' => $task->story->name,
                    'logo' => $task->story->logo,
                    'status' => $task->story->status,
                    'active' => $task->story->active,
                ],
                'assignee' => [
                    'role' => $task->assignee->role,
                    'offer_type' => $task->assignee->offer_type,
                    'offer_amount' => $task->assignee->offer_amount,
                ],
            ];
        })->toArray();

        return $tasks;
    }



    /**
     * Get the textual representation of the task status.
     *
     * @param int $status
     * @return string
     */
    private function getStatusText(int $status): string
    {
        switch ($status) {
            case 0:
                return 'To do';
            case 1:
                return 'In Progress';
            case 2:
                return 'Feedback';
            case 3:
                return 'Complete';
            default:
                return 'Unknown Status';
        }
    }

        /**
     * Format the date to "Today", "Yesterday", or specific date.
     *
     * @param \Carbon\Carbon $date
     * @return string
     */
    private function formatDate1(string $date): string
    {
    // Convert the string date to a Carbon instance
    $dateInstance = Carbon::parse($date);

    if ($dateInstance->isToday()) {
        return 'Today';
    } elseif ($dateInstance->isYesterday()) {
        return 'Yesterday';
    } else {
        return $dateInstance->format('M d, Y'); // Format as "M d, Y"
    }
    }

    public function getTaskDetailsForAuthor(int $authorId, int $taskId): array
    {
        // Get the current authenticated user
        $currentUser = Auth::user();
    
        // Fetch the task with the story, author, and assignee details
        $task = StoryTask::with(['story.author.user', 'assignee'])
            ->where('id', $taskId)
            ->where('assignee_id', $authorId)
            ->firstOrFail();
    
        return [
            'task_id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'created_at' => $this->formatDate1($task->created_at),
            'due_date' => $this->formatDate1($task->due_date),
            'attachment' => $task->attachment,
            'status' => $this->getStatusText($task->status),
            'story' => [
                'story_id' => $task->story->id,
                'name' => $task->story->name,
                'assigner_name' => $task->story->author->user->name, // Assigner name from related User
                'logo' => $task->story->logo,
                'status' => $task->story->status,
                'active' => $task->story->active,
            ],
            'assignee' => [
                'name' => $currentUser->name, // Current user's name as the assignee
                'role' => $task->assignee->role,
                'offer_type' => $task->assignee->offer_type,
                'offer_amount' => $task->assignee->offer_amount,
            ],
        ];
    }
    public function getStoryDetails(Story $story)
    {
     // Format the story details and return as an array
      return [
        'id' => $story->id,
        'author_id' => $story->author_id,
        'name' => $story->name,
        'logo' => $story->logo,
        'cover' => $story->cover,
        'pitch' => $story->pitch,
        'number_of_pages' => $story->number_of_pages,
        'period' => $this->getPeriodText($story->period), // Convert period
        'status' => $this->getStoryStatusText($story->status), // Convert status
        'active' => $story->active,
        'is_launched' => $story->is_launched,
        'is_finished' => $story->is_finished,
        'submitted_at' => $story->submitted_at,
        'approved_at' => $story->approved_at,
        'created_at' => $story->created_at,
        'updated_at' => $story->updated_at,
     ];
    }
    private function getPeriodText(int $period): string
    {
     switch ($period) {
        case 1:
            return 'Daily';
        case 2:
            return 'Weekly';
        case 3:
            return 'Monthly';
        default:
            return 'Unknown'; // Fallback for unexpected values
    }
    }

    private function getStoryStatusText(int $status): string
    {
     switch ($status) {
        case 0:
            return 'Pending';
        case 1:
            return 'Approved';
        case 2:
            return 'Rejected';
        default:
            return 'Unknown'; // Fallback for unexpected values
     }
    }

    public function getEarnings($user, $request)
    {
        $author = Author::where('user_id', $user->id)->firstOrFail();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sortOrder = $request->input('sort_order', 'newest');
        $searchTerm = $request->input('search_term');

        $query = StoryAssignee::with('story')
            ->leftJoin('stories', 'story_assignees.story_id', '=', 'stories.id')
            ->leftJoin('story_purchases', 'story_purchases.story_id', '=', 'story_assignees.story_id')
            ->select(
                'story_assignees.story_id',
                'story_assignees.role',
                'story_assignees.offer_type',
                'story_assignees.offer_amount',
                'stories.name as story_name',
                DB::raw('COUNT(story_purchases.subscriber_id) as total_subscribers'),
                DB::raw('SUM(story_purchases.amount) as total_amount'),
                DB::raw('DATE(MAX(story_purchases.created_at)) as latest_purchase_date'),
                'story_purchases.status as purchase_status'
            )
            ->where('story_assignees.author_id', $author->id)
            ->groupBy(
                'story_assignees.story_id',
                'story_assignees.role',
                'story_assignees.offer_type',
                'story_assignees.offer_amount',
                'stories.name',
                'story_purchases.status'
            );

        // Apply date filtering
        if ($startDate) {
            $query->having(DB::raw('DATE(MAX(story_purchases.created_at))'), '>=', $startDate);
        }
        if ($endDate) {
            $query->having(DB::raw('DATE(MAX(story_purchases.created_at))'), '<=', $endDate);
        }

        // Apply search filtering for story names
        if ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->where('stories.name', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply sorting
        if ($sortOrder === 'oldest') {
            $query->orderBy(DB::raw('DATE(MAX(story_purchases.created_at))'), 'asc');
        } else {
            $query->orderBy(DB::raw('DATE(MAX(story_purchases.created_at))'), 'desc');
        }

        $assignedStories = $query->get();

        // Map and calculate total earnings
        $statusMapping = [1 => 'Paid', 2 => 'On Going', 3 => 'Awaiting'];
        $offerTypeMapping = [1 => 'Percentage', 2 => 'Amount'];
        $totalEarnings = 0;

        $transformedData = $assignedStories->map(function ($story) use ($statusMapping, $offerTypeMapping, &$totalEarnings) {
            $totalEarnings += $story->total_amount;
            return [
                'story_id' => $story->story_id,
                'role' => $story->role,
                'offer_type' => $story->offer_type,
                'offer_type_text' => $offerTypeMapping[$story->offer_type] ?? 'Unknown',
                'offer_amount' => $story->offer_amount,
                'story_name' => $story->story_name,
                'total_subscribers' => $story->total_subscribers,
                'total_amount' => $story->total_amount,
                'latest_purchase_date' => $story->latest_purchase_date,
                'purchase_status' => $story->purchase_status,
                'purchase_status_text' => $statusMapping[$story->purchase_status] ?? 'Awaiting',
            ];
        });

        return [
            'success' => true,
            'data' => $transformedData,
            'total_earnings' => $totalEarnings,
            'total_payments' => 0.00,
        ];
    }

    public function paymentHistory($user, $request)
    {
        $author = Author::where('user_id', $user->id)->firstOrFail();
    
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sortOrder = $request->input('sort_order', 'newest');
        $searchTerm = $request->input('search_term');
    
        // Fetch data with additional joins
        $query = StoryAssignee::with(['story.purchases'])
            ->leftJoin('stories', 'story_assignees.story_id', '=', 'stories.id')
            ->leftJoin('story_purchases', 'story_purchases.story_id', '=', 'story_assignees.story_id')
            ->leftJoin('subscribers', 'story_purchases.subscriber_id', '=', 'subscribers.id')
            ->leftJoin('users', 'subscribers.user_id', '=', 'users.id')
            ->select(
                'story_assignees.story_id',
                'story_assignees.role',
                'story_assignees.offer_type',
                'story_assignees.offer_amount',
                'stories.name as story_name',
                'story_purchases.subscriber_id',
                'story_purchases.amount',
                'story_purchases.created_at as purchase_date',
                'story_purchases.status as purchase_status',
                'users.name as subscriber_name' // Fetch subscriber's user name
            )
            ->where('story_assignees.author_id', $author->id);
    
        // Apply date filtering
        if ($startDate) {
            $query->where('story_purchases.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('story_purchases.created_at', '<=', $endDate);
        }
    
        // Apply search filtering for story names
        if ($searchTerm) {
            $query->where('stories.name', 'like', '%' . $searchTerm . '%');
        }
    
        // Apply sorting
        if ($sortOrder === 'oldest') {
            $query->orderBy('story_purchases.created_at', 'asc');
        } else {
            $query->orderBy('story_purchases.created_at', 'desc');
        }
    
        $records = $query->get();
    
        // Process data to calculate totals and structure output
        $statusMapping = [1 => 'Paid', 2 => 'On Going', 3 => 'Awaiting'];
        $offerTypeMapping = [1 => 'Percentage', 2 => 'Amount'];
        $totalEarnings = 0;
        $totalPayments = 0.00;
    
        $transformedData = $records->map(function ($record) use ($statusMapping, $offerTypeMapping, &$totalEarnings) {
            $totalEarnings += $record->amount ?? 0;
            return [
                'story_id' => $record->story_id,
                'role' => $record->role,
                'offer_type' => $record->offer_type,
                'offer_type_text' => $offerTypeMapping[$record->offer_type] ?? 'Unknown',
                'offer_amount' => $record->offer_amount,
                'story_name' => $record->story_name,
                'subscriber_id' => $record->subscriber_id,
                'subscriber_name' => $record->subscriber_name, // Include subscriber name
                'amount' => $record->amount,
                'purchase_date' => $record->purchase_date,
                'purchase_status' => $record->purchase_status,
                'purchase_status_text' => $statusMapping[$record->purchase_status] ?? 'Awaiting',
            ];
        });
    
        return [
            'success' => true,
            'data' => $transformedData,
            'total_earnings' => $totalEarnings,
            'total_payments' => $totalPayments,
        ];
    }
}
