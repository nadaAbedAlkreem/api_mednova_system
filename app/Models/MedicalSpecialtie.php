<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalSpecialtie extends Model
{
    /** @use HasFactory<\Database\Factories\MedicalSpecialtieFactory> */
    use HasFactory ,SoftDeletes;
    protected $table = 'medical_specialties';
    protected $fillable = [
        'name_ar',
        'name_en',
        'description',
    ];
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($medicalSpecialties) {
            $medicalSpecialties->therapists()->each(function ($therapists) {
                $therapists->delete();
            });
            $medicalSpecialties->specialistPivot()->each(function ($specialistPivot) {
                $specialistPivot->delete();
            });
            $medicalSpecialties->customers()->each(function ($customers) {
                $customers->delete();
            });



        });
    }

    public function therapists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Therapist::class, 'medical_specialties_id');
    }
    public function customers()
    {
        return $this->belongsToMany(
            Customer::class,
            'rehabilitation_specialist_specialty',
            'specialty_id',
            'customer_id'
        )->withTimestamps();
    }


}
