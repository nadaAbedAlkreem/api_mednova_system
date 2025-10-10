<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPackage extends Model
{
    /** @use HasFactory<\Database\Factories\UserPackageFactory> */
    use HasFactory ,SoftDeletes;
    protected $fillable = [
        'customer_id',
        'package_id',
        'starts_at' ,
        'ends_at' ,
        'is_active' ,
    ];
}
