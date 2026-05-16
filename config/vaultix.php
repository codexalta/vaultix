<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Super Admin Email
    |--------------------------------------------------------------------------
    |
    | This email will always have full access to the Vaultix dashboard, 
    | bypassing the dynamic database-based authorization list.
    |
    */
    'super_admin' => env('VAULTIX_SUPER_ADMIN', 'super_admin@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that will be applied to all Vaultix routes.
    |
    */
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Disk Monitoring Path
    |--------------------------------------------------------------------------
    |
    | The absolute path to the disk/partition you want to monitor. 
    | Default is the project's storage path.
    |
    */
    'monitor_path' => env('VAULTIX_DISK_PATH', storage_path()),
];
