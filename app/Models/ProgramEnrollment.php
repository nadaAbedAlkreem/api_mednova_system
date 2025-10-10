<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramEnrollment extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramEnrollmentFactory> */
    use HasFactory , SoftDeletes;
    protected $fillable = ['customer_id' , 'program_id' , 'enrolled_at' ,'is_completed' ] ;

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

}
