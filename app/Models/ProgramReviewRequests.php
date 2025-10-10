<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramReviewRequests extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramReviewRequestsFactory> */
    use HasFactory ,SoftDeletes;
    protected $fillable=[
        'program_id',
        'requested_by',
        'status' ,
        'notes',
    ];



    public function program()
    {
        return $this->belongsTo(Program::class);
    }


    public function customer()
    {
        return $this->belongsTo(Customer::class, 'requested_by');
    }

}
