<?php

namespace AmjadIqbal\Kiln\Commands;

use AmjadIqbal\Kiln\OpcacheManager;
use Illuminate\Console\Command;

class ClearCommand extends Command
{
    protected $signature = 'kiln:clear';

    protected $description = 'Reset (clear) OPcache in this process';

    public function handle(OpcacheManager $opcache): int
    {
        if (! $opcache->extensionLoaded()) {
            $this->components->error('The Zend OPcache extension is not loaded.');

            return self::FAILURE;
        }

        if (! $opcache->isActive()) {
            $this->components->error(
                $opcache->isCli()
                    ? 'OPcache is not active for the CLI SAPI. Set opcache.enable_cli=1 to run this command, or use the HTTP route to clear FPM instead.'
                    : 'OPcache is disabled (opcache.enable=0).'
            );

            return self::FAILURE;
        }

        if ($opcache->isCli()) {
            $this->components->warn(
                'Running from the CLI only clears the CLI SAPI\'s own OPcache — it does not reach the FPM worker pool\'s shared memory. For a real FPM deploy, hit the kiln HTTP route instead (see config/kiln.php).'
            );
        }

        if ($opcache->clear()) {
            $this->components->info('OPcache cleared.');

            return self::SUCCESS;
        }

        $this->components->error('opcache_reset() returned false.');

        return self::FAILURE;
    }
}
