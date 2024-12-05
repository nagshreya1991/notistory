<?php

namespace Modules\Admin\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\User\Models\User;

class AdminPolicy
{
    /**
     * Automatically grant all permissions to users with type 1 (admin).
     *
     * @param  User  $user
     * @return bool|null
     */
    public function before(User $user)
    {
        if ($user->type === 1) {
            return true;
        }
        return null; // Returning null allows other policy methods to run for non-admins.
    }
}
