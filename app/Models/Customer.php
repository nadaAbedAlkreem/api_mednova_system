<?php

namespace App\Models;

use App\Models\Scopes\ActiveVerifiedCustomerScope;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasApiTokens, HasFactory,  SoftDeletes;
    const TYPE_PATIENT = 'patient';
    const TYPE_THERAPIST = 'therapist';
    const TYPE_CENTER = 'rehabilitation_center';

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
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
        'status',
        'timezone',
        'email_verified_at',
    ];


    public function complainantReport(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Customer::class, 'customer_id');
    }

    public function reported(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Customer::class, 'reported_customers_id');
    }
    public function senderMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
    public function receiverMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }
    public function notifications()
    {
        return $this->morphMany(\App\Models\Notification::class, 'notifiable');
    }

    public function medicalSpecialties()
    {
        return $this->belongsToMany(MedicalSpecialtie::class, 'rehabilitation_specialist_specialty', 'customer_id', 'specialty_id')->withTimestamps();
    }

    public function programs()
    {
        return $this->morphMany(Program::class, 'creator');
    }
    public function deviceRequests()
    {
        return $this->hasMany(DeviceRequest::class);
    }
    public function therapist(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Therapist::class);
    }
    public function ratingsReviewer(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Rating::class , 'reviewer_id');
    }
    public function ratings()
    {
        return $this->morphMany(Rating::class, 'reviewee');
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


    // علاقة طلبات الحجوزات
    public function appointmentRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentRequest::class);
    }
    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schedule::class, 'consultant_id');
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

    public function cards(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Card::class, 'owner');
    }

    public function bankAccounts(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(bankAccount::class, 'owner');
    }
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ActiveVerifiedCustomerScope);
        static::creating(function ($customer) {
            if (empty($customer->status) && !empty($customer->type_account)) {
                $customer->status = self::resolveDefaultStatus($customer->type_account);
            }
        });
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

            $customer->notifications()->each(function ($notifications) {
                $notifications->delete();
            });

            $customer->ratingReviewer()->each(function ($reviewer) {
                $reviewer->delete();
            });

            $customer->ratings()->each(function ($reviewee) {
                $reviewee->delete();
            });
            $customer->schedule()->each(function ($schedule) {
                $schedule->delete();
            });
            $customer->deviceRequests()->each(function ($deviceRequests) {
                $deviceRequests->delete();
            });
        });
    }



    public static function resolveDefaultStatus(string $type): string
    {
        return $type === self::TYPE_PATIENT
            ? self::STATUS_ACTIVE
            : self::STATUS_PENDING;
    }



}
