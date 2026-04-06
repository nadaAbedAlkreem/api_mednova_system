<?php

return [
    'consultation' => [
        'pay' => [
            'not_patient'      => 'Only patients can pay for consultations.',
            'not_owner'        => 'You cannot pay for a consultation that does not belong to you.',
            'already_paid'     => 'This consultation has already been paid.',
        ],
        'create_request' => [
            'not_approved'     => 'Your account has not been approved yet.',
            'not_active'       => 'Your account is inactive, please contact support.',
        ],
        'view' => [
            'unauthorized'     => 'You are not authorized to view this consultation.',
        ],
        'policy_not_found' =>  'This policy does not exist.',

    ],
];
