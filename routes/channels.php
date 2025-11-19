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
}, ['guards' => ['sanctum']]);

Broadcast::channel('patient.{patientId}', function ($user, $patientId) {
    return (int) $user->id === (int) $patientId;
}, ['guards' => ['sanctum']]);

Broadcast::channel('messages.{senderId}', function ($user, $senderId) {
    return (int) $user->id === (int) $senderId;
}, ['guards' => ['sanctum']]);

Broadcast::channel('chat.between.{id1}.{id2}', function ($user, $id1, $id2) {
    Log::info('test_channel'  .  in_array($user->id, [(int)$id1, (int)$id2]));

    return in_array($user->id, [(int)$id1, (int)$id2]);
}, ['guards' => ['sanctum']]);


Broadcast::channel('glove-data.customer.{customerId}', function ($user, $customerId) {
    return (int) $user->id === (int) $customerId;
}, ['guards' => ['sanctum']]);


