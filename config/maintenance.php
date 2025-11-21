<?php

return [
    'driver' => env('MAINTENANCE_DRIVER', 'file'),

    'store' => env('MAINTENANCE_STORE'),

    'retry' => env('MAINTENANCE_RETRY', 60),

    'secret' => env('MAINTENANCE_SECRET'),
];
