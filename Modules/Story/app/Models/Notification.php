<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Story\Database\Factories\NotificationFactory;

class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'message', 'is_read'];

    // Relationship to the user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}