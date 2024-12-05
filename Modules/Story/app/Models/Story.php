<?php

namespace Modules\Story\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Story\Database\Factories\StoryFactory;
use Modules\Author\Models\Author;


class Story extends Model
{
    protected $table = 'stories';

    protected $fillable = [
        'author_id',
        'name',
        'logo',
        'cover',
        'pitch',
        'number_of_pages',
        'period',
        'minimum_age',
        'price',
        'status',
        'active',
    ];
    /**
     * Get the author that owns the story.
     */
    public function author()
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    /**
     * Get the assignees for the story.
     */
    public function assignees()
    {
        return $this->hasMany(StoryAssignee::class, 'story_id');
    }
   
    /**
     * Define the relationship with the StoryPurchase model.
     */
    public function purchases()
    {
        return $this->hasMany(StoryPurchase::class, 'story_id', 'id');
    }
}