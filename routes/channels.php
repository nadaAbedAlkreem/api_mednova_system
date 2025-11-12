<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
|
*/

Broadcast::channel('private-consultant.{consultantId}', function ($user, $consultantId) {
    Log::info("Consultant ID: $consultantId");
    Log::info("Current Consultant ID:$user->id ");
    return (int) $user->id === (int) $consultantId;
});

Broadcast::channel('patient.{patientId}', function ($user, $patientId) {
    return (int) $user->id === (int) $patientId;
});


Broadcast::channel('chat.between.{senderId}.{receiverId}', function ($user, $senderId, $receiverId) {
    return in_array($user->id, [(int)$senderId, (int)$receiverId]);
});

Broadcast::channel('glove-data.customer.{customerId}', function ($user, $customerId) {
    return (int) $user->id === (int) $customerId;
});


