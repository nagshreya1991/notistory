<?php

namespace Modules\Author\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorSkill extends Model
{
  /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['author_id' ,'skill_id'];

  
}
