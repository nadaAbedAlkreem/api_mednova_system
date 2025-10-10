<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RehabilitationCenter extends Model
{
    /** @use HasFactory<\Database\Factories\RehabilitationCenterFactory> */
    use HasFactory , softDeletes;
    protected $table = 'rehabilitation_centers';
    protected $fillable = [
        'customer_id',
        'year_establishment' ,
        'license_number' ,
        'license_authority' ,
        'license_file' ,
        'bio',
        'has_commercial_registration' ,
        'commercial_registration_number',
        'commercial_registration_file' ,
        'commercial_registration_authority' ,

    ];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function schedules(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Schedule::class, 'schedulable');
    }
}
