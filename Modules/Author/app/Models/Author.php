<?php

namespace Modules\Author\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Models\User;
use Modules\Story\Models\Story;

class Author extends Model
{

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'phone_number',
        'case_keywords',
        'portfolio_link',
        'about',
        'iban'
    ];

    /**
     * Get the user associated with the author.
     */
    public function user()
    {
      //  return $this->belongsTo(User::class);
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The skills that belong to the author.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'author_skills')->withTimestamps();
    }
    public function stories()
  {
    return $this->hasMany(Story::class, 'author_id');
   }
}
