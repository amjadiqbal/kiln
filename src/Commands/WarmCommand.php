<?php

namespace AmjadIqbal\Kiln\Commands;

use AmjadIqbal\Kiln\OpcacheManager;
use Illuminate\Console\Command;

class WarmCommand extends Command
{
    protected $signature = 'kiln:warm
        {--vendor : Also warm vendor/ (overrides config kiln.warm_vendor)}
        {--time-limit= : Stop after this many seconds and report partial progress (default: no limit on the CLI — kiln.warm_time_limit only bounds the HTTP route)}';

    protected $description = 'Compile every configured .php file into OPcache without executing it';

    public function handle(OpcacheManager $opcache): int
    {
        if (! $opcache->isActive()) {
            $this->components->error(
                $opcache->isCli()
                    ? 'OPcache is not active for the CLI SAPI. Set opcache.enable_cli=1 to run this command, or use the HTTP route to warm FPM instead.'
                    : 'OPcache is disabled (opcache.enable=0).'
            );

            return self::FAILURE;
        }

        if ($opcache->isCli()) {
            $this->components->warn(
                'Running from the CLI only warms the CLI SAPI\'s own OPcache — it does not reach the FPM worker pool\'s shared memory. For a real FPM deploy, hit the kiln HTTP route instead (see config/kiln.php).'
            );
        }

        $paths = config('kiln.warm_paths', []);
        $warmVendor = $this->option('vendor') || config('kiln.warm_vendor', false);

        if ($warmVendor) {
            $paths[] = 'vendor';
        }

        $directories = array_map(
            fn (string $path) => base_path($path),
            $paths
        );

        $timeLimit = $this->option('time-limit') !== null ? (int) $this->option('time-limit') : null;

        $this->components->task('Warming OPcache', function () use ($opcache, $directories, $timeLimit, &$result) {
            $result = $opcache->warm($directories, $timeLimit);

            return true;
        });

        $this->components->info("Compiled {$result['compiled']} file(s).");

        if ($result['skipped'] > 0) {
            $this->components->warn("Skipped {$result['skipped']} file(s) — OPcache became inactive mid-run.");
        }

        if (! empty($result['failed'])) {
            $this->components->warn(count($result['failed']).' file(s) failed to compile:');

            foreach ($result['failed'] as $file => $error) {
                $this->line("  <fg=red>{$file}</>: {$error}");
            }
        }

        if ($result['timed_out']) {
            $this->components->warn("Stopped early: the --time-limit={$timeLimit} was reached before every file was compiled. Run again to continue, or raise --time-limit.");
        }

        return self::SUCCESS;
    }
}
