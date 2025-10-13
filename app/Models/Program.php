<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory ,SoftDeletes;
    protected $table = 'programs';
    protected $fillable = [
        'creator_id',
        'creator_type',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'cover_image',
        'price',
        'status',
        'is_approved'
    ];

    protected $casts = [
        'price' => 'float',
    ];


    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($program) {
            $program->videos()->each(function ($videos) {
                $videos->delete();
            });
            $program->reviewRequests()->each(function ($reviewRequests) {
                $reviewRequests->delete();
            });
            $program->enrollments()->each(function ($enrollments) {
                $enrollments->delete();
            });
        });
    }

    public function creator()
    {
        return $this->morphTo();
    }

    public function videos()
    {
        return $this->hasMany(ProgramVideos::class);
    }
    public function reviewRequests()
    {
        return $this->hasMany(ProgramReviewRequests::class);
    }


    public function enrollments()
    {
        return $this->hasMany(ProgramEnrollment::class);
    }
}
