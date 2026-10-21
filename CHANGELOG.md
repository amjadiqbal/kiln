# Changelog

All notable changes to Kiln are documented here.

## [Unreleased]

Docs only, no code changes.

- README: the CLI-SAPI warning note now says which commands warn and when (`kiln:clear` and
  `kiln:warm` always on the CLI, `kiln:status` only when OPcache is inactive, `kiln:config` never).
- README and `config/kiln.php`: don't add the `web` middleware to the route. Its CSRF check
  rejects the documented `curl -X POST` with a 419. Use session-free middleware such as
  `throttle:10,1` instead.
- README: removed the Discord links until there is a server invite.
- README: the appstract/laravel-opcache comparison and the Guzzle wording now match what
  reproduces on fresh Laravel 12 and 13 apps. New art with no brown.
- CHANGELOG: the v1.0.0 `kiln:config` entry had the `validate_timestamps` check backwards; it
  flags Off (and reminds you to clear OPcache on deploy), not On. The "no Guzzle" line is
  corrected the same way as the README.

## v1.0.0 — 2026-09-20

Initial release.

- `kiln:clear` — reset OPcache in the current process.
- `kiln:warm` — compile every configured `.php` file into OPcache without executing it.
- `kiln:status` — report OPcache's live status (hit rate, memory, cached scripts).
- `kiln:config` — dump the OPcache ini directives that matter for deployment and flag risky
  settings: a reminder when `opcache.validate_timestamps` is Off (the recommended production
  setting, which means OPcache must be cleared on every deploy) and a warning when
  `opcache.max_accelerated_files` is below 10000.
- A signed HTTP route (`/kiln/opcache/{clear,warm,status}`, disabled by default) for invalidating
  an FPM worker pool's shared OPcache — a `php artisan` process cannot reach FPM's shared memory
  from the CLI SAPI, so the route runs the same operations inside an actual FPM worker instead.
- No HTTP client of its own and no Guzzle version constraint — the route is served by the app
  itself. (Guzzle still arrives through Laravel's own `illuminate/http`.)
