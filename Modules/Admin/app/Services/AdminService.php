<?php

namespace Modules\Admin\Services;

use Illuminate\Http\Request;
use Modules\Author\Models\Author;
use Modules\Subscriber\Models\Subscriber;
use Modules\Story\Models\Story;
use Modules\Story\Models\StoryAssignee;
use Modules\Story\Models\StoryPage;
use Modules\Story\Models\StoryTask;
use Modules\Notification\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Story\Models\SubscriberStory;
use DB;

class AdminService
{
    /**
     * Retrieve all authors.
     *
     * @return array
     */
    
     public function getAllAuthors(string $searchTerm = null): array
     {
         try {
             // Start the query with eager loading and story count
             $query = Author::with(['user:id,name,email', 'skills:id,name'])
                            ->withCount('stories');
     
             // Apply the search term if provided
             if (!empty($searchTerm)) {
                 Log::info('Applying search filter...');
                 $query->where(function ($q) use ($searchTerm) {
                     $q->where('phone_number', 'like', '%' . $searchTerm . '%')
                       ->orWhere('case_keywords', 'like', '%' . $searchTerm . '%')
                       ->orWhere('portfolio_link', 'like', '%' . $searchTerm . '%')
                       ->orWhereHas('user', function ($q) use ($searchTerm) {
                           $q->where('name', 'like', '%' . $searchTerm . '%')
                             ->orWhere('email', 'like', '%' . $searchTerm . '%');
                       });
                 });
     
                 // Log the final SQL and bindings for debugging
                 Log::info('SQL Query:', [
                     'sql' => $query->toSql(),
                     'bindings' => $query->getBindings(),
                 ]);
             }
     
             // Get the authors with the search filter applied
             $authors = $query->get();
     
             // Format 'created_at' and map story count into the response
             $formattedAuthors = $authors->map(function ($author) {
                 return [
                     'id' => $author->id,
                     'user_id' => $author->user_id,
                     'phone_number' => $author->phone_number,
                     'case_keywords' => $author->case_keywords,
                     'portfolio_link' => $author->portfolio_link,
                     'about' => $author->about,
                     'iban' => $author->iban,
                     'earning_percentage' => $author->earning_percentage,
                     'created_at' => Carbon::parse($author->created_at)->format('M d, Y'),
                     'updated_at' => Carbon::parse($author->updated_at)->format('M d, Y'),
                     'user' => $author->user,
                     'skills' => $author->skills,
                     'story_count' => $author->stories_count,
                 ];
             });
     
             return [
                 'status' => true,
                 'message' => 'Authors retrieved successfully.',
                 'data' => $formattedAuthors,
             ];
         } catch (Exception $e) {
             Log::error('Failed to retrieve authors', [
                 'error' => $e->getMessage(),
                 'trace' => $e->getTraceAsString(),
             ]);
     
             return [
                 'status' => false,
                 'message' => 'An error occurred while retrieving authors. Please try again.',
                 'data' => null,
             ];
         }
     }

    /**
     * Retrieve an author by ID.
     *
     * @param int $id
     * @return array
     */
    public function getAuthorById(int $id): array
    {
        try {
            $author = Author::with('user:id,name,email')->where('id', $id)->first();

            return [
                'status' => true,
                'message' => 'Author retrieved successfully.',
                'data' => $author,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve author', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while retrieving the author. Please try again.',
                'data' => null,
            ];
        }
    }

