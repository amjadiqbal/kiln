# Changelog

All notable changes to Kiln are documented here.

## v1.0.0 — 2026-09-20

Initial release.

- `kiln:clear` — reset OPcache in the current process.
- `kiln:warm` — compile every configured `.php` file into OPcache without executing it.
- `kiln:status` — report OPcache's live status (hit rate, memory, cached scripts).
- `kiln:config` — dump the OPcache ini directives that matter for deployment and flag risky
  combinations (e.g. `opcache.validate_timestamps` on in production).
- A signed HTTP route (`/kiln/opcache/{clear,warm,status}`, disabled by default) for invalidating
  an FPM worker pool's shared OPcache — a `php artisan` process cannot reach FPM's shared memory
  from the CLI SAPI, so the route runs the same operations inside an actual FPM worker instead.
- No HTTP client dependency (no Guzzle) — the route is served by the app itself.
