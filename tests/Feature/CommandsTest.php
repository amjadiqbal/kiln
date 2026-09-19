<?php

use AmjadIqbal\Kiln\OpcacheManager;

it('kiln:status runs without error', function () {
    $this->artisan('kiln:status')->assertSuccessful();
});

it('kiln:config runs without error', function () {
    $this->artisan('kiln:config')->assertSuccessful();
});

it('kiln:clear fails cleanly when opcache is not active in this process', function () {
    $manager = app(OpcacheManager::class);

    $this->artisan('kiln:clear')
        ->assertExitCode($manager->isActive() ? 0 : 1);
});

it('kiln:warm exits cleanly whether or not opcache is active in this process', function () {
    $manager = app(OpcacheManager::class);

    $this->artisan('kiln:warm')
        ->assertExitCode($manager->isActive() ? 0 : 1);
});

it('kiln:warm --time-limit=0 stops early and reports it, when opcache is active', function () {
    $manager = app(OpcacheManager::class);

    if (! $manager->isActive()) {
        $this->markTestSkipped('opcache is not active for the CLI SAPI in this environment.');
    }

    $this->artisan('kiln:warm', ['--time-limit' => 0])
        ->expectsOutputToContain('Stopped early')
        ->assertSuccessful();
});
