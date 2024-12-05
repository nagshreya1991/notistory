<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $fillable = [
        'story_task_id',
        'author_id',
        'comment',
        'attachment',
        'parent_id',
    ];

    /**
     * Get the task that the comment belongs to.
     */
    public function task()
    {
        return $this->belongsTo(StoryTask::class, 'story_task_id');
    }

    /**
     * Get the author of the comment.
     */
    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    /**
     * Get the parent comment (if this comment is a reply).
     */
    public function parent()
    {
        return $this->belongsTo(TaskComment::class, 'parent_id');
    }

    /**
     * Get the replies to the comment.
     */
    public function replies()
    {
        return $this->hasMany(TaskComment::class, 'parent_id');
    }
}