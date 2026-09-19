<?php

namespace AmjadIqbal\Kiln;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class OpcacheManager
{
    /**
     * Whether the OPcache extension is loaded at all.
     */
    public function extensionLoaded(): bool
    {
        return extension_loaded('Zend OPcache');
    }

    /**
     * Whether OPcache is usable in *this* process (extension loaded, enabled,
     * and — for a CLI process — opcache.enable_cli is also on). Every public
     * method below is a silent no-op when this is false, exactly like the
     * underlying opcache_* functions are, so callers must check this first
     * rather than trust a bare true/false return.
     */
    public function isActive(): bool
    {
        if (! $this->extensionLoaded()) {
            return false;
        }

        if (! filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if (PHP_SAPI === 'cli' && ! filter_var(ini_get('opcache.enable_cli'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return true;
    }

    /**
     * True when this process is running under the CLI SAPI. Used to warn
     * that kiln:clear/kiln:warm run this way only affect the CLI's own
     * OPcache segment, not the FPM worker pool's.
     */
    public function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * Reset (clear) the entire OPcache. Returns false if OPcache isn't
     * active in this process — matches opcache_reset()'s own silent-false
     * behaviour rather than throwing.
     */
    public function clear(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return opcache_reset();
    }

    /**
     * Force-invalidate a single file, bypassing opcache.validate_timestamps.
     */
    public function invalidate(string $path, bool $force = true): bool
    {
        if (! $this->isActive() || ! is_file($path)) {
            return false;
        }

        return opcache_invalidate($path, $force);
    }

    /**
     * Raw opcache_get_status() output, or null if OPcache isn't active.
     */
    public function status(bool $includeScripts = false): ?array
    {
        if (! $this->isActive()) {
            return null;
        }

        $status = opcache_get_status($includeScripts);

        return $status === false ? null : $status;
    }

    /**
     * Raw opcache_get_configuration() output, or null if the extension
     * isn't loaded. Configuration (the ini directives) is readable even
     * when opcache.enable/enable_cli are off, since those are just more
     * ini values inside it.
     */
    public function configuration(): ?array
    {
        if (! $this->extensionLoaded()) {
            return null;
        }

        $config = opcache_get_configuration();

        return $config === false ? null : $config;
    }

    /**
     * Compile a single file into OPcache without executing it. Wrapped in
     * try/catch because a file with a parse error throws a catchable
     * ParseError/CompileError from inside opcache_compile_file() itself —
     * one bad file must not abort a whole warm run.
     *
     * @return array{compiled: bool, error: ?string}
     */
    public function compileFile(string $path): array
    {
        if (! $this->isActive()) {
            return ['compiled' => false, 'error' => 'opcache is not active in this process'];
        }

        try {
            $result = opcache_compile_file($path);

            return ['compiled' => (bool) $result, 'error' => $result ? null : 'opcache_compile_file returned false'];
        } catch (Throwable $e) {
            return ['compiled' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Warm every .php file found under the given directories.
     *
     * @param  string[]  $directories  Absolute paths to walk.
     * @return array{compiled: int, skipped: int, failed: array<string, string>}
     */
    public function warm(array $directories): array
    {
        $compiled = 0;
        $skipped = 0;
        $failed = [];

        foreach ($this->phpFilesIn($directories) as $file) {
            $path = $file->getPathname();
            $result = $this->compileFile($path);

            if ($result['compiled']) {
                $compiled++;
            } elseif ($result['error'] === 'opcache is not active in this process') {
                $skipped++;
            } else {
                $failed[$path] = (string) $result['error'];
            }
        }

        return ['compiled' => $compiled, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * @param  string[]  $directories
     * @return iterable<SplFileInfo>
     */
    protected function phpFilesIn(array $directories): iterable
    {
        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                /** @var SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() === 'php') {
                    yield $file;
                }
            }
        }
    }
}
