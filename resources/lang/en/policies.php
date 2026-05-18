<?php

return [
    'consultation' => [
        'pay' => [
            'not_patient' => 'Only patients can pay for consultations.',
            'not_owner' => 'You cannot pay for a consultation that does not belong to you.',
            'already_paid' => 'This consultation has already been paid.',
        ],
        'create_request' => [
            'not_approved' => 'Your account has not been approved yet.',
            'not_active' => 'Your account is inactive, please contact support.',
        ],
        'view' => [
            'unauthorized' => 'You are not authorized to view this consultation.',
        ],
        'policy_not_found' => 'This policy does not exist.',

        'dispute' =>
            [
                'not_owner' => 'This consultation does not belong to you, so you cannot open a dispute over it.',
                'not_review_window' => 'Disputes can only be opened during the review period.',
                'expired' => 'The review period for this consultation has expired.'
            ],
        'accept' => [
            'not_consultant' => 'Only the relevant consultant can accept the consultation.',
            'not_paid' => 'Unpaid consultations cannot be accepted.',
            'appointment_passed' => 'The consultation cannot be accepted because the appointment has passed.',
        ],
        'cancel' => [
            'wrong_role' =>  "You are not authorized to cancel this consultation.",
            'already_paid' => 'You cannot cancel the consultation after payment has been made.',
        ]


    ],
];
