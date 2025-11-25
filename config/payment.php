<?php

return [
    'networks' => [
        'mtn' => [
            'name' => 'MTN Money',
            'payment_numbers' => ['0154649143', '0166110299'],
            'patterns' => [
                'transaction_id' => '/Ref\.? :?([A-Z0-9]+)/i',
                'amount' => '/(\d+(?:\.\d+)?)\s*FCFA/i'
            ]
        ],
        'moov' => [
            'name' => 'Moov Money',
            'payment_numbers' => ['0158187101'],
            'patterns' => [
                'transaction_id' => '/Ref:? ([A-Z0-9]+)/i',
                'amount' => '/(\d+(?:\.\d+)?)\s*FCFA/i'
            ]
        ],
        'orange' => [
            'name' => 'Orange Money',
            'payment_numbers' => ['0158187101'],
            'patterns' => [
                'transaction_id' => '/Transaction:? ([A-Z0-9]+)/i',
                'amount' => '/(\d+(?:\.\d+)?)\s*FCFA/i'
            ]
        ]
    ]
];