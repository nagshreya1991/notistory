<?php

namespace Modules\Author\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];

    /**
     * The authors that belong to the skill.
     */
    public function authors()
    {
        return $this->belongsToMany(Author::class, 'author_skills')->withTimestamps();
    }
}
