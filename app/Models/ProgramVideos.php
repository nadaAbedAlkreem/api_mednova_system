<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramVideos extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramVideosFactory> */
    use HasFactory ,SoftDeletes;
    protected $table = 'program_videos';
    protected $fillable = ['program_id', 'title_ar' ,'description_ar', 'description_en', 'what_you_will_learn_ar', 'what_you_will_learn_en', 'title_en' , 'video_path' , 'duration_minute', 'order' , 'is_program_intro'];
    public function program()
    {
        return $this->belongsTo(Program::class);
    }


}
