<?php

namespace Modules\Subscriber\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Models\User;
use Modules\Subscriber\Models\Subscriber;
use Modules\Story\Models\Story;

class SubscriberPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can perform forgot password action.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function forgotPassword(User $user)
    {
        // Define your policy logic here
        return $user->role === User::ROLE_SUBSCRIBER;
    }
  
      /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subscriber $subscriber): bool
    {
        // Allow only subscribers to view their profile and ensure they own the profile
        return $user->role === User::ROLE_SUBSCRIBER && $user->id === $subscriber->user_id;
    }

     /**
     * Determine whether the user can update the subscriber profile.
     *
     * @param  \Modules\User\Models\User  $user
     * @param  \Modules\Subscriber\Models\Subscriber  $subscriber
     * @return bool
     */
    public function update(User $user, Subscriber $subscriber): bool
    {
        // Allow update only if the authenticated user owns the profile
        return $user->role === User::ROLE_SUBSCRIBER && $user->id === $subscriber->user_id;
    }

    public function viewStories(Subscriber $subscriber, Story $story)
    {
        // Implement your logic to determine if the subscriber can view the story
        return true; // Allow all subscribers for this example
    }

}