    /**
     * Retrieve all subscribers.
     *
     * @return array
     */
    public function getAllSubscribers(?string $searchTerm = null): array
    {
        try {
            $currentDate = Carbon::now();
    
            // Retrieve subscribers with additional statistics, filtering by scheduled time and search term
            $subscribers = Subscriber::with('user:id,name,email')
                ->leftJoin('subscriber_stories', 'subscribers.id', '=', 'subscriber_stories.subscriber_id')
                ->select('subscribers.*')
                ->selectRaw('MIN(subscriber_stories.scheduled_time) as member_since')
                ->selectRaw('COUNT(DISTINCT CASE WHEN subscriber_stories.scheduled_time < ? THEN subscriber_stories.story_id END) as total_stories', [$currentDate])
                ->when($searchTerm, function ($query, $searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'LIKE', "%{$searchTerm}%")
                                      ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhere('subscribers.phone_number', 'LIKE', "%{$searchTerm}%");
                    });
                })
                ->groupBy('subscribers.id')
                ->get();
    
            // Format 'member_since' date for each subscriber
            $subscribers->transform(function ($subscriber) {
                $subscriber->member_since = $subscriber->member_since 
                    ? Carbon::parse($subscriber->member_since)->format('M d, Y') 
                    : null;
                return $subscriber;
            });
    
            return [
                'status' => true,
                'message' => 'Subscribers retrieved successfully.',
                'data' => $subscribers,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve subscribers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return [
                'status' => false,
                'message' => 'An error occurred while retrieving subscribers. Please try again.',
                'data' => null,
            ];
        }
    }

    /**
     * Retrieve a subscriber by ID.
     *
     * @param int $id
     * @return array
     */
    public function getSubscriberById(int $id): array
    {
        try {
            $subscriber = Subscriber::findOrFail($id);

            return [
                'status' => true,
                'message' => 'Subscriber retrieved successfully.',
                'data' => $subscriber,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve subscriber', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while retrieving the subscriber. Please try again.',
                'data' => null,
            ];
        }
    }

    /**
     * Retrieve all stories.
     *
     * @param Request $request
     * @return array
     */
    public function getAllStories(Request $request): array
    {
        try {
            // Retrieve the filters from the request
            $status = $request->input('status');
            $searchTerm = $request->input('search_term');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $sortOrder = $request->input('sort_order', 'newest'); // Default sort order is 'newest'

            // Status mapping based on your status column values
            $statusMapping = [
                'pending' => 0,       // Pending Approval
                'approved' => 1,      // Approved
                'rejected' => 2,      // Rejected
                'finished' => 1,      // Finished
                'launched' => 1,      // Launched
            ];

            // Build the query
            $query = Story::query()
                ->select([
                    'stories.id',
                    'stories.name',
                    'stories.number_of_pages',
                    'stories.status',
                    'stories.is_finished',
                    'stories.is_launched',
                    'stories.submitted_at',
                    'stories.approved_at',
                    'stories.updated_at',
                    'users.name as author_name',
                ])
                ->join('authors', 'stories.author_id', '=', 'authors.id')
                ->join('users', 'authors.user_id', '=', 'users.id');

            // First logic: Ensure `submitted_at` is not null
            $query->whereNotNull('stories.submitted_at');

            // Filter by status if provided
            if ($status && isset($statusMapping[$status])) {
                if ($status === 'pending') {
                    // Pending means status is 'Pending Approval'
                    $query->where('stories.status', 0);
                } elseif ($status === 'approved') {
                    // Approved means status is 'Approved'
                    $query->where('stories.status', 1)->where('stories.is_finished', 0)->where('stories.is_launched', 0);
                } elseif ($status === 'finished') {
                    // Finished
                    $query->where('stories.is_finished', 1);
                } elseif ($status === 'launched') {
                    // Launched
                    $query->where('stories.is_launched', 1);
                } elseif ($status === 'rejected') {
                    // Rejected
                    $query->where('stories.status', 2);
                }
            }

            // Filter by search term (assuming search is in title or content)
            if ($searchTerm) {
                $query->where('stories.name', 'like', '%' . $searchTerm . '%');
            }

            // Filter by start date and end date
            if ($startDate) {
                $query->whereDate('stories.submitted_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('stories.submitted_at', '<=', $endDate);
            }

            // Sort by newest or oldest
            if ($sortOrder === 'newest') {
                $query->orderBy('stories.submitted_at', 'desc');
            } else {
                $query->orderBy('stories.submitted_at', 'asc');
            }

            // Get the stories after applying the filters and sorting
            $stories = $query->get();

            // Fetch story assignees for each story
            foreach ($stories as $story) {
                // Get the story assignees
                $assignees = $story->assignees()->with('author')->get();

                // Initialize variables for role details
                $author = '--';  // Default value if not available
                $illustrator = '--';  // Default value if not available
                $audioVideoCreator = '--';  // Default value if not available

                // Iterate through assignees and format the details
                foreach ($assignees as $assignee) {
                    // Check the role and prepare the output accordingly
                    if ($assignee->role === 'author') {
                        // Author gets percentage
                        $author = $assignee->offer_amount . '%'; // Assuming offer_amount is in percentage
                    } elseif ($assignee->role === 'illustrator') {
                        // Illustrator gets amount or percentage
                        $illustrator = ($assignee->offer_type == 1) ?
                            $assignee->offer_amount :
                            $assignee->offer_amount . '%'; // If amount, you can just state 'Amount'
                    } elseif ($assignee->role === 'audio_video_creator') {
                        // Audio/Video Creator gets amount or percentage
                        $audioVideoCreator = ($assignee->offer_type == 1) ?
                            $assignee->offer_amount :
                            $assignee->offer_amount . '%'; // If amount, you can just state 'Amount'
                    }
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

                $story->submitted_at_formatted = Carbon::parse($story->submitted_at)->format('M d, Y');
                $story->updated_at_formatted = $story->updated_at ? Carbon::parse($story->updated_at)->format('M d, Y') : '--';
                $story->approved_at_formatted = $story->approved_at ? Carbon::parse($story->approved_at)->format('M d, Y') : '--';
            }

            return [
                'status' => true,
                'message' => 'Stories retrieved successfully.',
                'data' => $stories,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve stories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while retrieving stories. Please try again.',
                'data' => null,
            ];
        }
    }

    /**
     * Retrieve a story by ID.
     *
     * @param int $id
     * @return array
     */
    public function getStoryById(int $id): array
    {
        try {
            $story = Story::findOrFail($id);

            $storagePath = config('app.url') . 'storage/app/';
            $defaultImageUrl = $storagePath . 'images/no-image.jpg';

            $story->logo = $story->logo ? $storagePath . $story->logo : $defaultImageUrl;
            $story->cover = $story->cover ? $storagePath . $story->cover : $defaultImageUrl;
            $periods = [1 => 'Daily', 2 => 'Weekly', 3 => 'Monthly'];
            $story->period_text = $periods[$story->period] ?? 'Unknown';

            return [
                'status' => true,
                'message' => 'Story retrieved successfully.',
                'data' => $story,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve story', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while retrieving the story. Please try again.',
                'data' => null,
            ];
        }
    }



    /**
     * Retrieve all notifications.
     *
     * @return array
     */
    public function getAllNotifications(): array
    {
        try {
            $notifications = Notification::all();

            return [
                'status' => true,
                'message' => 'Notifications retrieved successfully.',
                'data' => $notifications,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while retrieving notifications. Please try again.',
                'data' => null,
            ];
        }
    }
    public function getAssigneeList(Story $story, $acceptStatus = null)
    {
        $query = StoryAssignee::where('story_id', $story->id);
    
        if (!is_null($acceptStatus)) {
            $query->where('accept_status', $acceptStatus);
        }
    
        $assignees = $query->get();
    
        return $assignees->map(function ($assignee) {
            $author = $assignee->author;
            $user = $author->user;
            $offerValue = ($assignee->offer_type == 1) ? 'Percentage' : 'Amount';
    
            return [
                'author_id' => $author->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role' => $assignee->role,
                'offer_type' => $offerValue,
                'offer_amount' => $assignee->offer_amount,
                'accept_status' => $assignee->accept_status,
                'iban' => $author->iban ?? null,  // Include 'iban' if it's not null
            ];
        });
    }
    public function getPageList(Story $story)
    {
        // Retrieve all pages for the given story along with launch-specific details
        $pages = StoryPage::where('story_id', $story->id)
            ->join('stories', 'stories.id', '=', 'story_pages.story_id')
            ->select(
                'story_pages.*',
                'stories.name as story_name',
                'stories.period',
                'stories.created_at as story_created_at',
                'stories.is_launched',
                'stories.submitted_at',
                'stories.approved_at',
                'stories.is_finished',

            )
          //  ->orderBy('story_pages.launch_sequence', 'asc')
            ->get();

        // Map period values to human-readable text
        $pages->transform(function ($page, $key) {
            $periods = [1 => 'Day', 2 => 'Week', 3 => 'Month'];
            $page->period_text = $periods[$page->period] ?? 'Unknown';
            if ($key === 0 && is_null($page->launch_time)) {
                $page->launch_time = 'just after showing interest';
            }
            return $page;
        });

        return $pages;
        //return StoryPage::where('story_id', $story->id)->get();
    }
    public function getPageDetails(StoryPage $storyPage)
    {
        return $storyPage;
    }

    /**
     * Retrieve all tasks associated with a specific story by its ID.
     *
     * @param int $storyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTasksByStoryId($storyId)
    {
        $tasks = StoryTask::leftJoin('story_assignees', 'story_tasks.assignee_id', '=', 'story_assignees.id')
            ->leftJoin('authors', 'story_assignees.author_id', '=', 'authors.id')
            ->leftJoin('users', 'authors.user_id', '=', 'users.id')
            ->leftJoin('stories', 'story_tasks.story_id', '=', 'stories.id') // Join the stories table
            ->where('story_tasks.story_id', $storyId)
            ->get([
                'story_tasks.*',
                'users.name as assignee_name',
                'story_assignees.role',
                'stories.name as story_name',   // Fetch story name
                'stories.logo as story_logo'    // Fetch story logo
            ]);

        // Map through the tasks and prepend the URL to the story_logo
        $defaultImageUrl = config('app.url') . '/storage/app/images/no-image.jpg';
        return $tasks->map(function ($task) use ($defaultImageUrl) {
            $task->story_logo = !empty($task->story_logo)
                ? config('app.url') . '/storage/app/' . $task->story_logo
                : $defaultImageUrl;

            return $task;
        });
    }
     /**
     * Retrieve the details of a specific task by its ID.
     *
     * @param int $taskId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTaskDetailsById($taskId)
    {
        return StoryTask::leftJoin('story_assignees', 'story_tasks.assignee_id', '=', 'story_assignees.id')
            ->leftJoin('authors', 'story_assignees.author_id', '=', 'authors.id')
            ->leftJoin('users', 'authors.user_id', '=', 'users.id')
            ->where('story_tasks.id', $taskId)
            ->first([
                'story_tasks.*',
                'users.name as assignee_name',
                'story_assignees.role'
            ]);
    }
    public function getStoriesBySubscriber($subscriber_id): array
    {
    try {
        $currentDate = Carbon::now();

        // Query to get stories with required details
        $stories = SubscriberStory::leftJoin('stories', 'subscriber_stories.story_id', '=', 'stories.id')
            ->where('subscriber_stories.subscriber_id', $subscriber_id)
            ->where('subscriber_stories.scheduled_time', '<', $currentDate)
            ->groupBy('subscriber_stories.story_id')
            ->select(
                'subscriber_stories.story_id',
                'stories.name as story_name',
                DB::raw('COUNT(subscriber_stories.story_page_id) as total_pages'),
                DB::raw('MAX(subscriber_stories.scheduled_time) as last_date')
            )
            ->get();

        // Format last_date to 'M d, Y' format
        $stories->transform(function ($story) {
            $story->last_date = $story->last_date 
                ? Carbon::parse($story->last_date)->format('M d, Y') 
                : null;
            return $story;
        });

        return [
            'status' => true,
            'message' => 'Stories retrieved successfully for subscriber.',
            'data' => $stories,
        ];
    } catch (Exception $e) {
        Log::error('Failed to retrieve stories for subscriber', [
            'subscriber_id' => $subscriber_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'status' => false,
            'message' => 'An error occurred while retrieving stories. Please try again.',
            'data' => null,
        ];
    }
   }

   public function getDashboardData(): array
   {
    try {
        // Count active authors
        $totalAuthors = Author::whereHas('user', function ($query) {
            $query->where('status', 1);
        })->count();

        // Count total stories
        $totalStories = Story::whereNotNull('submitted_at')->count();

        // Count active subscribers
        $totalSubscribers = Subscriber::whereHas('user', function ($query) {
            $query->where('status', 1);
        })->count();

        $totalRevenue = '00.00';
        $totalExpenditure = '00.00';

        return [
            'status' => true,
            'message' => 'Dashboard data retrieved successfully.',
            'data' => [
                'total_authors' => $totalAuthors,
                'total_stories' => $totalStories,
                'total_subscribers' => $totalSubscribers,
                'totalRevenue' => $totalRevenue,
                'totalExpenditure' => $totalExpenditure,
            ],
        ];
    } catch (Exception $e) {
        Log::error('Failed to retrieve dashboard data', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'status' => false,
            'message' => 'An error occurred while retrieving dashboard data. Please try again.',
            'data' => null,
        ];
    }
    }

    public function getDashboardLists(): array
   {
    try {
        // 1. List of Waiting for Approval stories
        $waitingApprovalStories = Story::with(['author.user:id,name'])
            ->where('status', 0)
            ->select('stories.name', 'stories.logo', 'stories.created_at', 'stories.author_id')
            ->get()
            ->map(function ($story) {
                return [
                    'name' => $story->name,
                    'logo' => config('app.url') . '/storage/app/' .$story->logo,
                    'created_at' => $story->created_at ? Carbon::parse($story->created_at)->format('M d, Y') : null,
                    'author_name' => $story->author->user->name ?? 'N/A',
                ];
            });
            
            $topSubscribedStories = Story::join('subscriber_stories', 'stories.id', '=', 'subscriber_stories.story_id')
            ->select(
                'stories.id',
                'stories.name',
                'stories.logo',
                'stories.period',
                'stories.number_of_pages'
            )
            ->selectRaw('COUNT(DISTINCT subscriber_stories.subscriber_id) as subscriber_count')
            ->groupBy('stories.id', 'stories.name', 'stories.logo', 'stories.period', 'stories.number_of_pages') // Grouping by relevant fields
            ->orderByDesc('subscriber_count')
            ->limit(10)
            ->get()
            ->map(function ($story) {
                return [
                    'story_id' => $story->id,
                    'name' => $story->name,
                    'logo' => config('app.url') . '/storage/app/' .$story->logo,
                    'period' => $story->period,
                    'number_of_pages' => $story->number_of_pages,
                    'subscriber_count' => $story->subscriber_count,
                ];
            });


            $topAuthors = Author::join('stories', 'authors.id', '=', 'stories.author_id')
            ->select(
                'authors.*'
             )
            ->selectRaw('COUNT(stories.id) as story_count')
            ->groupBy('authors.id') 
            ->orderByDesc('story_count')
            ->limit(10)
            ->get()
            ->map(function ($author) {
                return [
                    'author_id' => $author->id,
                    'author_name' => $author->user->name,
                    'story_count' => $author->story_count,
                ];
            });
    //dd( $topAuthors);
           

         return [
            'status' => true,
            'message' => 'Dashboard lists retrieved successfully.',
            'data' => [
                'waiting_approval_stories' => $waitingApprovalStories,
                'top_subscribed_stories' => $topSubscribedStories,
                'top_authors' => $topAuthors,
            ],
         ];
        } catch (Exception $e) {
        Log::error('Failed to retrieve dashboard lists', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'status' => false,
            'message' => 'An error occurred while retrieving dashboard lists. Please try again.',
            'data' => null,
        ];
    }
   }
   /**
     * Get finance details for authors.
     */
    public function getFinanceDetails($searchTerm, $filterMonth, $filterYear)
    {
    // Default to current month and year if not provided
    $filterMonth = $filterMonth ?: now()->month;
    $filterYear = $filterYear ?: now()->year;

    // Fetch authors and filter by search term on author name or stories
    $authors = Author::with(['user:id,name', 'skills:id,name'])
        ->when($searchTerm, function ($query) use ($searchTerm) {
            $query->where(function ($query) use ($searchTerm) {
                $query->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'like', "%$searchTerm%");
                })
                ->orWhereHas('stories', function ($storyQuery) use ($searchTerm) {
                    $storyQuery->where('name', 'like', "%$searchTerm%");
                });
            });
        })
        ->get()
        ->map(function ($author) use ($filterMonth, $filterYear) {
            return $this->getAuthorFinanceDetails($author, $filterMonth, $filterYear);
        });

    return $authors;
    }

    /**
     * Get financial details for a specific author.
     */
    private function getAuthorFinanceDetails($author, $filterMonth, $filterYear)
    {
    $query = StoryAssignee::with(['story.purchases'])
        ->leftJoin('stories', 'story_assignees.story_id', '=', 'stories.id')
        ->leftJoin('story_purchases', 'story_purchases.story_id', '=', 'story_assignees.story_id')
        ->leftJoin('subscribers', 'story_purchases.subscriber_id', '=', 'subscribers.id')
        ->select(
            'story_assignees.story_id',
            'story_assignees.role as role',
            'story_assignees.offer_type as offer_type',
            'story_assignees.offer_amount as offer_amount',
            'stories.name as story_name',
            'stories.price as story_price',
            'stories.updated_at as story_updated_at',
            DB::raw('COUNT(DISTINCT story_purchases.subscriber_id) as subscriber_count'),
            DB::raw('COUNT(story_purchases.id) as purchase_count')
        )
        ->where('story_assignees.author_id', $author->id)
        ->groupBy(
            'story_assignees.story_id',
            'story_assignees.role',
            'story_assignees.offer_type',
            'story_assignees.offer_amount',
            'stories.name',
            'stories.price',
            'stories.updated_at'
        );

    // Apply filters
    if ($filterMonth) {
        $query->whereMonth('stories.updated_at', $filterMonth);
    }

    if ($filterYear) {
        $query->whereYear('stories.updated_at', $filterYear);
    }

    $records = $query->get();

    // Process story details
    $storyDetails = $records->map(function ($record) {
        // Calculate earnings based on offer type
        $baseEarnings = $record->purchase_count * $record->story_price;

        $authorEarnings = match ($record->offer_type) {
            1 => ($record->offer_amount / 100) * $baseEarnings, // Percentage
            2 => $record->offer_amount * $record->purchase_count, // Flat amount
            default => 0,
        };

        return [
            'story_id' => $record->story_id,
            'story_name' => $record->story_name,
            'role' => $record->role,
            'offer_type' => $record->offer_type,
            'offer_amount' => $record->offer_amount,
            'updated_at' => Carbon::parse($record->story_updated_at)->format('M d, Y'),
            'story_price' => $record->story_price,
            'subscriber_count' => $record->subscriber_count,
            'purchase_count' => $record->purchase_count,
            'total_earnings' => number_format($authorEarnings, 2, '.', ''),
        ];
    });

    // Calculate totals
    $totalSubscribers = $records->sum('subscriber_count');
    $totalEarnings = $storyDetails->sum('total_earnings');

    // Return author details along with story details
    return [
        'author_id' => $author->id,
        'author_name' => $author->user->name,
        'author_skills' => $author->skills->pluck('name')->toArray(),
        'total_subscribers' => $totalSubscribers,
        'total_earnings' => number_format($totalEarnings, 2, '.', ''),
        'stories' => $storyDetails,
    ];
    }
    public function generateBills($month, $year, $searchTerm = '')
{
    // Format billing month
    $billingMonth = Carbon::createFromDate($year, $month, 1)->format('M, Y');

    // Build the query for authors
    $authorsQuery = Author::with(['stories.purchases' => function ($query) use ($month, $year) {
        $query->whereMonth('created_at', $month)
              ->whereYear('created_at', $year);
    }]);

    // Apply search term filters if provided
    if ($searchTerm) {
        $authorsQuery->where(function ($query) use ($searchTerm) {
            // Search by story name
            $query->whereHas('stories', function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            })
            // Or search by author's user name
            ->orWhereHas('user', function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    // Fetch authors and their related stories with purchases
    $authors = $authorsQuery->get();

    $bills = [];
    $billCounter = 1;

    foreach ($authors as $author) {
        // Filter stories with purchases
        $storiesWithPurchases = $author->stories->filter(function ($story) {
            return !$story->purchases->isEmpty();
        });

        if ($storiesWithPurchases->isEmpty()) {
            continue;
        }

        foreach ($storiesWithPurchases as $story) {
            // Get assignee details for each story
            $assigneeDetails = DB::table('story_assignees')
                ->where('story_id', $story->id)
                ->where('author_id', $author->id)
                ->select('role', 'offer_type', 'offer_amount')
                ->first();

            if (!$assigneeDetails) {
                continue;
            }

            // Calculate earnings
            $subscriberCount = $story->purchases->unique('subscriber_id')->count();
            $percent = $assigneeDetails->offer_type == 1 
                ? "{$assigneeDetails->offer_amount}%" 
                : "Fixed";

            $earning = 0;
            if ($assigneeDetails->offer_type == 1) { // Percentage
                $earning = (float)$story->price * ($assigneeDetails->offer_amount / 100) * $subscriberCount;
            } elseif ($assigneeDetails->offer_type == 2) { // Fixed Amount
                $earning = (float)$assigneeDetails->offer_amount * $subscriberCount;
            }

            // Force rounding and format for two decimal places
            $earning = number_format($earning, 2, '.', '');

            // Add the bill data
            $bills[] = [
                'bill_no' => sprintf('#%03d', $billCounter),
                'author_id' => $author->id,
                'author' => $author->user->name,
                'notistories' => $story->name,
                'author_role' => $assigneeDetails->role,
                'launched_on' => Carbon::parse($story->updated_at)->format('M d, Y'),
                'billing_month' => $billingMonth,
                'percent' => $percent,
                'subscriber_count' => $subscriberCount,
                'price' => number_format($story->price, 2, '.', ''),
                'earnings_price' => $earning, // Ensure this value is rounded
            ];
        }

        $billCounter++;
    }

    return $bills;
}

}
