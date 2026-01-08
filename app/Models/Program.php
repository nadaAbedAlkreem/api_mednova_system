<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

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
    protected $appends = [
        'total_duration_minutes',
    ];

    protected $casts = [
        'price' => 'float',
    ];
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('is_approved', true);
    }
    public function getTotalDurationMinutesAttribute(): int
    {
        return (int) $this->videos->sum('duration_minute');
    }

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

            $program->ratings()->each(function ($ratings) {
                $ratings->delete();
            });
        });
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'reviewee');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function videos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProgramVideos::class);
    }
    public function reviewRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProgramReviewRequests::class);
    }


    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }
}
