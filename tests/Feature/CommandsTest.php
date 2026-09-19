<?php

it('kiln:status runs without error', function () {
    $this->artisan('kiln:status')->assertSuccessful();
});

it('kiln:config runs without error', function () {
    $this->artisan('kiln:config')->assertSuccessful();
});

it('kiln:clear fails cleanly when opcache is not active in this process', function () {
    $manager = app(\AmjadIqbal\Kiln\OpcacheManager::class);

    $this->artisan('kiln:clear')
        ->assertExitCode($manager->isActive() ? 0 : 1);
});

it('kiln:warm exits cleanly whether or not opcache is active in this process', function () {
    $manager = app(\AmjadIqbal\Kiln\OpcacheManager::class);

    $this->artisan('kiln:warm')
        ->assertExitCode($manager->isActive() ? 0 : 1);
});
