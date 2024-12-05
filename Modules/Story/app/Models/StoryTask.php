<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;

class StoryTask extends Model
{
    protected $fillable = [
        'assignee_id',
        'story_id',
        'title',
        'description',
        'due_date',
        'attachment',
        'status',
    ];

    /**
     * Get the assignee for the task.
     */
    public function assignee()
    {
        return $this->belongsTo(StoryAssignee::class, 'assignee_id');
    }

    /**
     * Get the story associated with the task.
     */
    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    /**
     * Get all comments related to the task.
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'story_task_id')->whereNull('parent_id'); // Top-level comments
    }
}