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
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'gender',
        'omnix_user_id',
        'password',
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function omnixUser()
    {
         return $this->hasMany(OmnixLog::class);
    }
}
