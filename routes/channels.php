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

Broadcast::channel('consultant.{consultantId}', function ($user, $consultantId) {
    return (int) $user->id === (int) $consultantId;
});

Broadcast::channel('patient.{patientId}', function ($user, $patientId) {
    return (int) $user->id === (int) $patientId;
});
//Broadcast::channel('notify.for.{customerId}', function ($user, $customerId) {
//    return in_array($user->id, [(int)$consultantId, (int)$patientId]);
//});

Broadcast::channel('chat.between.{senderId}.{receiverId}', function ($user, $senderId, $receiverId) {
    return in_array($user->id, [(int)$senderId, (int)$receiverId]);
});

