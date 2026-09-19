<?php

namespace AmjadIqbal\Kiln\Commands;

use AmjadIqbal\Kiln\OpcacheManager;
use Illuminate\Console\Command;

class ConfigCommand extends Command
{
    protected $signature = 'kiln:config';

    protected $description = 'Show the OPcache ini directives that matter for deployment, and flag risky combinations';

    /**
     * Directives worth surfacing — the rest of opcache_get_configuration()
     * is noise for this purpose.
     */
    protected const RELEVANT_DIRECTIVES = [
        'opcache.enable',
        'opcache.enable_cli',
        'opcache.validate_timestamps',
        'opcache.revalidate_freq',
        'opcache.max_accelerated_files',
        'opcache.memory_consumption',
        'opcache.interned_strings_buffer',
        'opcache.file_cache',
        'opcache.jit',
        'opcache.jit_buffer_size',
    ];

    public function handle(OpcacheManager $opcache): int
    {
        if (! $opcache->extensionLoaded()) {
            $this->components->error('The Zend OPcache extension is not loaded.');

            return self::FAILURE;
        }

        $configuration = $opcache->configuration();

        if ($configuration === null) {
            $this->components->error('opcache_get_configuration() returned false.');

            return self::FAILURE;
        }

        $directives = $configuration['directives'];

        foreach (self::RELEVANT_DIRECTIVES as $key) {
            if (array_key_exists($key, $directives)) {
                $this->components->twoColumnDetail($key, $this->formatValue($directives[$key]));
            }
        }

        $this->newLine();
        $this->flagRisks($directives);

        return self::SUCCESS;
    }

    protected function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'On' : 'Off';
        }

        return (string) $value;
    }

    protected function flagRisks(array $directives): void
    {
        $flagged = false;

        if (! ($directives['opcache.validate_timestamps'] ?? true)) {
            $this->components->warn('opcache.validate_timestamps is Off — this is the recommended production setting, but it means Kiln (or an equivalent) MUST run on every deploy or stale bytecode keeps serving.');
            $flagged = true;
        }

        if (($directives['opcache.max_accelerated_files'] ?? 0) < 10000) {
            $this->components->warn('opcache.max_accelerated_files is low ('.($directives['opcache.max_accelerated_files'] ?? 'unset').'). A typical Laravel app plus vendor/ can exceed this, causing evictions.');
            $flagged = true;
        }

        if (! $flagged) {
            $this->components->info('No risky combinations flagged.');
        }
    }
}
