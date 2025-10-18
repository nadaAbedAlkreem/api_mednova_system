<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\AdminFactory> */

    use HasFactory , SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    public function programs()
    {
        return $this->morphMany(Program::class, 'creator');
    }
    public function notifications()
    {
        return $this->morphMany(\App\Models\Notifications::class, 'notifiable');
    }

}
