<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationsFactory> */
    use HasFactory;
    protected $table = 'notifications';
    protected $fillable = ['type', 'notifiable_id'  , 'notifiable_type'  ,'status' , 'data' , 'read_at' , 'created_at' , 'updated_at' , 'deleted_at'];
    public function notifiable()
    {
        return $this->morphTo();
    }
}
