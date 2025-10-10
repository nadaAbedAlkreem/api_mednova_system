<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasApiTokens,HasFactory, softDeletes;
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'gender' ,
        'phone' ,
        'birth_date' ,
        'image' ,
        'provider' ,
        'provider_id' ,
        'fcm_token' ,
        'is_online' ,
        'last_active_at' ,
        'is_banned' ,
        'type_account',
    ];
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($customer) {
            $customer->location()->each(function ($location) {
                $location->delete();
            });

            $customer->therapist()->each(function ($therapist) {
                $therapist->delete();
            });
            $customer->rehabilitationCenter()->each(function ($rehabilitationCenter) {
                $rehabilitationCenter->delete();
            });
            $customer->patient()->each(function ($patient) {
                $patient->delete();
            });

            $customer->schedules()->each(function ($schedules) {
                $schedules->delete();
            });

            $customer->appointmentRequests()->each(function ($appointmentRequests) {
                $appointmentRequests->delete();
            });

            $customer->consultationVideoRequests()->each(function ($consultationVideoRequests) {
                $consultationVideoRequests->delete();
            });

            $customer->consultationVideoRequests()->each(function ($consultationVideoRequests) {
                $consultationVideoRequests->delete();
            });
            $customer->userPackages()->each(function ($userPackages) {
                $userPackages->delete();
            });
            $customer->programEnrollments()->each(function ($programEnrollments) {
                $programEnrollments->delete();
            });
            $customer->programEnrollments()->each(function ($programEnrollments) {
                $programEnrollments->delete();
            });
            //medicalSpecialties
            $customer->medicalSpecialties()->each(function ($medicalSpecialties) {
                $medicalSpecialties->delete();
            });

            $customer->programs()->each(function ($programs) {
                $programs->delete();
            });
        });
    }
    public function medicalSpecialties()
    {
        return $this->belongsToMany(MedicalSpecialtie::class, 'rehabilitation_specialist_specialty', 'customer_id', 'specialty_id')->withTimestamps();
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class)->withTimestamps();
    }
    public function therapist(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Therapist::class);
    }
    public function ratings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function rehabilitationCenter(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RehabilitationCenter::class);
    }
    public function patient(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function location(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Location::class);
    }


    // علاقة المواعيد (أوفلاين أو أونلاين)
    public function schedules(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Schedule::class, 'schedulable');
    }

    // علاقة طلبات الحجوزات
    public function appointmentRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentRequest::class);
    }

    // علاقة طلبات الاستشارة (محادثة)
    public function receivedConsultations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationChatRequest::class, 'consultant_id');
    }
    public function consultationRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationChatRequest::class, 'patient_id');
    }
    // علاقة طلبات الاستشارة فيديو
    public function consultationVideoRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ConsultationVideoRequest::class);
    }

    // علاقة باقات المستخدم
    public function userPackages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserPackage::class);
    }

    // علاقة البرامج المشترك بها
    public function programEnrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }





}
