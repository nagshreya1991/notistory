<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoryPurchase extends Model
{
    use HasFactory;

    // Specify the table name
    protected $table = 'story_purchases';

    // Specify the fields that can be mass-assigned
    protected $fillable = [
        'story_id',
        'subscriber_id',
        'amount',
        'status',
    ];

    /**
     * Define a relationship to the `Story` model.
     */
    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    /**
     * Define a relationship to the `Subscriber` model.
     */
    public function subscriber()
    {
        return $this->belongsTo(\Modules\Subscriber\Models\Subscriber::class, 'subscriber_id');
    }
}
