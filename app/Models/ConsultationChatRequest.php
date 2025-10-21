<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationChatRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationChatRequestFactory> */
    use HasFactory ,SoftDeletes;
    protected $fillable = [
        'patient_id' ,
        'consultant_id' ,
        'consultant_type' ,
        'status' ,
        'patient_message_count',
        'consultant_message_count' ,
        'first_patient_message_at' ,
        'first_consultant_message_at' ,
        'session_duration_hours' ,
        'started_at' ,
        'ended_at' ,
        'action_by' ,
        'action_reason'
    ];

    // المريض
    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class, 'patient_id');
    }

     public function consultant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
     {
        return $this->belongsTo(Customer::class, 'consultant_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_request_id');
    }
    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'chat_request_id')
            ->where('is_read', false);
    }


}
