<?php

declare(strict_types=1);

return [
    /*
     * How long (in minutes) after the last activity event the "currently active"
     * indicator stays visible in the title bar. Intentionally shorter than the
     * idle_timeout used for session reconstruction.
     */
    'current_activity_timeout_minutes' => 5,

    'reports' => [
        'disk' => env('ACTIVITY_REPORTS_DISK', 'local'),

        'csv' => [
            'delimiter' => ',',
        ],
    ],
];
