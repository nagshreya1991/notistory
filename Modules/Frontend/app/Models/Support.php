<?php

namespace Modules\Frontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Frontend\Database\Factories\SupportFactory;

class Support extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['title', 'content','title_fr', 'content_fr'];

    protected static function newFactory(): SupportFactory
    {
        //return SupportFactory::new();
    }
}
