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
    | Warm time limit (seconds)
    |--------------------------------------------------------------------------
    |
    | A recursive warm over app/ (and optionally vendor/) run over HTTP inside
    | an FPM worker can exceed max_execution_time and pin that worker for the
    | whole walk — on a small pool that's an outage, not just a slow request.
    | kiln:warm and the HTTP route both stop early once this many seconds have
    | elapsed and report partial progress instead of running unbounded.
    |
    */
    'warm_time_limit' => env('KILN_WARM_TIME_LIMIT', 20),

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
    | `clear` and `warm` are state-changing and registered as POST only, so
    | link prefetchers, chat-app unfurlers, security scanners and proxies
    | that blindly fetch GET URLs can't trigger them by accident. `status` is
    | read-only and stays GET.
    |
    | Every generated URL MUST use URL::temporarySignedRoute() with a short
    | expiry, never the permanent URL::signedRoute() — a permanent signed URL
    | is a permanent unauthenticated credential the moment it leaks into a
    | deploy log, shell history, or `ps` output. Every route runs Kiln's own
    | `kiln.signed` middleware (RequireTemporarySignature), which validates
    | the HMAC first and then additionally rejects any otherwise-valid
    | signature that has no `expires` parameter at all, so a permanent URL
    | can't be used even by mistake.
    |
    | No `web` middleware by default — this is a deploy-script endpoint, not
    | a browser one, and starting a session on every call needlessly couples
    | an ops endpoint to the session driver. Add 'web' yourself if you
    | genuinely want it (e.g. to layer on CSRF-aware tooling).
    |
    | absolute_signature (default true, the strictest option): behind a
    | reverse proxy that terminates TLS without TrustProxies configured
    | correctly, the app can see the request as http:// on an internal host
    | while the URL was signed against the public https:// host — the HMAC
    | then never matches and every call 403s with "Invalid signature",
    | indistinguishable from a wrong APP_KEY. Set this to false to validate
    | only the path + query (immune to scheme/host rewriting) instead of
    | the full absolute URL.
    |
    | IMPORTANT: this flag only changes how Kiln *validates* — it does not
    | retroactively fix a URL that was already generated the other way. The
    | $absolute flag is baked into the HMAC itself at generation time
    | (Illuminate\Routing\UrlGenerator::temporarySignedRoute()'s own
    | `$absolute = true` default), so if you set this to false you MUST also
    | generate every URL with `absolute: false`:
    |   URL::temporarySignedRoute('kiln.clear', now()->addMinutes(5), [], absolute: false)
    | A URL generated the default (absolute) way will simply fail validation
    | under relative mode, and vice versa — confirmed directly against real
    | UrlGenerator source and a real request/response round-trip, not
    | assumed.
    |
    */
    'route' => [
        'enabled' => env('KILN_ROUTE_ENABLED', false),
        'prefix' => env('KILN_ROUTE_PREFIX', 'kiln'),
        'middleware' => [],
        'absolute_signature' => env('KILN_ABSOLUTE_SIGNATURE', true),
    ],

];
