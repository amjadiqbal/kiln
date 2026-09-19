# Kiln: Deploy-Time OPcache Control for Laravel

Artisan commands (and an optional signed HTTP route) to clear, warm, and inspect PHP's OPcache
for Laravel apps deployed with `opcache.validate_timestamps=0` — the recommended production
setting under Octane, FrankenPHP, or any standard FPM deploy. Without invalidating OPcache on
every deploy, your app keeps serving stale bytecode until the process pool restarts.

[![Tests](https://github.com/AmjadIqbal/kiln/actions/workflows/ci.yml/badge.svg)](https://github.com/AmjadIqbal/kiln/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/amjadiqbal/kiln.svg)](https://packagist.org/packages/amjadiqbal/kiln)
[![License](https://img.shields.io/packagist/l/amjadiqbal/kiln.svg)](https://github.com/AmjadIqbal/kiln/blob/main/LICENSE.md)

## Why this exists

`appstract/laravel-opcache` did this job for years (138k+ downloads/month) but has been dead
since December 2020 and pins `guzzlehttp/guzzle: ^6.3.1|^7.0` — a version ceiling Guzzle has long
since moved past, which means **it cannot even be installed** on a current Laravel app. Kiln is a
from-scratch replacement targeting PHP 8.3+ and Laravel 12/13, with zero HTTP client dependency.

## The one thing you need to understand before using this

**A `php artisan` process and your FPM worker pool do not share the same OPcache.** Each PHP SAPI
process group gets its own OPcache shared-memory segment. Running `php artisan kiln:clear` over
SSH clears the *CLI's* OPcache — useful for confirming your config, but it does **not** touch the
bytecode your FPM workers are actually serving to real requests.

To invalidate OPcache for real traffic, the operation has to run *inside* a worker that shares
memory with the pool serving your site. That's what the HTTP route is for — see below.

## Installation

```bash
composer require amjadiqbal/kiln
```

Laravel's package auto-discovery registers the service provider automatically. Publish the config
if you want to change the warmed paths or enable the HTTP route:

```bash
php artisan vendor:publish --tag=kiln-config
```

## Artisan commands

```bash
php artisan kiln:clear     # opcache_reset() in this process
php artisan kiln:warm      # compile every file under config('kiln.warm_paths') into OPcache
php artisan kiln:status    # hit rate, memory usage, cached script count
php artisan kiln:config    # dump the relevant opcache.* ini directives, flag risky settings
```

`kiln:warm` compiles files with `opcache_compile_file()`, which **does not execute** the file's
top-level code — it's safe to run against `app/`, `config/`, and `routes/` without triggering
side effects, migrations, or route registration twice.

Every command warns explicitly when it detects it's running under the CLI SAPI, since that's the
process whose OPcache doesn't matter for production traffic.

## The HTTP route — for real FPM deploys

Enable it in `config/kiln.php` (or via `.env`):

```env
KILN_ROUTE_ENABLED=true
```

This registers three signed routes:

- `GET /kiln/opcache/clear`
- `GET /kiln/opcache/warm`
- `GET /kiln/opcache/status`

All three require a valid signature — generate a URL from your deploy script or a scheduled job:

```php
use Illuminate\Support\Facades\URL;

$url = URL::signedRoute('kiln.clear');
```

Then, from your deploy script:

```bash
curl -fsS "$(php artisan tinker --execute=\"echo URL::signedRoute('kiln.clear');\")"
```

or simpler — generate the signed URL once and store it as a deploy secret, since a signed URL
without an expiry stays valid until you rotate `APP_KEY`.

Hitting the route once resets the shared OPcache segment for every worker in that pool — this is
standard Zend OPcache shared-memory behaviour, not something Kiln does itself; the route exists
purely so the reset happens *inside* an FPM worker instead of a separate CLI process.

## Configuration

```php
// config/kiln.php
return [
    'warm_paths' => ['app', 'bootstrap/cache', 'config', 'routes'],
    'warm_vendor' => false,
    'route' => [
        'enabled' => env('KILN_ROUTE_ENABLED', false),
        'prefix' => env('KILN_ROUTE_PREFIX', 'kiln'),
        'middleware' => ['web'],
    ],
];
```

## Testing

```bash
composer test
```

## License

MIT. See [LICENSE.md](LICENSE.md).
