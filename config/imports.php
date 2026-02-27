<?php

return [
    'customers' => [
        'queue_threshold_kb' => (int) env('CUSTOMERS_IMPORT_QUEUE_THRESHOLD_KB', 1024),
        'queue_name' => env('CUSTOMERS_IMPORT_QUEUE_NAME', 'imports'),
    ],
];
