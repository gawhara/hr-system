<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document expiry alert mail channel
    |--------------------------------------------------------------------------
    | In-app (database) notifications are always on. Mail is opt-in per
    | deployment so offline branch servers without SMTP never fail the
    | scheduled alert job.
    */

    'expiry_alert_mail' => env('HR_EXPIRY_ALERT_MAIL', false),

    /*
    |--------------------------------------------------------------------------
    | Notify the employee themselves (in addition to HR) on expiring documents
    |--------------------------------------------------------------------------
    */

    'expiry_alert_notify_employee' => env('HR_EXPIRY_ALERT_NOTIFY_EMPLOYEE', true),

    /*
    |--------------------------------------------------------------------------
    | Offline sync (Strategy A)
    |--------------------------------------------------------------------------
    | role: 'standalone' (no sync), 'branch' (pushes to / pulls from a central
    | server) or 'central' (receives branch pushes, serves pulls).
    | Branch nodes cannot lock payroll runs with unsynced data.
    */

    'sync' => [
        'role' => env('HR_SYNC_ROLE', 'standalone'),
        'central_url' => env('HR_SYNC_CENTRAL_URL'),
        'token' => env('HR_SYNC_TOKEN'),
        'device_name' => env('HR_SYNC_DEVICE_NAME', gethostname() ?: 'unknown-device'),
    ],

];
