<?php

return [
    'store_branches' => [
        'Complete Selular Babakan',
        'Complete Selular Cilacap',
        'Complete Selular Ciledug',
        'Complete Selular Gebang',
        'Complete Selular Jatiwangi',
        'Complete Selular Jatiwangi2 (Cibolerang)',
        'Complete Selular Kroya',
        'Complete Selular Pabuaran',
        'Complete Selular Patrol',
        'Complete Selular Perumnas',
        'Complete Selular Plaza Cell',
        'Complete Selular Sindang',
        'Complete Selular Surya 1',
        'Complete Selular Tegal',
        'Complete Selular Tuparev',
        'Global Selular Jatibarang',
        'HP Mart Ciledug',
        'HP Mart Sindang',
        'Intiphone',
        'Mi Shop Ciledug',
        'Oppo Store Tentara Pelajar',
        'Selular Plus Jatibarang',
        'Unboxing Megu',
    ],

    'whatsapp_validation' => [
        'enabled'               => env('WHATSAPP_ENABLED', false),
        'provider'              => env('WHATSAPP_PROVIDER', 'fonnte'),
        'endpoint'              => env('WHATSAPP_VALIDATION_ENDPOINT', 'https://api.fonnte.com/validate'),
        'token'                 => env('WHATSAPP_API_KEY'),
        'country_code'          => env('WHATSAPP_COUNTRY_CODE', '62'),
        'timeout'               => (int) env('WHATSAPP_VALIDATION_TIMEOUT', 5),
        'cache_ttl'             => (int) env('WHATSAPP_VALIDATION_CACHE_TTL', 300),
        'allow_manual_fallback' => env('WHATSAPP_VALIDATION_ALLOW_MANUAL', true),
        'rate_limit'            => [
            'max_attempts' => (int) env('WHATSAPP_VALIDATION_RATE_LIMIT', 10),
            'decay'        => (int) env('WHATSAPP_VALIDATION_RATE_DECAY', 60),
        ],
    ],
];
