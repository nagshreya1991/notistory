<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Story\Database\Factories\StoryPageLaunchScheduleFactory;

class SubscriberStory extends Model
{
    use HasFactory;
    protected $table = 'subscriber_stories';
    protected $fillable = [
        'story_id', 'story_page_id', 'subscriber_id', 'launch_status', 'period','launch_sequence', 'scheduled_time',
    ];

    // Relationships
    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    public function storyPage()
    {
        return $this->belongsTo(StoryPage::class);
    }

    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class);
    }
}
