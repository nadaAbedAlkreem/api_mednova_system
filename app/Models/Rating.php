<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rating extends Model
{
    /** @use HasFactory<\Database\Factories\RatingFactory> */
    use HasFactory, softDeletes;
    protected $fillable = [
        'customer_id',
        'rating',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
