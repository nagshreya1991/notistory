<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Story\Database\Factories\StoryAssigneeFactory;
use Modules\Story\Models\Story;
use Modules\Author\Models\Author;

class StoryAssignee extends Model
{
    protected $table = 'story_assignees';

    protected $fillable = [
        'story_id',
        'author_id',
        'role',
        'offer_type',
        'offer_amount',
    ];
      /**
     * Get the story that this assignee belongs to.
     */
    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    /**
     * Get the author that is assigned to the story.
     */
    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }
    
}
