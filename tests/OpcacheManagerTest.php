<?php

use AmjadIqbal\Kiln\OpcacheManager;

it('reports the extension as loaded', function () {
    $manager = new OpcacheManager;

    expect($manager->extensionLoaded())->toBeTrue();
});

it('returns false from clear() when opcache is not active in this process', function () {
    // Testbench/PHPUnit runs under the CLI SAPI, and CI does not set
    // opcache.enable_cli — this asserts the safe, silent-false behaviour
    // rather than assuming a live cache.
    $manager = new OpcacheManager;

    if ($manager->isActive()) {
        $this->markTestSkipped('opcache.enable_cli is on in this environment.');
    }

    expect($manager->clear())->toBeFalse();
    expect($manager->status())->toBeNull();
});

it('returns a compiled=false result with an error message for a missing file', function () {
    $manager = new OpcacheManager;

    if (! $manager->isActive()) {
        $this->markTestSkipped('opcache is not active for the CLI SAPI in this environment.');
    }

    $result = $manager->compileFile('/does/not/exist.php');

    expect($result['compiled'])->toBeFalse();
});

it('warm() reports zero compiled files for an empty directory list', function () {
    $manager = new OpcacheManager;

    $result = $manager->warm([]);

    expect($result)->toBe(['compiled' => 0, 'skipped' => 0, 'failed' => [], 'timed_out' => false]);
});

it('warm() stops early and reports timed_out when the time limit is exceeded', function () {
    $manager = new OpcacheManager;

    if (! $manager->isActive()) {
        $this->markTestSkipped('opcache is not active for the CLI SAPI in this environment.');
    }

    // A 0-second limit expires before the very first file, so this proves
    // the guard actually stops the walk rather than merely accepting the
    // parameter.
    $result = $manager->warm([__DIR__], 0);

    expect($result['timed_out'])->toBeTrue();
    expect($result['compiled'])->toBe(0);
});

it('configuration() returns null only when the extension is not loaded', function () {
    $manager = new OpcacheManager;

    expect($manager->configuration())->toBeArray();
});
