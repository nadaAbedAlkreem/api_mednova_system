<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Therapist extends Model
{
    /** @use HasFactory<\Database\Factories\TherapistFactory> */
    use HasFactory   , softDeletes;

    protected $fillable = [
        'customer_id',
        'medical_specialties_id',
        'experience_years' ,
        'university_name' ,
        'countries_certified' ,
        'graduation_year' ,
        'certificate_file' ,
        'license_number' ,
        'license_authority' ,
        'bio' ,
        'license_file' ,

    ];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function specialty()
    {
        return $this->belongsTo(MedicalSpecialtie::class, 'medical_specialties_id');
    }

    public function schedules()
    {
        return $this->morphMany(Schedule::class, 'schedulable');
    }
}
