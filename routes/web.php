<?php

use AmjadIqbal\Kiln\Http\Controllers\OpcacheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kiln HTTP routes
|--------------------------------------------------------------------------
|
| Registered only when kiln.route.enabled is true (see config/kiln.php).
| Every route requires a valid signed URL — generate one with
| URL::signedRoute('kiln.clear') from a deploy script or scheduled job,
| never expose these unsigned.
|
*/

Route::middleware(config('kiln.route.middleware', ['web']))
    ->prefix(config('kiln.route.prefix', 'kiln'))
    ->name('kiln.')
    ->group(function () {
        Route::get('opcache/clear', [OpcacheController::class, 'clear'])
            ->name('clear')
            ->middleware('signed');

        Route::get('opcache/warm', [OpcacheController::class, 'warm'])
            ->name('warm')
            ->middleware('signed');

        Route::get('opcache/status', [OpcacheController::class, 'status'])
            ->name('status')
            ->middleware('signed');
    });
