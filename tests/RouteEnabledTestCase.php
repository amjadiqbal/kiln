<?php

namespace AmjadIqbal\Kiln\Tests;

/**
 * kiln.route.enabled is read once, at boot() time — flipping the config
 * value inside a test after the app has already booted does not retroactively
 * register the routes. Tests that need the HTTP route present set it via
 * getEnvironmentSetUp() instead, which runs before boot.
 */
abstract class RouteEnabledTestCase extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('kiln.route.enabled', true);
    }
}
