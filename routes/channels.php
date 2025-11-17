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

Broadcast::channel('chat.between.{senderId}.{receiverId}', function ($user, $senderId, $receiverId) {
    return in_array($user->id, [(int)$senderId, (int)$receiverId]);
}, ['guards' => ['sanctum']]);

Broadcast::channel('glove-data.customer.{customerId}', function ($user, $customerId) {
    return (int) $user->id === (int) $customerId;
}, ['guards' => ['sanctum']]);


