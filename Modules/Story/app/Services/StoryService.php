<?php

namespace Modules\Story\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Author\Models\Author;
use Modules\Story\Models\Story;
use Modules\Story\Models\StoryAssignee;
use Modules\Story\Models\PageFile;
use Modules\Story\Models\StoryTask;
use Modules\Story\Models\TaskComment;
use Modules\Story\Models\StoryPage;
use Modules\User\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoryService
{
    /**
     * Creates a new story along with associated assignees.
     *
     * @param array $data
     * @return \Modules\Story\Models\Story
     */
    public function createStory($user, $data)
    {

        $author = Author::where('user_id', $user->id)->firstOrFail();
        $author_id = $author->id;
        $story = Story::create([
            'author_id' => $author_id,
            'name' => $data['name'],
            'period' => 2,
            'number_of_pages' => 0,
            'status' => 0,
            'active' => true,
        ]);

        $folderPath = 'uploads/stories/' . $story->id;

        // Check if the folder exists, if not, create the directory
//        if (!Storage::exists($folderPath)) {
//            Storage::makeDirectory($folderPath, 0755, true);
//            chmod(storage_path('app/' . $folderPath), 0755);
//        }
//
//        if (!empty($data['logo'])) {
//            $logoPath = $data['logo']->store($folderPath);
//            $story->logo = $logoPath;
//        }
//
//        if (!empty($data['cover'])) {
//            $coverPath = $data['cover']->store($folderPath);
//            $story->cover = $coverPath;
//        }

        $story->save();

        foreach ($data['assignees'] as $assignee) {
            // Check if the author_id exists in the authors table
            if ($assignee['author_id'] != '' && Author::where('id', $assignee['author_id'])->exists()) {
                StoryAssignee::create([
                    'story_id' => $story->id,
                    'author_id' => $assignee['author_id'],
                    'role' => $assignee['role'],
                    'offer_type' => $assignee['offer_type'],
                    'offer_amount' => $assignee['offer_amount'],
                ]);
            }
        }

        return $story;
    }

    public function addAssignee($data)
    {
        $story = Story::findOrFail($data['story_id']);

        // Retrieve existing assignees for this story
        $existingAssignees = StoryAssignee::where('story_id', $story->id)->get();
        $totalAssignees = $existingAssignees->count();

        // Check for existing roles to avoid duplicates
        $assignedRoles = $existingAssignees->pluck('role')->all();

//        if ($data['role'] === 'story_writer') {
//            throw new \Exception("A story writer already exists for this story.");
//        }
//
//        if ($data['role'] === 'illustrator' && in_array('illustrator', $assignedRoles)) {
//            throw new \Exception("An illustrator already exists for this story.");
//        }
//
//        if ($data['role'] === 'audio_video_creator' && in_array('audio_video_creator', $assignedRoles)) {
//            throw new \Exception("An audio/video creator already exists for this story.");
//        }

        // Calculate new distribution if adding an illustrator or AV creator with percentage offer type
        if ($data['offer_type'] == 1) { // Percentage
            $newRolePercentage = $data['offer_amount'];

            // Ensure the new role's percentage doesn't exceed 100%
            if ($newRolePercentage > 100) {
                throw new \Exception("The offer amount cannot exceed 100%");
            }

            // Calculate the total percentage already allocated to other roles, excluding the author
            $totalOtherRolesPercentage = StoryAssignee::where('story_id', $story->id)
                ->where('role', '!=', 'author')
                ->where('role', '!=', $data['role'])
                ->where('offer_type', 1)
                ->sum('offer_amount');

            // Calculate the new total if we add the new role's percentage
            $totalPercentage = $totalOtherRolesPercentage + $newRolePercentage;

            if ($totalPercentage > 100) {
                throw new \Exception("Total offer percentage cannot exceed 100%");
            }

            // Update the author's percentage to be the remaining percentage
            $authorPercentage = 100 - $totalPercentage;
            if ($authorPercentage == 0) {
                throw new \Exception("Author's offer percentage cannot be 0");
            }
            $authorAssignee = StoryAssignee::where('story_id', $story->id)
                ->where('role', 'author')
                ->where('offer_type', 1)
                ->first();

            if ($authorAssignee) {
                $authorAssignee->update(['offer_amount' => $authorPercentage]);
            }

            // Finally, set the new assignee's percentage
            $data['offer_amount'] = $newRolePercentage;
        }

        // Check if there's already an assignee with the specified role
        $existingAssignee = $existingAssignees->where('role', $data['role'])->first();

        // If an assignee with this role exists, update; otherwise, create new
        if ($existingAssignee) {
            $existingAssignee->update([
                'author_id' => $data['author_id'],
                'offer_type' => $data['offer_type'],
                'offer_amount' => $data['offer_amount'],
            ]);

            return $existingAssignee;
        } else {
            $assignee = StoryAssignee::create([
                'story_id' => $story->id,
                'author_id' => $data['author_id'],
                'role' => $data['role'],
                'offer_type' => $data['offer_type'],
                'offer_amount' => $data['offer_amount'],
            ]);

            return $assignee;
        }

    }

    /**
     * Creates a new task for a story.
     *
     * @param array $data
     * @return \Modules\Story\Models\StoryTask
     */
    public function createTask($data)
    {
        $task = StoryTask::create([
            'assignee_id' => $data['assignee_id'],
            'story_id' => $data['story_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'due_date' => $data['due_date'],
            //'status' => $data['status'], // Uncomment and handle if status is needed
        ]);


        if (isset($data['attachment'])) {

            $attachmentPath = $data['attachment']->store("uploads/stories/{$task->story_id}/tasks/");
            $task->attachment = $attachmentPath;
            $task->save();
        }

        return $task;
    }

    /**
     * Add a comment to a story task, including an  attachment.
     *
     * @param array $data
     */
    public function addTaskComment($user, $data)
    {
        $attachmentPath = null;
        if (isset($data['attachment'])) {
            $task = StoryTask::findOrFail($data['story_task_id']);
            $attachmentPath = $data['attachment']->store("uploads/stories/{$task->story_id}/tasks");
        }

        $taskComment = TaskComment::create([
            'story_task_id' => $data['story_task_id'],
            'author_id' => $data['author_id'],
            'comment' => $data['comment'],
            'attachment' => $attachmentPath,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return $taskComment;
    }

    /**
     * Retrieve the assignee details for a given story and author.
     *
     * @param Story $story The story for which assignee details are being retrieved.
     * @param int $authorId The ID of the author whose assignee details are to be fetched.
     */
    public function getAssigneeDetails(Story $story, $authorId)
    {

        $assignee = StoryAssignee::where('story_id', $story->id)
            ->where('author_id', $authorId)
            ->firstOrFail();


        $author = $assignee->author;
        $role = $assignee->role;
        $offerType = $assignee->offer_type;
        $offerAmount = $assignee->offer_amount;
        $user = $author->user;
        return [
            'author_id' => $author->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $role,
            'offer_type' => $offerType,
            'offer_amount' => $offerAmount,
        ];
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

    public function updateTaskStatus(StoryTask $task, $status)
    {
        $task->status = $status;
        $task->save();

        return $task;
    }

    public function getStoryDetails(Story $story)
    {
        return $story;
    }

    public function updateStoryInfo(Story $story, array $data)
    {
        if (isset($data['pitch'])) {
            $story->pitch = $data['pitch'];
        }if (isset($data['minimum_age'])) {
            $story->minimum_age = $data['minimum_age'];
        }if (isset($data['price'])) {
            $story->price = $data['price'];
        }if (isset($data['period'])) {
            $story->period = $data['period'];
        }

        $folderPath = 'uploads/stories/' . $story->id;

        // Check if the folder exists, if not, create the directory
        if (!Storage::exists($folderPath)) {
            Storage::makeDirectory($folderPath, 0755, true);
        }

        if (isset($data['logo'])) {
            $logoPath = $data['logo']->store($folderPath);
            $story->logo = $logoPath;
            @chmod(dirname($logoPath), 0755);
        }
        if (isset($data['cover'])) {
            $coverPath = $data['cover']->store($folderPath);
            $story->cover = $coverPath;
            @chmod(dirname($coverPath), 0755);
        }
        $story->save();

        return $story;
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
            ];
        });
    }

    public function addPage(array $data, $author)
   {
    // Assign the author
    $data['author_id'] = $author->id;

    // Create the new story page
    $page = StoryPage::create($data);

    // Increment the number_of_pages for the story
    $story = Story::find($data['story_id']);
    if ($story) {
        $story->increment('number_of_pages');
    }

    return $page;
   }

    public function addPageFile(array $data, StoryPage $storyPage)
    {
        $filePath = $data['file']->store('uploads/stories/' . $storyPage->story_id . '/pages/' . $storyPage->id);

        $pageFile = PageFile::create([
            'author_id' => $storyPage->author_id,
            'story_id' => $storyPage->story_id,
            'story_page_id' => $storyPage->id,
            'file' => $filePath,
        ]);

        return $pageFile;
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
                'stories.minimum_age',
                'stories.number_of_pages',
                'stories.price',
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

    public function updateTask($task, $data)
    {
        // Update task fields
        $task->update([
            'assignee_id' => $data['assignee_id'] ?? $task->assignee_id,
            'title' => $data['title'] ?? $task->title,
            'description' => $data['description'] ?? $task->description,
            'due_date' => $data['due_date'] ?? $task->due_date,
            //'status' => $data['status'] ?? $task->status, // Uncomment if status is needed
        ]);

        // Handle attachment update
        // if (isset($data['attachment'])) {
        //     // Optionally, delete old attachment if one exists
        //     if ($task->attachment) {
        //         \Storage::delete($task->attachment);
        //     }

        //     // Store the new attachment
        //     $attachmentPath = $data['attachment']->store("uploads/stories/{$task->story_id}/tasks/");

        //     $task->attachment = $attachmentPath;
        //     $task->save();
        // }

        return $task;
    }

    public function deleteTask(StoryTask $task)
    {
        // If the task has an attachment, delete it
        if ($task->attachment) {
            \Storage::delete($task->attachment);
        }

        // Delete the task
        return $task->delete();
    }

    /**
     * Get all comments for a specific task.
     *
     * @param int $story_task_id
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTaskComments($story_task_id)
    {
        return TaskComment::where('story_task_id', $story_task_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($comment) {
                // Retrieve the author from the authors table
                $author = Author::find($comment->author_id);

                // Retrieve the user based on the author's associated user_id
                $user = $author ? User::find($author->user_id) : null;

                // Set the author_name to the user's name or null if not found
                $author_name = $user ? $user->name : null;

                return [
                    'id' => $comment->id,
                    'story_task_id' => $comment->story_task_id,
                    'author_id' => $comment->author_id,
                    'author_name' => $author_name, // Include the author's associated user name
                    'comment' => $comment->comment,
                    'attachment' => $comment->attachment,
                    'parent_id' => $comment->parent_id,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];
            });
    }

    public function editPage(array $data, StoryPage $storyPage, $author)
    {
        // Update the page data
        $storyPage->update([
            'page_number' => $data['page_number'] ?? $storyPage->page_number,
            'title' => $data['title'] ?? $storyPage->title,
            'pitch_line' => $data['pitch_line'] ?? $storyPage->pitch_line,
            'source' => $data['source'] ?? $storyPage->source,
            'author_note' => $data['author_note'] ?? $storyPage->author_note,
            'content' => $data['content'] ?? $storyPage->content,
            'status' => $data['status'] ?? $storyPage->status,
            'author_id' => $author->id, // Set the author ID
        ]);

        return $storyPage;
    }
    /**
     * Retrieve the list of a story by status.
     *
     * @param int $status
     */
    /**
     * Retrieve the list of a story by status and author_id.
     *
     * @param int $status
     * @param int $authorId
     * @return \Illuminate\Support\Collection
     */
   
    public function getStoriesByStatusAndAuthor(Request $request , int $authorId): array
{
    
    try {
        // Retrieve the filters from the request
        $status = $request->input('status');
        $searchTerm = $request->input('search_term');
        $isActive = $request->input('active');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sortOrder = $request->input('sort_order', 'newest');  // Default to 'newest'
        $period = $request->input('period');
        // Status mapping based on your status column values
        $statusMapping = [
            'pending' => 0,
            'approved' => 1,
            'rejected' => 2,
            'finished' => 1,
            'launched' => 1,
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
                'stories.created_at',
                'stories.approved_at',
                'stories.updated_at',
                'stories.period',
                'stories.active',
                'users.name as author_name',
            ])
            ->join('authors', 'stories.author_id', '=', 'authors.id')
            ->join('users', 'authors.user_id', '=', 'users.id')
            ->where('stories.author_id', $authorId);  // Filter by author ID

        // Filter by status
        if ($status && isset($statusMapping[$status])) {
            switch ($status) {
                case 'pending':
                    $query->where('stories.status', 0);
                    break;
                case 'approved':
                    $query->where('stories.status', 1)->where('stories.is_finished', 0)->where('stories.is_launched', 0);
                    break;
                case 'finished':
                    $query->where('stories.is_finished', 1);
                    break;
                case 'launched':
                    $query->where('stories.is_launched', 1);
                    break;
                case 'rejected':
                    $query->where('stories.status', 2);
                    break;
            }
        }

         // Active filter
         if ($isActive) {
            $isActiveValue = $isActive === 'active' ? 1 : 0;
            $query->where('stories.active', $isActiveValue);
        }
       // Period term mapping
        $periodMapping = [
            'Day' => 1,
            'Week' => 2,
            'Month' => 3,
        ];

        // Period term
        if ($period && isset($periodMapping[$period])) {
            $query->where('stories.period', $periodMapping[$period]);
        }
        // Filter by search term
        if ($searchTerm) {
            $query->where('stories.name', 'like', '%' . $searchTerm . '%');
        }

        // Filter by start and end dates
        if ($startDate) {
            $query->whereDate('stories.created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('stories.created_at', '<=', $endDate);
        }

        // Sort by newest or oldest
        $query->orderBy('stories.created_at', $sortOrder === 'newest' ? 'desc' : 'asc');

        // Retrieve the stories
        $stories = $query->get();

        // Process the assignees and their roles for each story
        foreach ($stories as $story) {
            $assignees = $story->assignees()->with('author')->get();

            $author = '--';
            $illustrator = '--';
            $audioVideoCreator = '--';

            foreach ($assignees as $assignee) {
                switch ($assignee->role) {
                    case 'author':
                        $author = $assignee->offer_amount . '%';
                        break;
                    case 'illustrator':
                        $illustrator = $assignee->offer_type == 1 ? $assignee->offer_amount : $assignee->offer_amount . '%';
                        break;
                    case 'audio_video_creator':
                        $audioVideoCreator = $assignee->offer_type == 1 ? $assignee->offer_amount : $assignee->offer_amount . '%';
                        break;
                }
            }

            $story->author = $author;
            $story->illustrator = $illustrator;
            $story->audio_video_creator = $audioVideoCreator;

            // Map status values to human-readable text
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

            // Format dates
            $story->created_at_formatted = Carbon::parse($story->created_at)->format('M d, Y');
            $story->updated_at_formatted = $story->updated_at ? Carbon::parse($story->updated_at)->format('M d, Y') : '--';
            $story->approved_at_formatted = $story->approved_at ? Carbon::parse($story->approved_at)->format('M d, Y') : '--';
        }

        return [
            'status' => true,
            'message' => __('Stories retrieved successfully.'),
            'data' => $stories,
        ];
    } catch (Exception $e) {
        Log::error('Failed to retrieve stories', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'status' => false,
            'message' => __('An error occurred while retrieving stories. Please try again.'),
            'data' => null,
        ];
    }
}

    public function updateLaunchTime(StoryPage $storyPage, array $data)
    {
        // Update the story page with new data
        $storyPage->author_notes = $data['author_notes'];
        $storyPage->launch_status = $data['launch_status']; // 1 as specified in request
        $storyPage->launch_sequence = $data['launch_sequence'];
        $storyPage->launch_time = $data['launch_time'];
        $storyPage->save();

        return $storyPage;
    }

    public function getPageLaunchConfiguration(Story $story)
    {
        // Retrieve all pages for the given story along with launch-specific details
        $pages = StoryPage::where('story_id', $story->id)
            ->join('stories', 'stories.id', '=', 'story_pages.story_id')
            ->select(
                'story_pages.id',
                'story_pages.page_number',
                'story_pages.launch_status',
                'story_pages.launch_sequence',
                'story_pages.launch_time',
                'stories.name as story_name',
                'stories.period',
                'stories.created_at as story_created_at'
            )
            ->get();

        // Map period values to human-readable text
        $pages->transform(function ($page) {
            $periods = [1 => 'Daily', 2 => 'Weekly', 3 => 'Monthly'];
            $page->period_text = $periods[$page->period] ?? 'Unknown';
            return $page;
        });

        return $pages;
    }
    public function createOrUpdatePage(array $data, $author, StoryPage $storyPage = null)
   {
    // Assign the author ID to the data
    $data['author_id'] = $author->id;

    if ($storyPage) {
        // Update existing story page
        $storyPage->update([
            'page_number' => $data['page_number'] ?? $storyPage->page_number,
            'title' => $data['title'] ?? $storyPage->title,
            'pitch_line' => $data['pitch_line'] ?? $storyPage->pitch_line,
            'source' => $data['source'] ?? $storyPage->source,
            'author_note' => $data['author_note'] ?? $storyPage->author_note,
            'content' => $data['content'] ?? $storyPage->content,
            'status' => $data['status'] ?? $storyPage->status,
        ]);
        return $storyPage;
    } else {
        // Create a new story page
        $newPage = StoryPage::create($data);

        // Increment the number of pages in the story
        $story = Story::find($data['story_id']);
        if ($story) {
            $story->increment('number_of_pages');
        }

        return $newPage;
    }
   }
}
