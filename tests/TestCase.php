<?php

namespace AmjadIqbal\Kiln\Tests;

use AmjadIqbal\Kiln\KilnServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [KilnServiceProvider::class];
    }
}
