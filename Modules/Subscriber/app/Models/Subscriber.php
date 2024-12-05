<?php

namespace Modules\Subscriber\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Models\User;

class Subscriber extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'phone_number',
    ];

    /**
     * Get the user associated with the subscriber.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
