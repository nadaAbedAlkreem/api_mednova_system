<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RehabilitationSpecialistSpecialty extends Model
{
    use HasFactory;
    protected $table = 'rehabilitation_centers';
    protected $fillable = [
        'customer_id',
        'specialty_id' ,
    ];

}
