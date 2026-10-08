<?php

return [
    'quick_access' => [
        [
            'label' => 'Coordinadora de RH',
            'email' => env('SIPD_QUICK_COORDINATOR_EMAIL'),
            'password' => env('SIPD_QUICK_COORDINATOR_PASSWORD'),
        ],
        [
            'label' => 'Asesora jurídica',
            'email' => env('SIPD_QUICK_KELLY_EMAIL'),
            'password' => env('SIPD_QUICK_KELLY_PASSWORD'),
        ],
        [
            'label' => 'Jefe de personal',
            'email' => env('SIPD_QUICK_MARSHALL_EMAIL'),
            'password' => env('SIPD_QUICK_MARSHALL_PASSWORD'),
        ],
        [
            'label' => 'Asesor jurídico',
            'email' => env('SIPD_QUICK_JORGE_EMAIL'),
            'password' => env('SIPD_QUICK_JORGE_PASSWORD'),
        ],
    ],
];
