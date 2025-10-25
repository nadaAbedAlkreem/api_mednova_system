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
        'reviewer_id',
        'reviewee_id',
        'reviewee_type',
        'rating',
        'comment',
    ];
    protected $casts = [
        'rating' => 'float',

    ];
    public function reviewee()
    {
        return $this->belongsTo(Customer::class , 'reviewee_id');
    }
    public function reviewer()
    {
        return $this->belongsTo(Customer::class , 'reviewer_id');
    }


}
