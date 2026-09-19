<?php

namespace AmjadIqbal\Kiln\Commands;

use AmjadIqbal\Kiln\OpcacheManager;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    protected $signature = 'kiln:status';

    protected $description = 'Show OPcache status for this process';

    public function handle(OpcacheManager $opcache): int
    {
        if (! $opcache->extensionLoaded()) {
            $this->components->error('The Zend OPcache extension is not loaded.');

            return self::FAILURE;
        }

        if (! $opcache->isActive()) {
            $this->components->warn(
                $opcache->isCli()
                    ? 'OPcache is not active for the CLI SAPI (opcache.enable_cli=0). This is normal — check status via the HTTP route to see the FPM pool\'s real state.'
                    : 'OPcache is disabled (opcache.enable=0).'
            );

            return self::SUCCESS;
        }

        $status = $opcache->status();

        if ($status === null) {
            $this->components->error('opcache_get_status() returned false.');

            return self::FAILURE;
        }

        $stats = $status['opcache_statistics'];
        $memory = $status['memory_usage'];

        $this->components->twoColumnDetail('SAPI', PHP_SAPI);
        $this->components->twoColumnDetail('Enabled', $status['opcache_enabled'] ? 'yes' : 'no');
        $this->components->twoColumnDetail('Cache full', $status['cache_full'] ? 'yes' : 'no');
        $this->components->twoColumnDetail('Restart pending', $status['restart_pending'] ? 'yes' : 'no');
        $this->components->twoColumnDetail('Cached scripts', (string) $stats['num_cached_scripts']);
        $this->components->twoColumnDetail('Hits', (string) $stats['hits']);
        $this->components->twoColumnDetail('Misses', (string) $stats['misses']);
        $this->components->twoColumnDetail('Hit rate', number_format($stats['opcache_hit_rate'], 2).'%');
        $this->components->twoColumnDetail('Memory used', $this->formatBytes($memory['used_memory']));
        $this->components->twoColumnDetail('Memory free', $this->formatBytes($memory['free_memory']));
        $this->components->twoColumnDetail('Memory wasted', number_format($memory['current_wasted_percentage'], 2).'%');

        return self::SUCCESS;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
