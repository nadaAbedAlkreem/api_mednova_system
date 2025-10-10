<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory , softDeletes;
    protected $table = 'packages';
    protected $fillable = [
      'name_ar',
      'name_en',
      'description_ar',
      'description_en',
      'type',
      'price',
      'billing_cycle' ,
      'is_active'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($package) {
            $$package->features()->each(function ($features) {
                $features->delete();
            });
        });
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'package_feature')->withPivot('is_enabled','value')->withTimestamps();
    }
}
