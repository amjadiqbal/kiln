<?php

use AmjadIqbal\Kiln\Tests\RouteEnabledTestCase;
use AmjadIqbal\Kiln\Tests\TestCase;

// Pest only auto-loads a single tests/Pest.php — there is no per-directory
// Pest.php discovery — so every directory-specific binding lives here.
// tests/Feature/RouteEnabled/ needs the HTTP route registered at boot time
// (kiln.route.enabled is read once during boot, so it must be set in
// getEnvironmentSetUp(), not toggled mid-test); everything else uses the
// plain TestCase. Pest refuses to bind the same path twice, so the plain
// TestCase binding lists every other test location explicitly rather than
// the whole tests/ directory.
uses(RouteEnabledTestCase::class)->in(__DIR__.'/Feature/RouteEnabled');
uses(TestCase::class)->in(
    __DIR__.'/OpcacheManagerTest.php',
    __DIR__.'/Feature/CommandsTest.php',
    __DIR__.'/Feature/RouteDisabledByDefaultTest.php',
);
