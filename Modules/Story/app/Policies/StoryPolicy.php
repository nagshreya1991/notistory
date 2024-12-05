<?php

namespace Modules\Story\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

use Modules\Story\Models\Story;
use Modules\User\Models\User;
use Modules\Author\Models\Author;
use Modules\Story\Models\StoryTask;
use Modules\Story\Models\StoryAssignee;
class StoryPolicy
{
    use HandlesAuthorization;

     /**
     * Determine whether the user can view the story.
     *
     * @param  \App\Models\User  $user
     * @param  \Modules\Story\Models\Story  $story
     * @return bool
     */
    public function view(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    
     /**
     * Determine whether the user can create a story.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return Author::where('user_id', $user->id)->exists();
    }
    public function createTask(User $user, Story $story)
    {
        $authorExists = Author::where('user_id', $user->id)->exists();

        if (!$authorExists) {
            return false;
        }
        $author = Author::where('user_id', $user->id)->first();

        return $author->id === $story->author_id;
    }
    public function addTaskComment(User $user, Story $story)
    {
        $authorExists = Author::where('user_id', $user->id)->exists();

        if (!$authorExists) {
            return false;
        }
        $author = Author::where('user_id', $user->id)->first();

        return $author->id === $story->author_id;
    }
    public function viewAssigneeDetails(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
    
        return $author && $author->id === $story->author_id;
    }
    public function viewTaskList(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
   
    public function viewTaskDetails(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    
    public function changeTaskStatus(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    public function update(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    
    public function viewAssigneeList(User $user, Story $story)
    {
        // Retrieve the author's record associated with the user
        $author = Author::where('user_id', $user->id)->first();

        if (!$author) {
            return false;
        }

        // Check if the author is the owner of the story
        if ($author->id === $story->author_id) {
            return true;
        }

        // Check if the author is an assignee for the story
        $isAssignee = StoryAssignee::where('story_id', $story->id)
            ->where('author_id', $author->id)
            ->exists();

        return $isAssignee;
    }
    public function addPage(User $user, Story $story)
    { 
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    public function editPage(User $user, Story $story)
    { 
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    public function addPageFile(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    public function viewPages(User $user, Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    public function viewPageDetails(User $user,  Story $story)
    {
        $author = Author::where('user_id', $user->id)->first();
        return $author && $author->id === $story->author_id;
    }
    public function addAssignee(User $user, Story $story)
   {
    $author = Author::where('user_id', $user->id)->first();
    return $author && $author->id === $story->author_id;
   }
   public function editTask(User $user, Story $story)
   {
   

    $authorExists = Author::where('user_id', $user->id)->exists();

    if (!$authorExists) {
        return false;
    }
    $author = Author::where('user_id', $user->id)->first();

    return $author->id === $story->author_id;
   }

   public function deleteTask(User $user, Story $story)
   {
    $authorExists = Author::where('user_id', $user->id)->exists();

    if (!$authorExists) {
        return false;
    }
    $author = Author::where('user_id', $user->id)->first();

    return $author->id === $story->author_id;
   }
   public function viewTaskComments(User $user, Story $story)
  {
    $authorExists = Author::where('user_id', $user->id)->exists();

    if (!$authorExists) {
        return false;
    }
    $author = Author::where('user_id', $user->id)->first();

    return $author->id === $story->author_id;
  }
  public function updateLaunchTime(User $user, Story $story)
  {
    $authorExists = Author::where('user_id', $user->id)->exists();

    if (!$authorExists) {
        return false;
    }
    $author = Author::where('user_id', $user->id)->first();

    return $author->id === $story->author_id;
  }

  public function viewLaunchPages(User $user, Story $story)
  {
    $author = Author::where('user_id', $user->id)->first();
    return $author && $author->id === $story->author_id;
  }
    public function approveStory(User $user, Story $story)
    {
    $author = Author::where('user_id', $user->id)->first();
    return $author && $author->id === $story->author_id;
    }
}
