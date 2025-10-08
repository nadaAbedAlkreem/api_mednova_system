<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory , softDeletes;
    protected $table = 'locations';
    protected $fillable = [
        'customer_id',
        'latitude',
        'longitude',
        'formatted_address',
        'country',
        'region',
        'city',
        'district',
        'postal_code',
        'location_type'
    ];

    public function customers(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }}
