<?php

namespace Modules\Frontend\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Frontend\Database\Factories\TestimonialFactory;

class Testimonial extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['image', 'name', 'content'];

    protected static function newFactory(): TestimonialFactory
    {
        //return TestimonialFactory::new();
    }
}
