<?php

use AmjadIqbal\Kiln\Http\Controllers\OpcacheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Kiln HTTP routes
|--------------------------------------------------------------------------
|
| Registered only when kiln.route.enabled is true (see config/kiln.php).
| Every route requires a valid TEMPORARY signed URL — generate one with
| URL::temporarySignedRoute('kiln.clear', now()->addMinutes(5)) from a
| deploy script or scheduled job. URL::signedRoute() (no expiry) is
| rejected by the `kiln.signed` middleware even if the signature itself is
| otherwise valid — see RequireTemporarySignature for why.
|
| `clear` and `warm` are state-changing and POST-only, so a GET request
| from a link prefetcher, chat unfurler, security scanner or proxy can't
| trigger them. `status` is read-only and stays GET.
|
*/

Route::middleware(config('kiln.route.middleware', []))
    ->prefix(config('kiln.route.prefix', 'kiln'))
    ->name('kiln.')
    ->group(function () {
        Route::post('opcache/clear', [OpcacheController::class, 'clear'])
            ->name('clear')
            ->middleware('kiln.signed');

        Route::post('opcache/warm', [OpcacheController::class, 'warm'])
            ->name('warm')
            ->middleware('kiln.signed');

        Route::get('opcache/status', [OpcacheController::class, 'status'])
            ->name('status')
            ->middleware('kiln.signed');
    });
