<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Directories to warm
    |--------------------------------------------------------------------------
    |
    | `kiln:warm` recursively compiles every .php file found under these
    | directories into OPcache. Paths are relative to the application base
    | path. Keep this list to code that is actually autoloaded/executed —
    | warming test suites or storage/logs wastes cycles.
    |
    */
    'warm_paths' => [
        'app',
        'bootstrap/cache',
        'config',
        'routes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warm vendor/
    |--------------------------------------------------------------------------
    |
    | Off by default: vendor/ is large and its files are already compiled the
    | first time they're autoloaded in production traffic. Turn this on only
    | if a deploy wants every dependency pre-warmed too.
    |
    */
    'warm_vendor' => false,

    /*
    |--------------------------------------------------------------------------
    | HTTP route
    |--------------------------------------------------------------------------
    |
    | A CLI process (php artisan) has its own OPcache SHM segment, separate
    | from the FPM worker pool's. `kiln:clear`/`kiln:warm` run from the CLI
    | only affect the CLI SAPI's cache. To actually invalidate the FPM
    | workers' shared OPcache, the operation must run *inside* an FPM worker
    | — this signed route exists for exactly that: a deploy script curls it
    | after deploying new code, instead of (or in addition to) the artisan
    | commands.
    |
    | Disabled by default. Enable explicitly and keep it behind the signed
    | URL — do not expose it unsigned.
    |
    */
    'route' => [
        'enabled' => env('KILN_ROUTE_ENABLED', false),
        'prefix' => env('KILN_ROUTE_PREFIX', 'kiln'),
        'middleware' => ['web'],
    ],

];
