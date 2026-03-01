<?php

namespace App\Models;

use App\Enums\ProgramStatus;
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
        'what_you_will_learn_ar',
        'what_you_will_learn_en',
        'cover_image',
        'price',
        'currency',
        'status',
//        'is_approved'
    ];
    protected $appends = [
        'total_duration_minutes',
    ];

    protected $casts = [
        'price' => 'float',
    ];
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', ProgramStatus::Approved );
//            ->where('is_approved', true);
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
    public function approve(): void
    {
        if (!$this->canBeApproved()) {
            throw new \DomainException(__('messages.PROGRAM_NOT_READY_FOR_APPROVAL'));
        }

        $this->update([
            'status' => ProgramStatus::Approved->value,
        ]);
    }
    public function reject(?string $reason = null): void
    {
        if ($this->status === ProgramStatus::Rejected->value) {
            throw new \DomainException(__('messages.PROGRAM_ALREADY_REJECTED'));
        }

        if ($this->status === ProgramStatus::Approved->value) {
            throw new \DomainException(__('messages.APPROVED_PROGRAM_CANNOT_BE_REJECTED'));
        }

        $this->update([
            'status' => ProgramStatus::Rejected->value,
            // 'rejection_reason' => $reason //
        ]);
    }
    public function canBeApproved(): bool
    {
        return $this->hasRequiredArabicData()
            && $this->hasAtLeastOneVideo()
            && $this->hasValidVideosData()
            && $this->hasIntroVideo()
            && $this->hasValidVideoOrdering();
    }
    protected function hasRequiredArabicData(): bool
    {
        return !empty($this->title_ar)
            && !empty($this->description_ar)
            && !empty($this->what_you_will_learn_ar);
    }
    protected function hasAtLeastOneVideo(): bool
    {
        return $this->videos()->count() > 0;
    }
    protected function hasValidVideosData(): bool
    {
        return !$this->videos()->where(function ($q) {
            $q->whereNull('title_ar')
                ->orWhereNull('video_path')
                ->orWhereNull('duration_minute');
        })->exists();
    }
    protected function hasIntroVideo(): bool
    {
        return $this->videos()
                ->where('is_program_intro', true)
                ->count() === 1;
    }
    protected function hasValidVideoOrdering(): bool
    {
        $orders = $this->videos()
            ->orderBy('order')
            ->pluck('order')
            ->toArray();
        if (count($orders) !== count(array_unique($orders))) {
            return false;
        }
        return $orders === range(1, count($orders));
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
