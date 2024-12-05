<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Story\Database\Factories\StoryPageFactory;
use Modules\Story\Models\Story;
use Modules\Author\Models\Author;

class StoryPage extends Model
{
    protected $table = 'story_pages';

    protected $fillable = [
        'story_id',
        'author_id',
        'page_number',
        'title',
        'pitch_line',
        'source',
        'author_note',
        'content',
        'status',
        'author_notes',
        'launch_status',
        'launch_sequence',
        'launch_time',
        'is_admin_edited',
    ];

    /**
     * Get the story that this page belongs to.
     */
    public function story()
    {
        return $this->belongsTo(Story::class, 'story_id');
    }

    /**
     * Get the author of this page.
     */
    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }
}

