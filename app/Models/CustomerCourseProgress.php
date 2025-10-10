<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCourseProgress extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerCourseProgressFactory> */
    use HasFactory , SoftDeletes;
    protected $table = 'customer_course_progress';
    protected $fillable =
        ['customer_id' , 'program_id' , 'videos_completed' ,'current_video' ,'current_time' ] ;
}
