# Contributing

## CI is local

GitHub workflows don't run checks for this repo (Amjad, 2026-10-10: failed runs flood his email). The `CI`
workflow is disabled and its file (`.github/workflows/ci.yml`) stays only as a reference for what the local run
covers. Don't re-enable or trigger it. `Release` (`release.yml`) only runs on a pushed `v*.*.*` tag.

Every PR runs the same matrix on the developer's machine and pastes the summary block into its description:

```bash
scripts/ci-local.sh                                      # PHP 8.3/8.4/8.5 × Laravel 12/13, the same steps as ci.yml
PHP_VERSIONS=8.4 LARAVEL_VERSIONS=13 scripts/ci-local.sh # one cell
```

Each cell pins Testbench to its Laravel major (12 → `^10.0`, 13 → `^11.0`), runs `composer update`, checks the
intended `laravel/framework` major resolved, then runs Pest, Pint `--test` and PHPStan with opcache on in the CLI.
It finds `php8.x` on PATH or Homebrew's `php@8.x`, and puts `composer.json` back when it's done.
