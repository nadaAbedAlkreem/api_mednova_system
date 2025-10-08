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
        });
    }
    public function therapist(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Therapist::class);
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



}
