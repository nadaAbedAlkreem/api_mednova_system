<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory  ,SoftDeletes;
    protected $fillable = [
        'customer_id',
        'status',
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

}
