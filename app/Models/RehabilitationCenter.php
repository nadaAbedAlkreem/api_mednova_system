<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RehabilitationCenter extends Model
{
    /** @use HasFactory<\Database\Factories\RehabilitationCenterFactory> */
    use HasFactory , softDeletes;
    protected $table = 'rehabilitation_center';
    protected $fillable = [
        'customer_id',
        'experience_years' ,
        'medical_specialty' ,
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
}
