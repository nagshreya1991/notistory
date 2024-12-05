<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Story\Database\Factories\PageFileFactory;
use Modules\Story\Models\StoryPage;
use Modules\Story\Models\Story;
use Modules\Author\Models\Author;

class PageFile extends Model
{
    protected $table = 'page_files';

    protected $fillable = [
        'author_id',
        'story_id',
        'story_page_id',
        'file',
    ];

    /**
     * Get the story page that this file belongs to.
     */
    public function storyPage()
    {
        return $this->belongsTo(StoryPage::class, 'story_page_id');
    }
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
