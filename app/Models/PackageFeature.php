<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageFeature extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFeatureFactory> */
    use HasFactory , softDeletes;
    protected $fillable = [
        'package_id',
        'feature_id',
        'is_enabled',
        'value',
    ];
}
