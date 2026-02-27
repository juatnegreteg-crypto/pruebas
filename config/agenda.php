<?php

return [
    'driver' => env('AGENDA_DRIVER', 'database'),

    'http' => [
        'base_url' => env('AGENDA_HTTP_BASE_URL'),
        'token' => env('AGENDA_HTTP_TOKEN'),
        'timeout' => (int) env('AGENDA_HTTP_TIMEOUT', 10),
        'connect_timeout' => (int) env('AGENDA_HTTP_CONNECT_TIMEOUT', 5),
        'retry_times' => (int) env('AGENDA_HTTP_RETRY_TIMES', 1),
        'retry_sleep_ms' => (int) env('AGENDA_HTTP_RETRY_SLEEP_MS', 100),
        'endpoints' => [
            'create' => env('AGENDA_HTTP_ENDPOINT_CREATE', '/api/v1/appointments'),
            'reschedule' => env('AGENDA_HTTP_ENDPOINT_RESCHEDULE', '/api/v1/appointments/{appointment}/reschedule'),
            'reassign_technician' => env('AGENDA_HTTP_ENDPOINT_REASSIGN_TECHNICIAN', '/api/v1/appointments/{appointment}/reassign-technician'),
            'cancel' => env('AGENDA_HTTP_ENDPOINT_CANCEL', '/api/v1/appointments/{appointment}/cancel'),
            'availability' => env('AGENDA_HTTP_ENDPOINT_AVAILABILITY', '/api/v1/appointments/availability'),
        ],
    ],
];
